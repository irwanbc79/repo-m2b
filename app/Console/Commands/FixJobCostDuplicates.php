<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixJobCostDuplicates extends Command
{
    protected $signature = 'diagnose:jc-duplicates
                            {--fix        : Hapus UNPAID orphan yang ditemukan}
                            {--shipment=  : Batasi ke satu shipment ID}
                            {--dry-run    : Preview tanpa eksekusi}';

    protected $description = 'Cari & perbaiki JobCost duplikat: UNPAID orphan + PAID shadow pada shipment & amount yang sama';

    public function handle(): int
    {
        $fix        = $this->option('fix');
        $isDryRun   = $this->option('dry-run');
        $shipmentId = $this->option('shipment');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║        DIAGNOSA DUPLIKAT JOB COST (UNPAID + PAID)            ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // ── Cari (shipment_id, amount) yang punya sekaligus UNPAID & PAID ─────
        $groupQuery = DB::table('job_costs')
            ->select('shipment_id', 'amount')
            ->groupBy('shipment_id', 'amount')
            ->havingRaw("SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) > 0")
            ->havingRaw("SUM(CASE WHEN status = 'paid'   THEN 1 ELSE 0 END) > 0");

        if ($shipmentId) {
            $groupQuery->where('shipment_id', (int) $shipmentId);
        }

        $groups = $groupQuery->get();

        if ($groups->isEmpty()) {
            $this->info('✓ Tidak ada duplikat JC yang ditemukan.' . ($shipmentId ? " (shipment #{$shipmentId})" : ''));
            return 0;
        }

        $duplicates = collect();

        foreach ($groups as $group) {
            $ref = DB::table('shipments')
                ->where('id', $group->shipment_id)
                ->value(DB::raw("COALESCE(awb_number, bl_number, CONCAT('SHP#', id))"));

            // Detail tiap JC dalam grup ini
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

            $unpaidEntries = $entries->where('status', 'unpaid');
            $paidEntries   = $entries->where('status', 'paid');

            foreach ($unpaidEntries as $unpaid) {
                // UNPAID dianggap orphan jika:
                // 1. Tidak ada CashTransaction yang link ke JC ini
                // 2. Ada setidaknya satu PAID JC untuk shipment+amount yang sama
                $hasCt = DB::table('cash_transactions')
                    ->where('job_cost_id', $unpaid->id)
                    ->exists();

                if ($hasCt) {
                    continue; // UNPAID ini punya CT → bukan orphan, lewati
                }

                foreach ($paidEntries as $paid) {
                    $duplicates->push([
                        'shipment_id'   => $group->shipment_id,
                        'ref'           => $ref,
                        'amount'        => $group->amount,
                        'paid_jc_id'    => $paid->id,
                        'paid_desc'     => $paid->description,
                        'paid_vendor'   => $paid->vendor_name,
                        'paid_at'       => substr($paid->date_paid ?? $paid->created_at, 0, 10),
                        'unpaid_jc_id'  => $unpaid->id,
                        'unpaid_desc'   => $unpaid->description,
                        'unpaid_vendor' => $unpaid->vendor_name,
                        'created_at'    => substr($unpaid->created_at, 0, 16),
                    ]);
                }
            }
        }

        if ($duplicates->isEmpty()) {
            $this->info('✓ Tidak ada UNPAID orphan yang ditemukan (semua UNPAID punya CashTransaction).');
            return 0;
        }

        $this->warn("  Ditemukan {$duplicates->count()} pasang duplikat:\n");

        $tableRows = $duplicates->map(fn($d) => [
            $d['ref'],
            'Rp ' . number_format($d['amount'], 0, ',', '.'),
            "JC#{$d['paid_jc_id']} ✓ PAID\n" . mb_strimwidth($d['paid_desc'] ?? '-', 0, 30, '..') . "\n" . mb_strimwidth($d['paid_vendor'], 0, 28, '..') . "\n{$d['paid_at']}",
            "JC#{$d['unpaid_jc_id']} ✗ UNPAID (orphan)\n" . mb_strimwidth($d['unpaid_desc'] ?? '-', 0, 30, '..') . "\n" . mb_strimwidth($d['unpaid_vendor'], 0, 28, '..') . "\n{$d['created_at']}",
        ])->toArray();

        $this->table(
            ['Shipment', 'Amount', 'PAID (tetap)', 'UNPAID ORPHAN (akan dihapus)'],
            $tableRows
        );

        if (!$fix && !$isDryRun) {
            $this->line('');
            $this->line('  Gunakan --fix untuk menghapus UNPAID orphan.');
            $this->line('  Gunakan --dry-run untuk konfirmasi tanpa eksekusi.');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('  ** DRY RUN — tidak ada perubahan **');
            return 0;
        }

        // ── Eksekusi fix ────────────────────────────────────────────────────────
        $orphanIds = $duplicates->pluck('unpaid_jc_id')->unique()->values();
        if (!$this->confirm("  Yakin hapus {$orphanIds->count()} UNPAID orphan job cost?")) {
            $this->info('  Dibatalkan.');
            return 0;
        }

        $deleted = 0;
        foreach ($orphanIds as $id) {
            $row = $duplicates->firstWhere('unpaid_jc_id', $id);
            $affected = DB::table('job_costs')
                ->where('id', $id)
                ->where('status', 'unpaid')
                ->delete();

            if ($affected) {
                $this->line("  ✓ JC#{$id} [{$row['unpaid_desc']}] di {$row['ref']} dihapus.");
                $deleted++;
            }
        }

        $this->info('');
        $this->info("  Selesai: {$deleted} dari {$orphanIds->count()} orphan dihapus.");
        return 0;
    }
}
