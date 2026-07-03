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
        {--include-journaled : Ikutkan payment yang invoice-nya sudah punya jurnal PAY-* dari journals:migrate-payments (risiko dobel pembukuan, cek manual dulu)}
        {--link-existing : HANYA proses payment ber-jurnal PAY-*: buat CashTransaction TANPA jurnal baru, di-link ke jurnal PAY-* (nominal jurnal wajib sama dengan payment)}
        {--yes : Lewati konfirmasi interaktif (untuk eksekusi via SSH non-interaktif)}';

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

        if ($this->option('link-existing')) {
            return $this->linkToExistingJournals($journaled, $dryRun);
        }

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

        if ($targets->isEmpty()) {
            return self::SUCCESS;
        }

        if (!$this->option('yes') && !$this->confirm("Lanjutkan membuat {$targets->count()} CashTransaction + jurnal?")) {
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
                    // NOT NULL di prod; dari console Auth::id() null →
                    // pakai pencatat payment asli (audit trail akurat).
                    'created_by'         => $payment->recorded_by ?? 1,
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

    /**
     * Mode --link-existing: untuk payment yang invoice-nya sudah punya jurnal
     * PAY-{invoice_id} (Debit Bank/Kredit Piutang, dibuat journals:migrate-payments).
     * Ledger sudah benar — jurnal baru justru dobel pembukuan. Yang hilang hanya
     * baris kasir, jadi buat CashTransaction yang menunjuk jurnal eksisting.
     * Guard: total debit jurnal wajib sama persis dengan nominal payment
     * (selisih = anomali, harus dikoreksi manual, jangan di-link membabi buta).
     */
    protected function linkToExistingJournals($journaled, bool $dryRun): int
    {
        if ($journaled->isEmpty()) {
            $this->info('Tidak ada payment ber-jurnal PAY-* yang perlu di-link.');
            return self::SUCCESS;
        }

        $rows = [];
        $linkable = [];

        foreach ($journaled as $payment) {
            $journal = Journal::where('reference_no', 'PAY-' . $payment->invoice_id)->first();
            $journalAmount = $journal
                ? (float) DB::table('journal_items')->where('journal_id', $journal->id)->sum('debit')
                : null;

            $match = $journal && abs($journalAmount - (float) $payment->amount) < 1;
            if ($match) {
                $linkable[] = [$payment, $journal];
            }

            $rows[] = [
                $payment->id,
                $payment->invoice->invoice_number ?? ('#' . $payment->invoice_id),
                number_format((float) $payment->amount, 0, ',', '.'),
                $journal->journal_number ?? '-',
                $journalAmount === null ? '-' : number_format($journalAmount, 0, ',', '.'),
                $match ? 'LINK' : 'SKIP (nominal beda — koreksi manual)',
            ];
        }

        $this->table(['Payment ID', 'Invoice', 'Dibayar', 'Jurnal PAY-*', 'Nominal Jurnal', 'Aksi'], $rows);
        $this->info('Akan di-link: ' . count($linkable) . ' dari ' . $journaled->count());

        if ($dryRun) {
            $this->warn('[DRY RUN] Tidak ada data yang ditulis.');
            return self::SUCCESS;
        }

        if (empty($linkable)) {
            return self::SUCCESS;
        }

        if (!$this->option('yes') && !$this->confirm('Lanjutkan membuat ' . count($linkable) . ' CashTransaction (tanpa jurnal baru)?')) {
            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($linkable as [$payment, $journal]) {
            if (CashTransaction::where('invoice_payment_id', $payment->id)->exists()) {
                continue;
            }

            try {
                $debitItem = DB::table('journal_items')->where('journal_id', $journal->id)->where('debit', '>', 0)->first();
                $creditItem = DB::table('journal_items')->where('journal_id', $journal->id)->where('credit', '>', 0)->first();

                CashTransaction::create([
                    'transaction_date' => $payment->payment_date,
                    'type' => 'in',
                    'amount' => $payment->amount,
                    'account_id' => $debitItem->account_id,
                    'counter_account_id' => $creditItem->account_id ?? null,
                    'invoice_id' => $payment->invoice_id,
                    'invoice_payment_id' => $payment->id,
                    'customer_id' => $payment->invoice->customer_id ?? null,
                    'description' => 'Pelunasan ' . ($payment->invoice->invoice_number ?? ('invoice #' . $payment->invoice_id)) . ' (link jurnal ' . $journal->journal_number . ')',
                    'proof_file' => $payment->proof_file,
                    'counterpart_type' => 'customer',
                    'cost_category' => null,
                    'journal_id' => $journal->id,
                    'is_posted' => true,
                    'posted_at' => now(),
                    'created_by' => $payment->recorded_by ?? 1,
                ]);

                $success++;
                $this->line("✓ Payment #{$payment->id} → link ke {$journal->journal_number}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Payment #{$payment->id} gagal: {$e->getMessage()}");
            }
        }

        $this->info("Selesai. Berhasil: {$success}, Gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
