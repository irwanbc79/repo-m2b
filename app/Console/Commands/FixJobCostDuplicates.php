<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixJobCostDuplicates extends Command
{
    protected $signature = 'diagnose:jc-duplicates
                            {--fix         : Jalankan perbaikan (hapus orphan unpaid)}
                            {--shipment=   : Batasi ke satu shipment ID saja}
                            {--dry-run     : Preview perubahan tanpa eksekusi}';

    protected $description = 'Cari & perbaiki JobCost duplikat: UNPAID orphan + PAID dengan CashTransaction yang sama';

    public function handle(): int
    {
        $fix       = $this->option('fix');
        $isDryRun  = $this->option('dry-run');
        $shipmentId = $this->option('shipment');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║        DIAGNOSA DUPLIKAT JOB COST (UNPAID + PAID)            ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // ── Temukan PAID job costs yang punya CashTransaction ──────────────────
        $paidQuery = DB::table('job_costs as jc_paid')
            ->join('cash_transactions as ct', 'ct.job_cost_id', '=', 'jc_paid.id')
            ->join('shipments as s', 'jc_paid.shipment_id', '=', 's.id')
            ->leftJoin('vendors as v', 'jc_paid.vendor_id', '=', 'v.id')
            ->where('jc_paid.status', 'paid')
            ->select(
                'jc_paid.id as paid_jc_id',
                'jc_paid.shipment_id',
                's.awb_number', 's.bl_number',
                'jc_paid.amount',
                'jc_paid.description as paid_desc',
                'ct.id as ct_id',
                DB::raw("COALESCE(v.name,'(no vendor)') as paid_vendor")
            );

        if ($shipmentId) {
            $paidQuery->where('jc_paid.shipment_id', (int) $shipmentId);
        }

        $paidCosts = $paidQuery->get();

        $duplicates = collect();

        foreach ($paidCosts as $paid) {
            // Cari UNPAID job cost di shipment yang sama dengan amount sama dan TANPA CashTransaction
            $orphans = DB::table('job_costs as jc_unpaid')
                ->leftJoin('vendors as v2', 'jc_unpaid.vendor_id', '=', 'v2.id')
                ->where('jc_unpaid.shipment_id', $paid->shipment_id)
                ->where('jc_unpaid.amount', $paid->amount)
                ->where('jc_unpaid.status', 'unpaid')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('cash_transactions')
                      ->whereColumn('cash_transactions.job_cost_id', 'jc_unpaid.id');
                })
                ->select(
                    'jc_unpaid.id as unpaid_jc_id',
                    'jc_unpaid.description as unpaid_desc',
                    DB::raw("COALESCE(v2.name,'(no vendor)') as unpaid_vendor"),
                    'jc_unpaid.created_at'
                )
                ->get();

            foreach ($orphans as $orphan) {
                $duplicates->push([
                    'shipment_id'   => $paid->shipment_id,
                    'ref'           => $paid->awb_number ?? $paid->bl_number ?? "SHP#{$paid->shipment_id}",
                    'amount'        => $paid->amount,
                    'paid_jc_id'    => $paid->paid_jc_id,
                    'paid_desc'     => $paid->paid_desc,
                    'paid_vendor'   => $paid->paid_vendor,
                    'ct_id'         => $paid->ct_id,
                    'unpaid_jc_id'  => $orphan->unpaid_jc_id,
                    'unpaid_desc'   => $orphan->unpaid_desc,
                    'unpaid_vendor' => $orphan->unpaid_vendor,
                    'created_at'    => substr($orphan->created_at, 0, 16),
                ]);
            }
        }

        if ($duplicates->isEmpty()) {
            $this->info('✓ Tidak ada duplikat JC yang ditemukan.' . ($shipmentId ? " (shipment #{$shipmentId})" : ''));
            return 0;
        }

        $this->warn("  Ditemukan {$duplicates->count()} pasang duplikat:\n");

        $tableRows = $duplicates->map(fn($d) => [
            $d['ref'],
            'Rp ' . number_format($d['amount'], 0, ',', '.'),
            "JC#{$d['paid_jc_id']} (PAID)\n" . mb_strimwidth($d['paid_desc'], 0, 28, '..') . "\n" . mb_strimwidth($d['paid_vendor'], 0, 25, '..') . "\nCT#{$d['ct_id']}",
            "JC#{$d['unpaid_jc_id']} (UNPAID) ← ORPHAN\n" . mb_strimwidth($d['unpaid_desc'], 0, 28, '..') . "\n" . mb_strimwidth($d['unpaid_vendor'], 0, 25, '..') . "\ndibuat: {$d['created_at']}",
        ])->toArray();

        $this->table(['Shipment', 'Amount', 'PAID (ada CT)', 'UNPAID ORPHAN (akan dihapus)'], $tableRows);

        if (!$fix && !$isDryRun) {
            $this->line('');
            $this->line('  Gunakan --fix untuk menghapus UNPAID orphan.');
            $this->line('  Gunakan --dry-run untuk preview tanpa eksekusi.');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('  ** DRY RUN — tidak ada perubahan yang disimpan **');
            return 0;
        }

        // ── Eksekusi fix ────────────────────────────────────────────────────────
        if (!$this->confirm("  Yakin hapus {$duplicates->count()} UNPAID orphan job cost?")) {
            $this->info('  Dibatalkan.');
            return 0;
        }

        $deleted = 0;
        foreach ($duplicates as $d) {
            $affected = DB::table('job_costs')
                ->where('id', $d['unpaid_jc_id'])
                ->where('status', 'unpaid')
                ->delete();

            if ($affected) {
                $this->line("  ✓ JC#{$d['unpaid_jc_id']} [{$d['unpaid_desc']}] di {$d['ref']} berhasil dihapus.");
                $deleted++;
            }
        }

        $this->info('');
        $this->info("  Total dihapus: {$deleted} dari {$duplicates->count()} orphan.");

        return 0;
    }
}
