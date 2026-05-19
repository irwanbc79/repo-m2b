<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseAPData extends Command
{
    protected $signature = 'diagnose:ap-data';
    protected $description = 'Diagnosa sumber data Hutang AP dan anomali biaya bulanan';

    public function handle()
    {
        $this->info('');
        $this->info('============================================================');
        $this->info('  DIAGNOSA HUTANG AP & DATA HISTORIS');
        $this->info('============================================================');

        // 1. VendorBill: distribusi per bulan created_at
        $this->info('');
        $this->info('--- [1] VENDOR BILLS UNPAID/PARTIAL per Bulan (created_at) ---');
        $vbByMonth = DB::table('vendor_bills')
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as bln, COUNT(*) as jml, SUM(amount_idr) as total_tagihan, SUM(amount_idr - paid_amount) as outstanding")
            ->groupByRaw("DATE_FORMAT(created_at,'%Y-%m')")
            ->orderBy('bln')
            ->get();

        if ($vbByMonth->isEmpty()) {
            $this->warn('  Tidak ada VendorBill unpaid/partial.');
        } else {
            $headers = ['Bulan', 'Jumlah', 'Total Tagihan (IDR)', 'Outstanding (IDR)'];
            $rows = $vbByMonth->map(fn($r) => [
                $r->bln,
                $r->jml,
                'Rp ' . number_format($r->total_tagihan, 0, ',', '.'),
                'Rp ' . number_format($r->outstanding, 0, ',', '.'),
            ])->toArray();
            $this->table($headers, $rows);
        }

        $vbTotal = DB::table('vendor_bills')
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('SUM(amount_idr - paid_amount) as total')
            ->value('total') ?? 0;
        $this->info("  TOTAL OUTSTANDING VendorBill: Rp " . number_format($vbTotal, 0, ',', '.'));

        // 2. JobCost: distribusi unpaid per bulan shipment created_at
        $this->info('');
        $this->info('--- [2] JOB COSTS UNPAID per Bulan (shipment.created_at) ---');
        $jcByMonth = DB::table('job_costs')
            ->join('shipments', 'job_costs.shipment_id', '=', 'shipments.id')
            ->where('job_costs.status', 'unpaid')
            ->selectRaw("DATE_FORMAT(shipments.created_at,'%Y-%m') as bln, COUNT(*) as jml, SUM(job_costs.amount) as total")
            ->groupByRaw("DATE_FORMAT(shipments.created_at,'%Y-%m')")
            ->orderBy('bln')
            ->get();

        if ($jcByMonth->isEmpty()) {
            $this->warn('  Tidak ada JobCost unpaid.');
        } else {
            $headers = ['Bulan Shipment', 'Jumlah Job Cost', 'Total Unpaid (IDR)'];
            $rows = $jcByMonth->map(fn($r) => [
                $r->bln,
                $r->jml,
                'Rp ' . number_format($r->total, 0, ',', '.'),
            ])->toArray();
            $this->table($headers, $rows);
        }

        // 3. Shipment: distribusi per bulan (cek spike Jan 2026)
        $this->info('');
        $this->info('--- [3] SHIPMENTS per Bulan (created_at) ---');
        $shipByMonth = DB::table('shipments')
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as bln, COUNT(*) as jml")
            ->groupByRaw("DATE_FORMAT(created_at,'%Y-%m')")
            ->orderBy('bln')
            ->get();

        $headers = ['Bulan', 'Jumlah Shipment'];
        $rows = $shipByMonth->map(fn($r) => [$r->bln, $r->jml])->toArray();
        $this->table($headers, $rows);

        // 4. Job Cost ALL (paid+unpaid) per bulan shipment - sumber angka trend report
        $this->info('');
        $this->info('--- [4] TOTAL BIAYA (ALL STATUS) per Bulan Shipment — sumber "Cost" di Trend Report ---');
        $costAllByMonth = DB::table('job_costs')
            ->join('shipments', 'job_costs.shipment_id', '=', 'shipments.id')
            ->selectRaw("DATE_FORMAT(shipments.created_at,'%Y-%m') as bln, COUNT(*) as jml, SUM(job_costs.amount) as total, SUM(CASE WHEN job_costs.status='unpaid' THEN job_costs.amount ELSE 0 END) as unpaid, SUM(CASE WHEN job_costs.status='paid' THEN job_costs.amount ELSE 0 END) as paid")
            ->groupByRaw("DATE_FORMAT(shipments.created_at,'%Y-%m')")
            ->orderBy('bln')
            ->get();

        $headers = ['Bulan', 'Jml', 'Total Biaya', 'Unpaid', 'Paid'];
        $rows = $costAllByMonth->map(fn($r) => [
            $r->bln, $r->jml,
            'Rp ' . number_format($r->total, 0, ',', '.'),
            'Rp ' . number_format($r->unpaid, 0, ',', '.'),
            'Rp ' . number_format($r->paid, 0, ',', '.'),
        ])->toArray();
        $this->table($headers, $rows);

        // 5. Top 10 VendorBill terbesar yang masih unpaid
        $this->info('');
        $this->info('--- [5] TOP 10 VENDOR BILLS TERBESAR YANG MASIH UNPAID ---');
        $top10vb = DB::table('vendor_bills as vb')
            ->leftJoin('vendors', 'vb.vendor_id', '=', 'vendors.id')
            ->whereIn('vb.status', ['unpaid', 'partial'])
            ->selectRaw("vb.bill_number, vendors.name as vendor, vb.bill_date, vb.due_date, vb.amount_idr, vb.paid_amount, (vb.amount_idr - vb.paid_amount) as outstanding, vb.status, vb.created_at")
            ->orderByRaw('(vb.amount_idr - vb.paid_amount) DESC')
            ->limit(10)
            ->get();

        if ($top10vb->isEmpty()) {
            $this->warn('  Tidak ada VendorBill unpaid.');
        } else {
            $headers = ['Bill No', 'Vendor', 'Bill Date', 'Outstanding', 'Status', 'Created At'];
            $rows = $top10vb->map(fn($r) => [
                $r->bill_number ?? '-',
                $r->vendor ?? '(no vendor)',
                $r->bill_date ?? '-',
                'Rp ' . number_format($r->outstanding, 0, ',', '.'),
                $r->status,
                $r->created_at,
            ])->toArray();
            $this->table($headers, $rows);
        }

        // 6. Summary
        $this->info('');
        $this->info('--- [6] RINGKASAN ---');
        $totalVB  = DB::table('vendor_bills')->count();
        $unpaidVB = DB::table('vendor_bills')->whereIn('status', ['unpaid','partial'])->count();
        $paidVB   = DB::table('vendor_bills')->where('status', 'paid')->count();
        $totalJC  = DB::table('job_costs')->count();
        $unpaidJC = DB::table('job_costs')->where('status','unpaid')->count();

        $this->line("  VendorBill : total={$totalVB}, unpaid/partial={$unpaidVB}, paid={$paidVB}");
        $this->line("  JobCost    : total={$totalJC}, unpaid={$unpaidJC}");

        // Deteksi apakah ada VB/JC sebelum tanggal go-live (estimasi: sebelum Feb 2026)
        $goLive = '2026-02-01';
        $oldVB = DB::table('vendor_bills')->whereIn('status',['unpaid','partial'])->where('created_at', '<', $goLive)->count();
        $oldJC = DB::table('job_costs')->where('status','unpaid')->where('created_at', '<', $goLive)->count();
        $oldVBAmount = DB::table('vendor_bills')->whereIn('status',['unpaid','partial'])->where('created_at', '<', $goLive)->selectRaw('SUM(amount_idr-paid_amount) as t')->value('t') ?? 0;
        $oldJCAmount = DB::table('job_costs')->where('status','unpaid')->where('created_at', '<', $goLive)->sum('amount');

        $this->info('');
        $this->info("  [!] Data SEBELUM go-live (< {$goLive}) yang masih UNPAID:");
        $this->line("      VendorBill : {$oldVB} records = Rp " . number_format($oldVBAmount, 0, ',', '.'));
        $this->line("      JobCost    : {$oldJC} records = Rp " . number_format($oldJCAmount, 0, ',', '.'));

        if ($oldVB > 0 || $oldJC > 0) {
            $this->warn('  --> Kemungkinan data dari Accurate yang belum di-reconcile!');
            $this->warn('  --> Ini adalah sumber utama anomali Hutang AP.');
        } else {
            $this->info('  --> Tidak ada data lama sebelum go-live yang unpaid.');
        }

        $this->info('');
        $this->info('============================================================');
        $this->info('  Selesai. Kirim output ini untuk analisis lebih lanjut.');
        $this->info('============================================================');
        $this->info('');

        return 0;
    }
}
