<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixJobCostDuplicates extends Command
{
    protected $signature = 'diagnose:jc-duplicates
                            {--fix        : Hapus shadow job cost (duplikat yang tidak perlu)}
                            {--shipment=  : Batasi ke satu shipment ID}
                            {--dry-run    : Preview tanpa eksekusi}';

    protected $description = 'Cari & perbaiki duplikat JobCost (PAID+PAID atau UNPAID+PAID) pada shipment & amount yang sama';

    public function handle(): int
    {
        $fix        = $this->option('fix');
        $isDryRun   = $this->option('dry-run');
        $shipmentId = $this->option('shipment');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║        DIAGNOSA DUPLIKAT JOB COST (semua status)             ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // ── Cari (shipment_id, amount) yang punya count > 1 ──────────────────
        $groupQuery = DB::table('job_costs')
            ->select('shipment_id', 'amount')
            ->groupBy('shipment_id', 'amount')
            ->havingRaw('COUNT(*) > 1');

        if ($shipmentId) {
            $groupQuery->where('shipment_id', (int) $shipmentId);
        }

        $groups = $groupQuery->get();

        if ($groups->isEmpty()) {
            $this->info('✓ Tidak ada duplikat JC yang ditemukan.' . ($shipmentId ? " (shipment #{$shipmentId})" : ''));
            return 0;
        }

        $toDelete  = collect();
        $toDisplay = collect();

        foreach ($groups as $group) {
            $ref = DB::table('shipments')
                ->where('id', $group->shipment_id)
                ->value(DB::raw("COALESCE(awb_number, bl_number, CONCAT('SHP#', id))"));

            $entries = DB::table('job_costs as jc')
                ->leftJoin('vendors as v', 'jc.vendor_id', '=', 'v.id')
                ->where('jc.shipment_id', $group->shipment_id)
                ->where('jc.amount', $group->amount)
                ->select(
                    'jc.id', 'jc.description', 'jc.status',
                    'jc.created_at', 'jc.date_paid',
                    DB::raw("COALESCE(v.name,'(no vendor)') as vendor_name")
                )
                ->orderBy('jc.created_at')
                ->get();

            // Tandai entri mana yang punya CashTransaction
            $entriesWithCt = $entries->filter(function ($e) {
                return DB::table('cash_transactions')
                    ->where('job_cost_id', $e->id)
                    ->exists();
            });

            // Tambah ke tampilan
            foreach ($entries as $e) {
                $hasCt = $entriesWithCt->contains('id', $e->id);
                $toDisplay->push([
                    'ref'        => $ref,
                    'shipment_id'=> $group->shipment_id,
                    'amount'     => $group->amount,
                    'jc_id'      => $e->id,
                    'desc'       => $e->description,
                    'status'     => $e->status,
                    'vendor'     => $e->vendor_name,
                    'created_at' => substr($e->created_at, 0, 16),
                    'has_ct'     => $hasCt,
                ]);
            }

            // Tentukan yang harus dihapus:
            // Prioritas keep: yang ada linked CT (job_cost_id)
            // Jika tidak ada yang punya CT: keep yang paling lama (ID terkecil)
            if ($entriesWithCt->isNotEmpty()) {
                // Hapus semua yang TIDAK punya CT
                $shadows = $entries->filter(fn($e) => !$entriesWithCt->contains('id', $e->id));
            } else {
                // Tidak ada yang punya CT → hapus yang lebih baru (ID lebih besar)
                $oldest  = $entries->first();
                $shadows = $entries->filter(fn($e) => $e->id !== $oldest->id);
            }

            foreach ($shadows as $s) {
                $toDelete->push([
                    'ref'        => $ref,
                    'shipment_id'=> $group->shipment_id,
                    'jc_id'      => $s->id,
                    'desc'       => $s->description,
                    'status'     => $s->status,
                    'amount'     => $group->amount,
                ]);
            }
        }

        // ── Tampilkan semua duplikat ───────────────────────────────────────────
        $this->warn("  Ditemukan duplikat di " . $groups->count() . " grup (shipment+amount):\n");

        $tableRows = $toDisplay->map(fn($d) => [
            $d['ref'],
            "JC#{$d['jc_id']}",
            mb_strimwidth($d['desc'] ?? '-', 0, 32, '..'),
            mb_strimwidth($d['vendor'], 0, 22, '..'),
            'Rp ' . number_format($d['amount'], 0, ',', '.'),
            strtoupper($d['status']),
            $d['has_ct'] ? '✓ ada' : '✗ tidak',
            $d['created_at'],
        ])->toArray();

        $this->table(
            ['Shipment', 'JC ID', 'Deskripsi', 'Vendor', 'Amount', 'Status', 'Ada CT?', 'Dibuat'],
            $tableRows
        );

        if ($toDelete->isEmpty()) {
            $this->info('  Semua entri memiliki CashTransaction — tidak ada yang aman dihapus otomatis.');
            $this->line('  Hapus manual via UI jika perlu.');
            return 0;
        }

        $this->warn("\n  Akan dihapus ({$toDelete->count()} shadow job cost):");
        foreach ($toDelete as $d) {
            $this->line("    JC#{$d['jc_id']} [{$d['desc']}] {$d['status']} | {$d['ref']} | Rp " . number_format($d['amount'], 0, ',', '.'));
        }

        if (!$fix && !$isDryRun) {
            $this->line('');
            $this->line('  Gunakan --fix untuk menghapus shadow di atas.');
            $this->line('  Gunakan --dry-run untuk preview tanpa eksekusi.');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('  ** DRY RUN — tidak ada perubahan **');
            return 0;
        }

        if (!$this->confirm("  Yakin hapus {$toDelete->count()} shadow job cost?")) {
            $this->info('  Dibatalkan.');
            return 0;
        }

        $deleted = 0;
        foreach ($toDelete as $d) {
            // Safety: jangan hapus jika ada CT yang link ke JC ini
            $hasCt = DB::table('cash_transactions')->where('job_cost_id', $d['jc_id'])->exists();
            if ($hasCt) {
                $this->warn("  ⚠ JC#{$d['jc_id']} punya CT — dilewati demi keamanan.");
                continue;
            }

            DB::table('job_costs')->where('id', $d['jc_id'])->delete();
            $this->line("  ✓ JC#{$d['jc_id']} [{$d['desc']}] dihapus.");
            $deleted++;
        }

        $this->info('');
        $this->info("  Selesai: {$deleted} shadow dihapus.");
        return 0;
    }
}
