<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseReconcileChanges extends Command
{
    protected $signature = 'diagnose:reconcile-changes
                            {--revert-historical : Kembalikan JC yang di-paid via reconcile:historical-jobcosts}
                            {--revert-auto       : Kembalikan JC & CT yang di-link via reconcile:auto-jobcosts}
                            {--dry-run           : Tampilkan apa yang akan di-revert tanpa eksekusi}';

    protected $description = 'Cek & opsional revert perubahan yang dilakukan oleh command reconcile kemarin';

    public function handle()
    {
        $revertHistorical = $this->option('revert-historical');
        $revertAuto       = $this->option('revert-auto');
        $isDryRun         = $this->option('dry-run');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║      DIAGNOSA PERUBAHAN RECONCILE (19 MEI 2026)              ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // ── [1] JC yang di-paid via reconcile:historical-jobcosts ──────────────
        $this->info('─── [1] JC DI-PAID via reconcile:historical-jobcosts ───────────');
        $this->info('        (shipment Des 2025, date_paid = 2026-01-31)');
        $this->info('');

        $historical = DB::table('job_costs as jc')
            ->join('shipments as s', 'jc.shipment_id', '=', 's.id')
            ->leftJoin('vendors as v', 'jc.vendor_id', '=', 'v.id')
            ->where('jc.status', 'paid')
            ->where('jc.date_paid', '2026-01-31')
            ->whereDate('jc.updated_at', '2026-05-19')
            ->select(
                'jc.id', 'jc.description', 'jc.amount', 'jc.date_paid',
                'jc.updated_at', 's.id as ship_id', 's.awb_number',
                DB::raw("DATE_FORMAT(s.created_at,'%Y-%m-%d') as ship_date"),
                DB::raw("COALESCE(v.name,'(no vendor)') as vendor_name")
            )
            ->orderBy('s.created_at')
            ->get();

        if ($historical->isEmpty()) {
            $this->line('  Tidak ditemukan. (mungkin sudah di-revert sebelumnya)');
        } else {
            $headers = ['JC ID', 'Shipment', 'AWB', 'Ship Date', 'Vendor', 'Keterangan', 'Amount'];
            $rows = $historical->map(fn($r) => [
                $r->id,
                "#{$r->ship_id}",
                $r->awb_number ?? '-',
                $r->ship_date,
                mb_strimwidth($r->vendor_name, 0, 22, '..'),
                mb_strimwidth($r->description ?? '-', 0, 28, '..'),
                'Rp ' . number_format($r->amount, 0, ',', '.'),
            ])->toArray();
            $this->table($headers, $rows);
            $this->info('  Total: ' . $historical->count() . ' record | Rp ' . number_format($historical->sum('amount'), 0, ',', '.'));
        }

        // ── [2] JC yang di-paid via reconcile:auto-jobcosts ────────────────────
        $this->info('');
        $this->info('─── [2] JC DI-PAID via reconcile:auto-jobcosts ─────────────────');
        $this->info('        (JC yang sekarang punya CT ter-link, updated 19 Mei 2026)');
        $this->info('');

        $autoLinked = DB::table('job_costs as jc')
            ->join('cash_transactions as ct', 'ct.job_cost_id', '=', 'jc.id')
            ->join('shipments as s', 'jc.shipment_id', '=', 's.id')
            ->leftJoin('vendors as v', 'jc.vendor_id', '=', 'v.id')
            ->where('jc.status', 'paid')
            ->whereDate('jc.updated_at', '2026-05-19')
            ->select(
                'jc.id as jc_id', 'jc.description as jc_desc', 'jc.amount as jc_amount',
                'jc.date_paid', 'jc.updated_at',
                'ct.id as ct_id', 'ct.description as ct_desc', 'ct.amount as ct_amount',
                'ct.transaction_date',
                's.id as ship_id', 's.awb_number',
                DB::raw("COALESCE(v.name,'(no vendor)') as vendor_name")
            )
            ->orderBy('s.id')
            ->get();

        if ($autoLinked->isEmpty()) {
            $this->line('  Tidak ditemukan. (mungkin sudah di-revert sebelumnya)');
        } else {
            $headers = ['JC ID', 'Shipment', 'JC Keterangan', 'JC Amount', 'CT ID', 'CT Date', 'CT Amount'];
            $rows = $autoLinked->map(fn($r) => [
                $r->jc_id,
                "#{$r->ship_id} " . ($r->awb_number ?? ''),
                mb_strimwidth($r->jc_desc ?? '-', 0, 28, '..'),
                'Rp ' . number_format($r->jc_amount, 0, ',', '.'),
                $r->ct_id,
                $r->transaction_date,
                'Rp ' . number_format($r->ct_amount, 0, ',', '.'),
            ])->toArray();
            $this->table($headers, $rows);
            $this->info('  Total: ' . $autoLinked->count() . ' record | Rp ' . number_format($autoLinked->sum('jc_amount'), 0, ',', '.'));
        }

        // ── [3] CT yang job_cost_id-nya di-set kemarin ─────────────────────────
        $this->info('');
        $this->info('─── [3] CASH TRANSACTIONS yang job_cost_id di-set kemarin ──────');
        $this->info('');

        $linkedCT = DB::table('cash_transactions as ct')
            ->leftJoin('vendors as v', 'ct.vendor_id', '=', 'v.id')
            ->whereNotNull('ct.job_cost_id')
            ->whereDate('ct.updated_at', '2026-05-19')
            ->select(
                'ct.id', 'ct.job_cost_id', 'ct.transaction_date',
                'ct.description', 'ct.amount', 'ct.shipment_id',
                DB::raw("COALESCE(v.name,'(no vendor)') as vendor_name")
            )
            ->orderBy('ct.shipment_id')
            ->get();

        if ($linkedCT->isEmpty()) {
            $this->line('  Tidak ditemukan.');
        } else {
            $headers = ['CT ID', 'Shipment', 'Date', 'Vendor', 'Keterangan', 'Amount', 'JC Linked'];
            $rows = $linkedCT->map(fn($r) => [
                $r->id,
                "#{$r->shipment_id}",
                $r->transaction_date,
                mb_strimwidth($r->vendor_name, 0, 20, '..'),
                mb_strimwidth($r->description ?? '-', 0, 25, '..'),
                'Rp ' . number_format($r->amount, 0, ',', '.'),
                "JC#{$r->job_cost_id}",
            ])->toArray();
            $this->table($headers, $rows);
        }

        // ── Revert ─────────────────────────────────────────────────────────────
        if (!$revertHistorical && !$revertAuto) {
            $this->info('');
            $this->info('─── OPSI REVERT ────────────────────────────────────────────────');
            $this->line('  --revert-historical   Kembalikan [1] ke status unpaid');
            $this->line('  --revert-auto         Kembalikan [2] ke unpaid & hapus link CT→JC');
            $this->line('  Tambahkan --dry-run untuk preview tanpa eksekusi.');
            $this->info('');
            return 0;
        }

        $this->info('');

        if ($revertHistorical && $historical->isNotEmpty()) {
            $this->info('─── REVERT [1] HISTORICAL ──────────────────────────────────────');
            $ids = $historical->pluck('id')->toArray();
            $this->warn("  Akan kembalikan {$historical->count()} JC → unpaid, date_paid = NULL");

            if ($isDryRun) {
                $this->warn('  ** DRY RUN — tidak ada perubahan **');
            } else {
                if ($this->confirm('  Lanjutkan revert historical?')) {
                    DB::table('job_costs')->whereIn('id', $ids)->update([
                        'status'     => 'unpaid',
                        'date_paid'  => null,
                        'updated_at' => now(),
                    ]);
                    $this->info("  ✓ {$historical->count()} JC berhasil dikembalikan ke unpaid.");
                }
            }
        }

        if ($revertAuto && $autoLinked->isNotEmpty()) {
            $this->info('─── REVERT [2] AUTO-RECONCILE ──────────────────────────────────');
            $jcIds = $autoLinked->pluck('jc_id')->toArray();
            $ctIds = $autoLinked->pluck('ct_id')->toArray();
            $this->warn("  Akan kembalikan {$autoLinked->count()} JC → unpaid & hapus link job_cost_id dari CT");

            if ($isDryRun) {
                $this->warn('  ** DRY RUN — tidak ada perubahan **');
            } else {
                if ($this->confirm('  Lanjutkan revert auto-reconcile?')) {
                    DB::table('job_costs')->whereIn('id', $jcIds)->update([
                        'status'     => 'unpaid',
                        'date_paid'  => null,
                        'updated_at' => now(),
                    ]);
                    DB::table('cash_transactions')->whereIn('id', $ctIds)->update([
                        'job_cost_id' => null,
                        'updated_at'  => now(),
                    ]);
                    $this->info("  ✓ {$autoLinked->count()} JC dikembalikan ke unpaid.");
                    $this->info("  ✓ {$autoLinked->count()} CT job_cost_id di-hapus.");
                }
            }
        }

        $this->info('');
        return 0;
    }
}
