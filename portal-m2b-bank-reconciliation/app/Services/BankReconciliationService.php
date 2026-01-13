<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\InvoicePayment;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankReconciliationService
{
    /**
     * Toleransi untuk matching
     */
    const DATE_TOLERANCE_DAYS = 3;
    const AMOUNT_TOLERANCE_PERCENT = 1;

    /**
     * Auto-match semua transaksi yang belum direkonsiliasi
     */
    public function autoMatchAll(): array
    {
        $unreconciled = BankTransaction::unreconciled()
            ->credits()
            ->orderBy('transaction_date')
            ->get();

        $matched = 0;
        $suggestions = [];

        foreach ($unreconciled as $transaction) {
            $result = $this->findMatch($transaction);

            if ($result['match']) {
                // Auto-match jika confidence tinggi
                if ($result['confidence'] >= 90) {
                    $this->matchTransaction($transaction, $result['payment'], 'auto');
                    $matched++;
                } else {
                    // Simpan sebagai suggestion
                    $suggestions[] = [
                        'transaction' => $transaction,
                        'payment' => $result['payment'],
                        'confidence' => $result['confidence'],
                        'reason' => $result['reason'],
                    ];
                }
            }
        }

        return [
            'auto_matched' => $matched,
            'suggestions' => $suggestions,
            'total_processed' => $unreconciled->count(),
        ];
    }

    /**
     * Cari potential match untuk sebuah transaksi
     */
    public function findMatch(BankTransaction $transaction): array
    {
        // Hanya match transaksi kredit (uang masuk)
        if (!$transaction->isCredit()) {
            return ['match' => false, 'reason' => 'Transaksi bukan kredit'];
        }

        $amount = $transaction->credit_amount;
        $date = $transaction->transaction_date;
        $description = $transaction->description . ' ' . $transaction->additional_description;

        // 1. Coba match berdasarkan nomor invoice di deskripsi
        $invoiceNumber = BankTransaction::extractInvoiceNumber($description);
        if ($invoiceNumber) {
            $payment = $this->findPaymentByInvoiceNumber($invoiceNumber, $amount);
            if ($payment) {
                return [
                    'match' => true,
                    'payment' => $payment,
                    'confidence' => 95,
                    'reason' => "Match by invoice number: $invoiceNumber",
                ];
            }
        }

        // 2. Match berdasarkan jumlah dan tanggal
        $payment = $this->findPaymentByAmountAndDate($amount, $date);
        if ($payment) {
            $confidence = $this->calculateConfidence($transaction, $payment);
            return [
                'match' => true,
                'payment' => $payment,
                'confidence' => $confidence,
                'reason' => 'Match by amount and date proximity',
            ];
        }

        // 3. Match berdasarkan jumlah saja (dengan toleransi)
        $payment = $this->findPaymentByAmount($amount);
        if ($payment) {
            $confidence = $this->calculateConfidence($transaction, $payment);
            return [
                'match' => true,
                'payment' => $payment,
                'confidence' => max(60, $confidence - 20), // Kurangi confidence
                'reason' => 'Match by amount only (date not confirmed)',
            ];
        }

        return ['match' => false, 'reason' => 'No matching payment found'];
    }

    /**
     * Cari payment berdasarkan nomor invoice
     */
    protected function findPaymentByInvoiceNumber(string $invoiceNumber, float $amount): ?InvoicePayment
    {
        // Cari invoice dengan nomor tersebut
        $invoice = Invoice::where('invoice_number', 'LIKE', "%$invoiceNumber%")->first();

        if (!$invoice) {
            return null;
        }

        // Cari payment yang belum di-match dengan jumlah yang sama
        return InvoicePayment::where('invoice_id', $invoice->id)
            ->whereDoesntHave('bankTransaction')
            ->whereBetween('amount', [
                $amount * (1 - self::AMOUNT_TOLERANCE_PERCENT / 100),
                $amount * (1 + self::AMOUNT_TOLERANCE_PERCENT / 100),
            ])
            ->first();
    }

    /**
     * Cari payment berdasarkan jumlah dan tanggal
     */
    protected function findPaymentByAmountAndDate(float $amount, Carbon $date): ?InvoicePayment
    {
        $startDate = $date->copy()->subDays(self::DATE_TOLERANCE_DAYS);
        $endDate = $date->copy()->addDays(self::DATE_TOLERANCE_DAYS);

        return InvoicePayment::whereDoesntHave('bankTransaction')
            ->whereBetween('amount', [
                $amount * (1 - self::AMOUNT_TOLERANCE_PERCENT / 100),
                $amount * (1 + self::AMOUNT_TOLERANCE_PERCENT / 100),
            ])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderByRaw('ABS(DATEDIFF(payment_date, ?))', [$date])
            ->first();
    }

    /**
     * Cari payment berdasarkan jumlah saja
     */
    protected function findPaymentByAmount(float $amount): ?InvoicePayment
    {
        return InvoicePayment::whereDoesntHave('bankTransaction')
            ->whereBetween('amount', [
                $amount * (1 - self::AMOUNT_TOLERANCE_PERCENT / 100),
                $amount * (1 + self::AMOUNT_TOLERANCE_PERCENT / 100),
            ])
            ->orderBy('payment_date', 'desc')
            ->first();
    }

    /**
     * Hitung confidence score
     */
    protected function calculateConfidence(BankTransaction $transaction, InvoicePayment $payment): int
    {
        $confidence = 70; // Base confidence

        // Amount match
        $amountDiff = abs($transaction->credit_amount - $payment->amount) / $payment->amount * 100;
        if ($amountDiff == 0) {
            $confidence += 15;
        } elseif ($amountDiff < 0.5) {
            $confidence += 10;
        }

        // Date proximity
        $daysDiff = abs($transaction->transaction_date->diffInDays($payment->payment_date));
        if ($daysDiff == 0) {
            $confidence += 15;
        } elseif ($daysDiff <= 1) {
            $confidence += 10;
        } elseif ($daysDiff <= 3) {
            $confidence += 5;
        }

        return min(100, $confidence);
    }

    /**
     * Match transaksi bank dengan payment
     */
    public function matchTransaction(
        BankTransaction $transaction,
        InvoicePayment $payment,
        string $matchType = 'manual',
        ?string $notes = null
    ): bool {
        try {
            DB::beginTransaction();

            $transaction->update([
                'is_reconciled' => true,
                'invoice_payment_id' => $payment->id,
                'matched_by' => auth()->id(),
                'matched_at' => now(),
                'matching_notes' => $notes ?? "Matched $matchType",
            ]);

            DB::commit();

            Log::info('Bank transaction matched', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'match_type' => $matchType,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to match transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
            return false;
        }
    }

    /**
     * Unmatch transaksi
     */
    public function unmatchTransaction(BankTransaction $transaction): bool
    {
        try {
            $transaction->update([
                'is_reconciled' => false,
                'invoice_payment_id' => null,
                'matched_by' => null,
                'matched_at' => null,
                'matching_notes' => 'Unmatched by user',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to unmatch transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
            return false;
        }
    }

    /**
     * Get statistics rekonsiliasi
     */
    public function getStatistics(?string $bankName = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = BankTransaction::query();

        if ($bankName) {
            $query->byBank($bankName);
        }

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $total = $query->count();
        $reconciled = (clone $query)->reconciled()->count();
        $unreconciled = (clone $query)->unreconciled()->count();

        $totalCredit = (clone $query)->sum('credit_amount');
        $totalDebit = (clone $query)->sum('debit_amount');

        $reconciledCredit = (clone $query)->reconciled()->sum('credit_amount');
        $unreconciledCredit = (clone $query)->unreconciled()->sum('credit_amount');

        return [
            'total_transactions' => $total,
            'reconciled' => $reconciled,
            'unreconciled' => $unreconciled,
            'reconciliation_rate' => $total > 0 ? round($reconciled / $total * 100, 1) : 0,
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'net_amount' => $totalCredit - $totalDebit,
            'reconciled_credit' => $reconciledCredit,
            'unreconciled_credit' => $unreconciledCredit,
        ];
    }

    /**
     * Get unreconciled payments (payments tanpa match di bank)
     */
    public function getUnreconciledPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return InvoicePayment::whereDoesntHave('bankTransaction')
            ->with(['invoice.customer'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }
}
