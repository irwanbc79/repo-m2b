<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyShipmentPayments extends Command
{
    protected $signature = 'verify:shipment-payments
                            {--month=2026-01 : Bulan shipment yang dicek (format YYYY-MM)}
                            {--shipment= : ID shipment spesifik (opsional)}';

    protected $description = 'Verifikasi cross-check JobCost vs CashTransaction untuk membuktikan apakah biaya sudah dibayar';

    public function handle()
    {
        $month    = $this->option('month');
        $shipmentId = $this->option('shipment');

        $this->info('');
        $this->info('============================================================');
        $this->info('  VERIFIKASI PEMBAYARAN: JOB COST vs CASH TRANSACTION');
        $this->info('============================================================');

        // --- [1] Daftar shipment yang dicek ---
        $shipmentQuery = DB::table('shipments')
            ->selectRaw("id, shipment_number, created_at, customer_id, status");

        if ($shipmentId) {
            $shipmentQuery->where('id', $shipmentId);
        } else {
            $shipmentQuery->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
        }

        $shipments = $shipmentQuery->orderBy('created_at')->get();

        if ($shipments->isEmpty()) {
            $this->warn("  Tidak ada shipment ditemukan.");
            return 0;
        }

        $this->info("  Periode / Shipment: " . ($shipmentId ? "ID #{$shipmentId}" : $month));
        $this->info("  Jumlah shipment   : {$shipments->count()}");
        $this->info('');

        $grandTotalCost    = 0;
        $grandTotalPaid    = 0;
        $grandTotalUnpaid  = 0;
        $grandTotalCashOut = 0;

        foreach ($shipments as $ship) {
            $this->info("  ┌─ Shipment #{$ship->id} | {$ship->shipment_number} | {$ship->created_at}");

            // Job costs untuk shipment ini
            $jobCosts = DB::table('job_costs')
                ->leftJoin('vendors', 'job_costs.vendor_id', '=', 'vendors.id')
                ->where('job_costs.shipment_id', $ship->id)
                ->select(
                    'job_costs.id',
                    'job_costs.description',
                    'job_costs.amount',
                    'job_costs.status',
                    'job_costs.date_paid',
                    'job_costs.created_at',
                    DB::raw("COALESCE(vendors.name, '(no vendor)') as vendor_name")
                )
                ->orderBy('job_costs.status')
                ->orderBy('job_costs.id')
                ->get();

            // Cash transactions untuk shipment ini
            $cashTrx = DB::table('cash_transactions')
                ->leftJoin('vendors', 'cash_transactions.vendor_id', '=', 'vendors.id')
                ->where('cash_transactions.shipment_id', $ship->id)
                ->where('cash_transactions.type', 'out')
                ->select(
                    'cash_transactions.id',
                    'cash_transactions.transaction_date',
                    'cash_transactions.amount',
                    'cash_transactions.description',
                    'cash_transactions.job_cost_id',
                    'cash_transactions.is_posted',
                    DB::raw("COALESCE(vendors.name, '(no vendor)') as vendor_name")
                )
                ->orderBy('cash_transactions.transaction_date')
                ->get();

            $totalCost   = $jobCosts->sum('amount');
            $totalPaid   = $jobCosts->where('status', 'paid')->sum('amount');
            $totalUnpaid = $jobCosts->where('status', 'unpaid')->sum('amount');
            $totalCashOut = $cashTrx->sum('amount');

            $grandTotalCost    += $totalCost;
            $grandTotalPaid    += $totalPaid;
            $grandTotalUnpaid  += $totalUnpaid;
            $grandTotalCashOut += $totalCashOut;

            // Tampilkan job costs
            if ($jobCosts->isNotEmpty()) {
                $this->info("  │  Job Costs ({$jobCosts->count()} item, total Rp " . number_format($totalCost, 0, ',', '.') . "):");
                $headers = ['JC ID', 'Vendor', 'Description', 'Amount', 'Status', 'Date Paid'];
                $rows = $jobCosts->map(fn($r) => [
                    $r->id,
                    mb_strimwidth($r->vendor_name, 0, 25, '..'),
                    mb_strimwidth($r->description ?? '-', 0, 28, '..'),
                    'Rp ' . number_format($r->amount, 0, ',', '.'),
                    $r->status === 'paid' ? '✓ paid' : '✗ UNPAID',
                    $r->date_paid ?? '-',
                ])->toArray();
                $this->table($headers, $rows);
            } else {
                $this->warn("  │  Tidak ada Job Cost.");
            }

            // Tampilkan cash transactions
            if ($cashTrx->isNotEmpty()) {
                $this->info("  │  Cash Transactions OUT ({$cashTrx->count()} item, total Rp " . number_format($totalCashOut, 0, ',', '.') . "):");
                $headers = ['CT ID', 'Date', 'Vendor', 'Description', 'Amount', 'JC Linked', 'Posted'];
                $rows = $cashTrx->map(fn($r) => [
                    $r->id,
                    $r->transaction_date,
                    mb_strimwidth($r->vendor_name, 0, 20, '..'),
                    mb_strimwidth($r->description ?? '-', 0, 25, '..'),
                    'Rp ' . number_format($r->amount, 0, ',', '.'),
                    $r->job_cost_id ? "JC#{$r->job_cost_id}" : '-',
                    $r->is_posted ? '✓' : '✗',
                ])->toArray();
                $this->table($headers, $rows);
            } else {
                $this->warn("  │  !! TIDAK ADA Cash Transaction OUT untuk shipment ini.");
            }

            // Gap analysis
            $gap = $totalUnpaid - $totalCashOut;
            $this->info("  │  RINGKASAN:");
            $this->line("  │    Total Job Cost  : Rp " . number_format($totalCost, 0, ',', '.'));
            $this->line("  │    JC Paid         : Rp " . number_format($totalPaid, 0, ',', '.') . " (" . $jobCosts->where('status','paid')->count() . " item)");
            $this->line("  │    JC Unpaid       : Rp " . number_format($totalUnpaid, 0, ',', '.') . " (" . $jobCosts->where('status','unpaid')->count() . " item)");
            $this->line("  │    Cash Out Tercatat: Rp " . number_format($totalCashOut, 0, ',', '.') . " (" . $cashTrx->count() . " transaksi)");

            if ($totalUnpaid > 0 && $totalCashOut == 0) {
                $this->error("  │    ⚠ BELUM ADA pembayaran di kasir — Rp " . number_format($totalUnpaid, 0, ',', '.') . " outstanding");
            } elseif ($totalUnpaid > 0 && $totalCashOut < $totalUnpaid) {
                $this->warn("  │    ⚠ Pembayaran SEBAGIAN — sisa Rp " . number_format($gap, 0, ',', '.') . " belum terbayar");
            } elseif ($totalUnpaid == 0) {
                $this->info("  │    ✓ Semua JC sudah paid");
            }

            $this->info("  └────────────────────────────────────────────────────────");
            $this->info('');
        }

        // Grand summary
        $this->info('============================================================');
        $this->info('  GRAND TOTAL');
        $this->info('============================================================');
        $this->line("  Total Job Cost       : Rp " . number_format($grandTotalCost, 0, ',', '.'));
        $this->line("  Total JC Paid        : Rp " . number_format($grandTotalPaid, 0, ',', '.'));
        $this->line("  Total JC Unpaid      : Rp " . number_format($grandTotalUnpaid, 0, ',', '.'));
        $this->line("  Total Cash Out Kasir : Rp " . number_format($grandTotalCashOut, 0, ',', '.'));

        $selisih = $grandTotalUnpaid - $grandTotalCashOut;
        if ($selisih > 0) {
            $this->error("  ⚠ SELISIH (belum masuk kasir): Rp " . number_format($selisih, 0, ',', '.'));
        } else {
            $this->info("  ✓ Kasir dan Job Cost seimbang");
        }
        $this->info('');

        return 0;
    }
}
