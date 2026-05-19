<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReconcileHistoricalJobCosts extends Command
{
    protected $signature = 'reconcile:historical-jobcosts
                            {--dry-run : Tampilkan yang akan diubah tanpa eksekusi}
                            {--before=2026-02-01 : Tandai paid semua JC untuk shipment sebelum tanggal ini}
                            {--paid-date=2026-01-31 : Tanggal payment yang dicatat}';

    protected $description = 'Reconcile JobCost historis (data Accurate) yang masih unpaid menjadi paid';

    public function handle()
    {
        $cutoffDate = $this->option('before');
        $paidDate   = $this->option('paid-date');
        $isDryRun   = $this->option('dry-run');

        $this->info('');
        $this->info('============================================================');
        $this->info('  RECONCILE HISTORICAL JOB COSTS');
        $this->info('============================================================');
        $this->info("  Cutoff shipment date : {$cutoffDate}");
        $this->info("  Paid date dicatat    : {$paidDate}");
        $this->info('  Mode                 : ' . ($isDryRun ? '** DRY RUN (tidak ada perubahan) **' : 'EKSEKUSI'));
        $this->info('');

        // Ambil data yang akan diubah
        $candidates = DB::table('job_costs')
            ->join('shipments', 'job_costs.shipment_id', '=', 'shipments.id')
            ->leftJoin('vendors', 'job_costs.vendor_id', '=', 'vendors.id')
            ->where('job_costs.status', 'unpaid')
            ->where('shipments.created_at', '<', $cutoffDate)
            ->select(
                'job_costs.id',
                'job_costs.description',
                'job_costs.amount',
                'job_costs.created_at as jc_created',
                'shipments.created_at as ship_created',
                DB::raw('COALESCE(vendors.name, "(no vendor)") as vendor_name'),
                'shipments.id as shipment_id'
            )
            ->orderBy('shipments.created_at')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('  Tidak ada JobCost unpaid untuk shipment sebelum ' . $cutoffDate);
            return 0;
        }

        // Tampilkan preview
        $this->info("  Ditemukan {$candidates->count()} JobCost unpaid untuk shipment sebelum {$cutoffDate}:");
        $this->info('');

        $headers = ['ID', 'Shipment Date', 'Vendor', 'Description', 'Amount', 'JC Created'];
        $rows = $candidates->map(fn($r) => [
            $r->id,
            substr($r->ship_created, 0, 10),
            $r->vendor_name,
            mb_strimwidth($r->description ?? '-', 0, 30, '...'),
            'Rp ' . number_format($r->amount, 0, ',', '.'),
            substr($r->jc_created, 0, 10),
        ])->toArray();
        $this->table($headers, $rows);

        $totalAmount = $candidates->sum('amount');
        $this->info('');
        $this->info('  Total yang akan di-reconcile: Rp ' . number_format($totalAmount, 0, ',', '.'));

        if ($isDryRun) {
            $this->warn('');
            $this->warn('  ** DRY RUN — tidak ada yang diubah **');
            $this->warn('  Jalankan tanpa --dry-run untuk eksekusi.');
            $this->info('');
            return 0;
        }

        // Konfirmasi
        if (!$this->confirm("  Tandai {$candidates->count()} record sebagai PAID dengan date {$paidDate}?")) {
            $this->info('  Dibatalkan.');
            return 0;
        }

        // Eksekusi bulk update
        $ids = $candidates->pluck('id')->toArray();

        DB::table('job_costs')
            ->whereIn('id', $ids)
            ->update([
                'status'     => 'paid',
                'date_paid'  => $paidDate,
                'updated_at' => now(),
            ]);

        $this->info('');
        $this->info("  ✓ {$candidates->count()} JobCost berhasil ditandai PAID.");
        $this->info('');

        // Verifikasi setelah update
        $remaining = DB::table('job_costs')
            ->join('shipments', 'job_costs.shipment_id', '=', 'shipments.id')
            ->where('job_costs.status', 'unpaid')
            ->where('shipments.created_at', '<', $cutoffDate)
            ->count();

        $totalUnpaidNow = DB::table('job_costs')->where('status', 'unpaid')->sum('amount');

        $this->info("  Sisa unpaid untuk shipment < {$cutoffDate}: {$remaining} record");
        $this->info("  Total Hutang AP sekarang: Rp " . number_format($totalUnpaidNow, 0, ',', '.'));
        $this->info('');
        $this->info('============================================================');

        return 0;
    }
}
