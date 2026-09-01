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
     * Cari saran Akun Lawan (COA) secara cerdas berdasarkan kata kunci transaksi
     */
    public function suggestCounterAccountId(BankTransaction $transaction): ?int
    {
        $text = strtolower($transaction->description . ' ' . $transaction->additional_description . ' ' . $transaction->category);

        // 1. Gaji / Payroll
        if (str_contains($text, 'gaji') || str_contains($text, 'salary') || str_contains($text, 'honor')) {
            $acc = \App\Models\Account::where('code', '5101')->orWhere('name', 'like', '%gaji%')->first();
            if ($acc) return $acc->id;
        }

        // 2. Pengembalian Jaminan / Deposit
        if (str_contains($text, 'jaminan') || str_contains($text, 'deposit') || str_contains($text, 'titipan')) {
            $acc = \App\Models\Account::where('code', '2103')->orWhere('name', 'like', '%jaminan%')->first();
            if ($acc) return $acc->id;
        }

        // 3. Kelebihan Pembayaran / Refund
        if (str_contains($text, 'kelebihan') || str_contains($text, 'refund') || str_contains($text, 'retur') || str_contains($text, 'pengembalian')) {
            $acc = \App\Models\Account::where('code', '2104')->orWhere('name', 'like', '%kelebihan%')->first();
            if ($acc) return $acc->id;
        }

        // 4. Biaya Operasional Lapangan / Trucking / Pelabuhan
        if (str_contains($text, 'trucking') || str_contains($text, 'lift on') || str_contains($text, 'lolo') || str_contains($text, 'thc') || str_contains($text, 'ops') || str_contains($text, 'operasional') || str_contains($text, 'do ') || str_contains($text, 'perbaikan')) {
            $acc = \App\Models\Account::where('code', '6203')->orWhere('name', 'like', '%operasional%')->first();
            if ($acc) return $acc->id;
        }

        // 5. Biaya Administrasi Bank / Fee
        if (str_contains($text, 'biaya adm') || str_contains($text, 'adm ') || str_contains($text, 'fee') || str_contains($text, 'clearing') || str_contains($text, 'saldo min')) {
            $acc = \App\Models\Account::where('code', '6208')->orWhere('name', 'like', '%administrasi%')->first();
            if ($acc) return $acc->id;
        }

        // 6. Pajak Bunga Bank
        if (str_contains($text, 'pajak') || str_contains($text, 'tax')) {
            $acc = \App\Models\Account::where('code', '6207')->orWhere('name', 'like', '%pajak%')->first();
            if ($acc) return $acc->id;
        }

        // 7. Bunga Bank
        if (str_contains($text, 'bunga') || str_contains($text, 'interest')) {
            $acc = \App\Models\Account::where('code', '7101')->orWhere('name', 'like', '%bunga%')->first();
            if ($acc) return $acc->id;
        }

        // Fallback default
        if ($transaction->debit_amount > 0) {
            $fallback = \App\Models\Account::where('code', '6209')->orWhere('type', 'beban_operasional')->orWhere('type', 'beban_lain')->first();
            return $fallback?->id;
        } else {
            $fallback = \App\Models\Account::where('code', '7101')->orWhere('type', 'pendapatan')->first();
            return $fallback?->id;
        }
    }

    /**
     * Dapatkan ID Akun Kas/Bank berdasarkan nama bank
     */
    public function getBankAccountId(string $bankName): ?int
    {
        $bankName = strtolower(trim($bankName));

        if (str_contains($bankName, 'mandiri')) {
            $acc = \App\Models\Account::where('code', '1101')->orWhere('name', 'like', '%mandiri%')->first();
            if ($acc) return $acc->id;
        } elseif (str_contains($bankName, 'bca')) {
            $acc = \App\Models\Account::where('code', '1103')->orWhere('name', 'like', '%bca%')->first();
            if ($acc) return $acc->id;
        }

        $fallback = \App\Models\Account::where('type', 'kas_bank')->first();
        return $fallback?->id;
    }

    /**
     * Buat Jurnal Akuntansi dari Transaksi Bank dan Rekonsiliasi Otomatis
     */
    public function createJournalAndReconcile(
        BankTransaction $transaction,
        int $bankAccountId,
        int $counterAccountId,
        string $description,
        ?string $referenceNo = null,
        ?int $userId = null
    ): \App\Models\Journal {
        return DB::transaction(function () use ($transaction, $bankAccountId, $counterAccountId, $description, $referenceNo, $userId) {
            $amount = $transaction->debit_amount > 0 ? (float) $transaction->debit_amount : (float) $transaction->credit_amount;
            $isExpense = $transaction->debit_amount > 0;
            $trxDate = $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : date('Y-m-d');

            // 1. Generate nomor jurnal anti-duplikasi
            $prefix = 'JR-' . date('ym', strtotime($trxDate)) . '-';
            $lastJournal = \App\Models\Journal::where('journal_number', 'like', $prefix . '%')
                ->orderByDesc('journal_number')
                ->value('journal_number');

            $sequence = 1;
            if ($lastJournal && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastJournal, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            $journalNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            while (\App\Models\Journal::where('journal_number', $journalNumber)->exists()) {
                $sequence++;
                $journalNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }

            // 2. Buat header jurnal
            $journal = \App\Models\Journal::create([
                'journal_number' => $journalNumber,
                'transaction_date' => $trxDate,
                'description' => $description,
                'reference_no' => $referenceNo ?: $transaction->reference_number,
                'created_by' => $userId ?: auth()->id() ?: 1,
                'status' => 'posted',
            ]);

            // 3. Susun Debit & Kredit
            // Pengeluaran Bank (Debit di Rekening Koran = Bank berkurang):
            // -> Debit: Akun Lawan (Beban / Titipan / Refund)
            // -> Kredit: Akun Bank (Mandiri / BCA)
            //
            // Penerimaan Bank (Kredit di Rekening Koran = Bank bertambah):
            // -> Debit: Akun Bank (Mandiri / BCA)
            // -> Kredit: Akun Lawan (Pendapatan / Bunga)
            if ($isExpense) {
                $debitAccountId = $counterAccountId;
                $creditAccountId = $bankAccountId;
            } else {
                $debitAccountId = $bankAccountId;
                $creditAccountId = $counterAccountId;
            }

            // Journal Item 1 (Debit)
            \App\Models\JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => 0,
            ]);

            // Journal Item 2 (Kredit)
            \App\Models\JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $amount,
            ]);

            // 4. Update saldo berjalan akun
            $accDebit = \App\Models\Account::find($debitAccountId);
            if ($accDebit) {
                if ($accDebit->isDebitNormal()) {
                    $accDebit->current_balance += $amount;
                } else {
                    $accDebit->current_balance -= $amount;
                }
                $accDebit->save();
            }

            $accCredit = \App\Models\Account::find($creditAccountId);
            if ($accCredit) {
                if ($accCredit->isDebitNormal()) {
                    $accCredit->current_balance -= $amount;
                } else {
                    $accCredit->current_balance += $amount;
                }
                $accCredit->save();
            }

            // 5. Tautkan transaksi bank dan tandai direkonsiliasi
            $transaction->update([
                'is_reconciled' => true,
                'journal_id' => $journal->id,
                'matched_by' => $userId ?: auth()->id(),
                'matched_at' => now(),
                'matching_notes' => "Direkonsiliasi via Jurnal Otomatis ({$journal->journal_number})",
            ]);

            return $journal;
        });
    }

    /**
     * Unmatch transaksi (termasuk rollback jurnal jika ada)
     */
    public function unmatchTransaction(BankTransaction $transaction): bool
    {
        try {
            DB::transaction(function () use ($transaction) {
                if ($transaction->journal_id) {
                    $journal = \App\Models\Journal::with('items')->find($transaction->journal_id);
                    if ($journal) {
                        // Rollback saldo akun
                        foreach ($journal->items as $item) {
                            $acc = \App\Models\Account::find($item->account_id);
                            if ($acc) {
                                if ($acc->isDebitNormal()) {
                                    $acc->current_balance -= $item->debit;
                                    $acc->current_balance += $item->credit;
                                } else {
                                    $acc->current_balance += $item->debit;
                                    $acc->current_balance -= $item->credit;
                                }
                                $acc->save();
                            }
                        }
                        $journal->items()->delete();
                        $journal->delete();
                    }
                }

                $transaction->update([
                    'is_reconciled' => false,
                    'invoice_payment_id' => null,
                    'journal_id' => null,
                    'matched_by' => null,
                    'matched_at' => null,
                    'matching_notes' => 'Unmatched by user',
                ]);
            });

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
