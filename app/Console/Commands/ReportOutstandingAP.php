<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportOutstandingAP extends Command
{
    protected $signature = 'report:outstanding-ap
                            {--month= : Filter shipment bulan tertentu (YYYY-MM), default semua}
                            {--shipment= : Hanya tampilkan satu shipment ID}';

    protected $description = 'Laporan detail shipment dengan Job Cost yang belum dibayar — untuk ditindaklanjuti staf';

    public function handle()
    {
        $month      = $this->option('month');
        $shipmentId = $this->option('shipment');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║        LAPORAN OUTSTANDING JOB COST BELUM DIBAYAR           ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('  Tanggal cetak : ' . now()->format('d M Y H:i'));
        if ($month)      $this->info("  Filter bulan  : {$month}");
        if ($shipmentId) $this->info("  Filter shipment: #{$shipmentId}");
        $this->info('');

        // Ambil shipment yang masih punya JC unpaid
        $shipQuery = DB::table('shipments as s')
            ->join('customers as c', 's.customer_id', '=', 'c.id')
            ->join('job_costs as jc', 'jc.shipment_id', '=', 's.id')
            ->where('jc.status', 'unpaid')
            ->whereNull('jc.date_paid');

        if ($shipmentId) {
            $shipQuery->where('s.id', $shipmentId);
        } elseif ($month) {
            $shipQuery->whereRaw("DATE_FORMAT(s.created_at, '%Y-%m') = ?", [$month]);
        }

        $shipments = $shipQuery
            ->select(
                's.id as ship_id',
                's.awb_number',
                's.bl_number',
                's.service_type',
                's.shipment_type',
                's.status',
                's.origin',
                's.destination',
                's.commodity',
                's.shipper_name',
                's.consignee_name',
                's.created_at as ship_date',
                'c.company_name',
                'c.customer_code'
            )
            ->groupBy(
                's.id', 's.awb_number', 's.bl_number', 's.service_type', 's.shipment_type',
                's.status', 's.origin', 's.destination', 's.commodity',
                's.shipper_name', 's.consignee_name', 's.created_at',
                'c.company_name', 'c.customer_code'
            )
            ->orderBy('s.created_at')
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('  Tidak ada shipment dengan Job Cost outstanding.');
            return 0;
        }

        $this->info("  Total shipment outstanding: {$shipments->count()} shipment");
        $this->info('');

        $grandTotal = 0;
        $no = 0;

        foreach ($shipments as $ship) {
            $no++;
            $label    = $ship->awb_number ?? $ship->bl_number ?? "ID#{$ship->ship_id}";
            $svcLabel = strtoupper($ship->service_type ?? '-') . ' / ' . strtoupper($ship->shipment_type ?? '-');
            $status   = strtoupper($ship->status ?? '-');

            $this->info("  ┌─────────────────────────────────────────────────────────────");
            $this->info("  │  [{$no}]  #{$ship->ship_id} ─ {$label}");
            $this->info("  │  Customer    : {$ship->company_name} ({$ship->customer_code})");
            $this->info("  │  Tipe        : {$svcLabel}  |  Status: {$status}");
            $this->info("  │  Rute        : {$ship->origin}  →  {$ship->destination}");
            if ($ship->commodity) {
                $this->info("  │  Komoditi    : {$ship->commodity}");
            }
            if ($ship->shipper_name || $ship->consignee_name) {
                $shipper   = $ship->shipper_name   ? "Shipper: {$ship->shipper_name}" : '';
                $consignee = $ship->consignee_name ? "Consignee: {$ship->consignee_name}" : '';
                $this->info("  │  " . implode('  |  ', array_filter([$shipper, $consignee])));
            }
            $this->info("  │  Tgl Buat    : " . substr($ship->ship_date, 0, 10));
            $this->info("  │");
            $this->info("  │  BIAYA YANG BELUM DIBAYAR:");

            // Job costs unpaid untuk shipment ini
            $jobCosts = DB::table('job_costs')
                ->leftJoin('vendors', 'job_costs.vendor_id', '=', 'vendors.id')
                ->where('job_costs.shipment_id', $ship->ship_id)
                ->where('job_costs.status', 'unpaid')
                ->whereNull('job_costs.date_paid')
                ->select(
                    'job_costs.id',
                    'job_costs.description',
                    'job_costs.amount',
                    'job_costs.created_at',
                    DB::raw("COALESCE(vendors.name, '(vendor tidak dicatat)') as vendor_name")
                )
                ->orderBy('job_costs.id')
                ->get();

            $headers = ['No', 'JC ID', 'Keterangan Biaya', 'Vendor', 'Jumlah (Rp)'];
            $rows = [];
            $subTotal = 0;
            $i = 0;
            foreach ($jobCosts as $jc) {
                $i++;
                $rows[] = [
                    $i,
                    $jc->id,
                    mb_strimwidth($jc->description ?? '-', 0, 35, '..'),
                    mb_strimwidth($jc->vendor_name, 0, 28, '..'),
                    number_format($jc->amount, 0, ',', '.'),
                ];
                $subTotal += $jc->amount;
            }

            $this->table($headers, $rows);
            $this->info("  │  TOTAL OUTSTANDING  :  Rp " . number_format($subTotal, 0, ',', '.'));
            $grandTotal += $subTotal;

            // Cek apakah ada CT yang masuk tapi belum di-link
            $unlinkedCT = DB::table('cash_transactions')
                ->where('shipment_id', $ship->ship_id)
                ->where('type', 'out')
                ->whereNull('job_cost_id')
                ->sum('amount');

            if ($unlinkedCT > 0) {
                $this->warn("  │  ⚠ Ada Cash Transaction OUT Rp " . number_format($unlinkedCT, 0, ',', '.') . " belum ter-link ke JC");
            }

            $this->info("  └─────────────────────────────────────────────────────────────");
            $this->info('');
        }

        // Grand total
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║  TOTAL KESELURUHAN BIAYA OUTSTANDING                         ║');
        $this->info('╠══════════════════════════════════════════════════════════════╣');
        $this->info('║  Jumlah Shipment  : ' . str_pad($shipments->count() . ' shipment', 41) . '║');
        $this->info('║  Total Outstanding: Rp ' . str_pad(number_format($grandTotal, 0, ',', '.'), 38) . '║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->warn('  TINDAKAN YANG DIPERLUKAN:');
        $this->warn('  1. Pastikan setiap biaya di atas sudah dibayar ke vendor');
        $this->warn('  2. Input pembayaran di menu Kasir dengan melampirkan bukti');
        $this->warn('  3. Hubungkan transaksi kasir ke Job Cost yang sesuai');
        $this->warn('  4. Jalankan: php artisan reconcile:auto-jobcosts --month=YYYY-MM');
        $this->info('');

        return 0;
    }
}
