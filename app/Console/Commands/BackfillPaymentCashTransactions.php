<?php

namespace App\Console\Commands;

use App\Models\CashTransaction;
use App\Models\InvoicePayment;
use App\Models\Journal;
use App\Services\CashierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPaymentCashTransactions extends Command
{
    protected $signature = 'cashier:backfill-payments
        {--dry-run : Preview tanpa eksekusi}
        {--include-journaled : Ikutkan payment yang invoice-nya sudah punya jurnal PAY-* dari journals:migrate-payments (risiko dobel pembukuan, cek manual dulu)}';

    protected $description = 'Buat CashTransaction + jurnal untuk InvoicePayment yang belum punya (gagal akibat bug enum cost_category)';

    public function handle(CashierService $cashier): int
    {
        $dryRun = $this->option('dry-run');

        $missing = InvoicePayment::with('invoice')
            ->whereNotIn('id', DB::table('cash_transactions')
                ->whereNotNull('invoice_payment_id')
                ->pluck('invoice_payment_id'))
            ->orderBy('payment_date')
            ->get();

        if ($missing->isEmpty()) {
            $this->info('Semua payment sudah punya CashTransaction. Tidak ada yang perlu di-backfill.');
            return self::SUCCESS;
        }

        // Pisahkan payment yang invoice-nya sudah dijurnal invoice-level oleh
        // journals:migrate-payments (reference_no = PAY-{invoice_id}) —
        // membuat jurnal payment baru untuk mereka berisiko dobel pembukuan.
        $journaledInvoiceIds = Journal::where('reference_no', 'like', 'PAY-%')
            ->pluck('reference_no')
            ->map(fn ($ref) => (int) str_replace('PAY-', '', $ref))
            ->all();

        [$journaled, $clean] = $missing->partition(
            fn ($p) => in_array($p->invoice_id, $journaledInvoiceIds, true)
        );

        $targets = $this->option('include-journaled') ? $missing : $clean;

        $this->info("Payment tanpa CashTransaction : {$missing->count()}");
        $this->info("  - invoice sudah dijurnal PAY-* (di-skip default): {$journaled->count()}");
        $this->info("  - akan diproses               : {$targets->count()}");

        $this->table(
            ['Payment ID', 'Invoice', 'Tanggal', 'Jumlah', 'Status'],
            $targets->map(fn ($p) => [
                $p->id,
                $p->invoice->invoice_number ?? ('#' . $p->invoice_id),
                optional($p->payment_date)->format('Y-m-d'),
                number_format((float) $p->amount, 0, ',', '.'),
                in_array($p->invoice_id, $journaledInvoiceIds, true) ? 'sudah ada jurnal PAY-*' : 'bersih',
            ])
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] Tidak ada data yang ditulis.');
            return self::SUCCESS;
        }

        if ($targets->isEmpty() || !$this->confirm("Lanjutkan membuat {$targets->count()} CashTransaction + jurnal?")) {
            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($targets as $payment) {
            // Idempotent: cek ulang tepat sebelum insert
            if (CashTransaction::where('invoice_payment_id', $payment->id)->exists()) {
                continue;
            }

            try {
                $cashier->processPayment([
                    'type'               => 'in',
                    'category'           => 'payment_from_customer',
                    'counterpart_type'   => 'customer',
                    'amount'             => $payment->amount,
                    'transaction_date'   => $payment->payment_date,
                    'invoice_id'         => $payment->invoice_id,
                    'invoice_payment_id' => $payment->id,
                    'customer_id'        => $payment->invoice->customer_id ?? null,
                    'proof_file'         => $payment->proof_file,
                    'description'        => 'Pelunasan ' . ($payment->invoice->invoice_number ?? ('invoice #' . $payment->invoice_id)) . ' (backfill)',
                ]);

                // processPayment bisa memaksa invoice unpaid → paid;
                // kembalikan status yang benar berdasarkan total pembayaran riil.
                $payment->invoice?->recalculateTotalPaid();

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Payment #{$payment->id} gagal: {$e->getMessage()}");
            }
        }

        $this->info("Selesai. Berhasil: {$success}, Gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
