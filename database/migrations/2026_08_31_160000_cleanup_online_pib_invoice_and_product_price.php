<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\CashTransaction;
use App\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Standarisasi Master Produk Layanan Online PIB M2B ke Rp 150.000
        DB::table('products')
            ->where('code', 'IMP-PIB-001')
            ->orWhere('name', 'like', '%Online PIB%')
            ->update([
                'default_price' => 150000,
                'updated_at' => now(),
            ]);

        // 2. Koreksi data anomali Invoice INV/2608/0003
        $inv003 = Invoice::where('invoice_number', 'INV/2608/0003')->first();
        if ($inv003) {
            // Hapus ghost/orphan payment 8.000.000 yang tidak punya catatan buku kas
            $bookedPaymentIds = DB::table('cash_transactions')
                ->whereNotNull('invoice_payment_id')
                ->pluck('invoice_payment_id')
                ->toArray();

            InvoicePayment::where('invoice_id', $inv003->id)
                ->where('amount', '>=', 1000000)
                ->whereNotIn('id', $bookedPaymentIds)
                ->delete();

            // Pastikan grand total invoice Rp 150.000
            if ((float)$inv003->grand_total != 150000 && (float)$inv003->grand_total >= 1000000) {
                $inv003->grand_total = 150000;
                $inv003->subtotal = 150000;
                $inv003->service_total = 150000;
                $inv003->save();
            }

            // Hitung ulang status pembayaran
            $inv003->recalculateTotalPaid();
        }

        // 3. Bersihkan pembayaran anomali tanpa buku kas yang melebihi 2x nilai invoice
        $bookedPaymentIds = DB::table('cash_transactions')
            ->whereNotNull('invoice_payment_id')
            ->pluck('invoice_payment_id')
            ->toArray();

        $orphanAnomalous = InvoicePayment::with('invoice')
            ->whereNotIn('id', $bookedPaymentIds)
            ->get();

        foreach ($orphanAnomalous as $payment) {
            if (!$payment->invoice) {
                // Orphan payment yang invoice-nya sudah dihapus
                $payment->delete();
            } elseif ((float)$payment->amount > (float)$payment->invoice->grand_total * 2 && (float)$payment->amount >= 1000000) {
                // Payment salah ketik nol yang tidak dibukukan kasir
                $payment->delete();
                $payment->invoice->recalculateTotalPaid();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal required for data cleanup
    }
};
