<?php
namespace App\Services;

use App\Models\PettyCashFund;
use App\Models\PettyCashTransaction;
use App\Models\PettyCashTopup;
use App\Models\PettyCashSettingLog;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class PettyCashService
{
    // COA sesuai M2B existing
    const COA_PETTY_CASH = '1102';  // Kas Kecil (Petty Cash)
    const COA_KAS_BESAR = '1101';   // Kas Besar (untuk top-up)

    /**
     * Buat transaksi pengeluaran kas kecil
     */
    public function createTransaction(PettyCashFund $fund, array $data): PettyCashTransaction
    {
        return DB::transaction(function () use ($fund, $data) {
            // Validasi
            if (!$fund->canSpend($data['amount'])) {
                throw new Exception('Saldo tidak cukup atau melebihi limit Rp' . number_format($fund->max_transaction, 0, ',', '.'));
            }
            if (empty($data['proof_file'])) {
                throw new Exception('Bukti transaksi wajib diupload');
            }

            $balanceBefore = $fund->current_balance;
            $balanceAfter = $balanceBefore - $data['amount'];

            // Simpan transaksi (auto-approve untuk pemegang kas)
            $transaction = PettyCashTransaction::create([
                'petty_cash_fund_id' => $fund->id,
                'transaction_number' => PettyCashTransaction::generateNumber(),
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'amount' => $data['amount'],
                'category' => $data['category'],
                'description' => $data['description'],
                'shipment_id' => $data['shipment_id'] ?? null,
                'proof_file' => $data['proof_file'],
                'status' => 'approved',
                'created_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            // Update saldo
            $fund->update(['current_balance' => $balanceAfter]);

            // Buat jurnal otomatis
            $this->createExpenseJournal($transaction);

            return $transaction;
        });
    }

    /**
     * Buat jurnal pengeluaran: Debit Beban, Kredit Kas Kecil
     */
    protected function createExpenseJournal(PettyCashTransaction $t): void
    {
        $expenseCode = $t->category_coa;
        $expenseAcc = Account::where('code', $expenseCode)->first();
        $pcAcc = Account::where('code', self::COA_PETTY_CASH)->first();

        if (!$expenseAcc || !$pcAcc) {
            \Log::warning("COA kas kecil tidak ditemukan: expense={$expenseCode}, pc=" . self::COA_PETTY_CASH);
            return;
        }

        // Generate journal number sesuai format M2B
        $journalNumber = 'JU-PC-' . now()->format('Ymd') . '-' . str_pad(
            Journal::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
        );

        $journal = Journal::create([
            'journal_number' => $journalNumber,
            'transaction_date' => $t->transaction_date,
            'description' => "[Kas Kecil] {$t->category_label}: {$t->description}",
            'reference_no' => $t->transaction_number,
            'status' => 'posted',
            'created_by' => Auth::id(),
            'posted_at' => now(),
        ]);

        // Debit: Beban
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $expenseAcc->id,
            'debit' => $t->amount,
            'credit' => 0,
            'description' => $t->description,
        ]);

        // Kredit: Kas Kecil
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $pcAcc->id,
            'debit' => 0,
            'credit' => $t->amount,
            'description' => $t->description,
        ]);

        $t->update(['journal_id' => $journal->id]);
    }

    /**
     * Request top-up kas kecil
     */
    public function requestTopup(PettyCashFund $fund, float $amount, ?string $notes = null): PettyCashTopup
    {
        if ($amount > $fund->max_topup_amount) {
            throw new Exception("Max top up: Rp" . number_format($fund->max_topup_amount, 0, ',', '.'));
        }

        return PettyCashTopup::create([
            'petty_cash_fund_id' => $fund->id,
            'topup_number' => PettyCashTopup::generateNumber(),
            'amount_requested' => $amount,
            'balance_before' => $fund->current_balance,
            'status' => 'pending',
            'requested_by' => Auth::id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Approve request top-up
     */
    public function approveTopup(PettyCashTopup $topup, ?float $amount = null): void
    {
        if (!$topup->isPending()) {
            throw new Exception('Top up sudah diproses');
        }

        $topup->update([
            'amount_approved' => $amount ?? $topup->amount_requested,
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Proses transfer top-up (dana sudah ditransfer)
     */
    public function processTopupTransfer(PettyCashTopup $topup, ?string $proof = null): void
    {
        if ($topup->status !== 'approved') {
            throw new Exception('Top up belum diapprove');
        }

        DB::transaction(function () use ($topup, $proof) {
            $fund = $topup->fund;
            $balanceAfter = $fund->current_balance + $topup->amount_approved;

            $topup->update([
                'balance_after' => $balanceAfter,
                'status' => 'transferred',
                'transferred_at' => now(),
                'transfer_proof' => $proof,
            ]);

            $fund->update(['current_balance' => $balanceAfter]);

            $this->createTopupJournal($topup);
        });
    }

    /**
     * Buat jurnal top-up: Debit Kas Kecil, Kredit Kas Besar
     */
    protected function createTopupJournal(PettyCashTopup $topup): void
    {
        $pcAcc = Account::where('code', self::COA_PETTY_CASH)->first();
        $kasAcc = Account::where('code', self::COA_KAS_BESAR)->first();

        if (!$pcAcc || !$kasAcc) {
            \Log::warning('COA top up tidak ditemukan');
            return;
        }

        $journalNumber = 'JU-PCT-' . now()->format('Ymd') . '-' . str_pad(
            Journal::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
        );

        $journal = Journal::create([
            'journal_number' => $journalNumber,
            'transaction_date' => now()->toDateString(),
            'description' => "[Top Up Kas Kecil] {$topup->topup_number}",
            'reference_no' => $topup->topup_number,
            'status' => 'posted',
            'created_by' => Auth::id(),
            'posted_at' => now(),
        ]);

        // Debit: Kas Kecil
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $pcAcc->id,
            'debit' => $topup->amount_approved,
            'credit' => 0,
        ]);

        // Kredit: Kas Besar
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $kasAcc->id,
            'debit' => 0,
            'credit' => $topup->amount_approved,
        ]);

        $topup->update(['journal_id' => $journal->id]);
    }

    /**
     * Reject top-up
     */
    public function rejectTopup(PettyCashTopup $topup, string $reason): void
    {
        $topup->update([
            'status' => 'rejected',
            'reject_reason' => $reason,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Update setting kas kecil dengan log
     */
    public function updateFundSettings(PettyCashFund $fund, array $data, ?string $reason = null): void
    {
        $changes = [];
        
        foreach (['plafon', 'min_balance_alert', 'max_transaction', 'holder_user_id', 'approver_user_id', 'name'] as $field) {
            if (isset($data[$field]) && $fund->$field != $data[$field]) {
                $changes[] = [
                    'field' => $field,
                    'old' => $fund->$field,
                    'new' => $data[$field],
                ];
            }
        }

        if (empty($changes)) return;

        DB::transaction(function () use ($fund, $data, $changes, $reason) {
            // Log semua perubahan
            foreach ($changes as $change) {
                PettyCashSettingLog::create([
                    'petty_cash_fund_id' => $fund->id,
                    'changed_by' => Auth::id(),
                    'field_changed' => $change['field'],
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                    'reason' => $reason,
                ]);
            }

            // Update fund
            $fund->update($data);
        });
    }

    /**
     * Summary untuk dashboard
     */
    public function getSummary(PettyCashFund $fund, string $period = 'month'): array
    {
        $query = $fund->transactions()->approved();

        if ($period === 'today') {
            $query->whereDate('transaction_date', today());
        } elseif ($period === 'week') {
            $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
        } else {
            $query->thisMonth();
        }

        $txns = $query->get();

        return [
            'total_transactions' => $txns->count(),
            'total_amount' => $txns->sum('amount'),
            'by_category' => $txns->groupBy('category')->map(fn($items) => [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ]),
            'current_balance' => $fund->current_balance,
            'usage_percentage' => $fund->usage_percentage,
            'needs_topup' => $fund->needsTopup(),
        ];
    }
}
