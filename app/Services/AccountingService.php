<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountingService
{
    /**
     * Generate journal number
     */
    public static function generateJournalNumber(): string
    {
        $prefix = 'JRN-' . date('Ym') . '-';
        $lastJournal = Journal::where('journal_number', 'like', $prefix . '%')
            ->orderBy('journal_number', 'desc')
            ->first();
        
        if ($lastJournal) {
            $lastNumber = intval(substr($lastJournal->journal_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create journal from Invoice (when invoice is created)
     * Debit: Piutang Usaha
     * Credit: Pendapatan Jasa
     */
    public static function createJournalFromInvoice(Invoice $invoice): ?Journal
    {
        // Skip if journal already exists for this invoice
        $existingJournal = Journal::where('reference_no', 'INV-' . $invoice->id)->first();
        if ($existingJournal) {
            return $existingJournal;
        }

        // Get accounts
        $piutangAccount = Account::where('code', '1201')->first(); // Piutang Usaha
        $pendapatanAccount = Account::where('code', '4101')->first(); // Pendapatan Jasa Clearance
        
        if (!$piutangAccount || !$pendapatanAccount) {
            \Log::warning('Auto Journal: Account not found for invoice ' . $invoice->invoice_number);
            return null;
        }

        $amount = $invoice->grand_total;
        
        if ($amount <= 0) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Create journal header
            $journal = Journal::create([
                'journal_number' => self::generateJournalNumber(),
                'transaction_date' => $invoice->invoice_date ?? now(),
                'description' => 'Invoice ' . $invoice->invoice_number . ' - ' . ($invoice->customer->company_name ?? 'Customer'),
                'reference_no' => 'INV-' . $invoice->id,
                'status' => 'posted',
                'created_by' => Auth::id() ?? 1,
                'posted_at' => now(),
            ]);

            // Debit: Piutang Usaha
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $piutangAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'note' => 'Piutang dari ' . $invoice->invoice_number,
            ]);

            // Credit: Pendapatan Jasa
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $pendapatanAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'note' => 'Pendapatan dari ' . $invoice->invoice_number,
            ]);

            // Update account balances
            $piutangAccount->increment('current_balance', $amount);
            $pendapatanAccount->increment('current_balance', $amount);

            DB::commit();

            \Log::info('Auto Journal created for Invoice: ' . $invoice->invoice_number . ' | Journal: ' . $journal->journal_number);
            
            return $journal;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Auto Journal failed for Invoice: ' . $invoice->invoice_number . ' | Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create journal from Payment (when invoice is marked as paid)
     * Debit: Kas/Bank
     * Credit: Piutang Usaha
     */
    public static function createJournalFromPayment(Invoice $invoice, string $bankAccountCode = '1103'): ?Journal
    {
        // Skip if payment journal already exists
        $existingJournal = Journal::where('reference_no', 'PAY-' . $invoice->id)->first();
        if ($existingJournal) {
            return $existingJournal;
        }

        // Get accounts
        $kasBank = Account::where('code', $bankAccountCode)->first(); // Default: Bank Mandiri
        $piutangAccount = Account::where('code', '1201')->first(); // Piutang Usaha
        
        if (!$kasBank || !$piutangAccount) {
            \Log::warning('Auto Journal Payment: Account not found for invoice ' . $invoice->invoice_number);
            return null;
        }

        $amount = $invoice->grand_total;
        
        if ($amount <= 0) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Create journal header
            $journal = Journal::create([
                'journal_number' => self::generateJournalNumber(),
                'transaction_date' => $invoice->payment_date ?? now(),
                'description' => 'Pembayaran ' . $invoice->invoice_number . ' - ' . ($invoice->customer->company_name ?? 'Customer'),
                'reference_no' => 'PAY-' . $invoice->id,
                'status' => 'posted',
                'created_by' => Auth::id() ?? 1,
                'posted_at' => now(),
            ]);

            // Debit: Kas/Bank
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $kasBank->id,
                'debit' => $amount,
                'credit' => 0,
                'note' => 'Terima pembayaran ' . $invoice->invoice_number,
            ]);

            // Credit: Piutang Usaha
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $piutangAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'note' => 'Pelunasan piutang ' . $invoice->invoice_number,
            ]);

            // Update account balances
            $kasBank->increment('current_balance', $amount);
            $piutangAccount->decrement('current_balance', $amount);

            DB::commit();

            \Log::info('Auto Journal Payment created for Invoice: ' . $invoice->invoice_number . ' | Journal: ' . $journal->journal_number);
            
            return $journal;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Auto Journal Payment failed for Invoice: ' . $invoice->invoice_number . ' | Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Reverse journal (for invoice cancellation)
     */
    public static function reverseJournal(string $referenceNo): ?Journal
    {
        $originalJournal = Journal::where('reference_no', $referenceNo)->first();
        
        if (!$originalJournal) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Create reversal journal
            $reversalJournal = Journal::create([
                'journal_number' => self::generateJournalNumber(),
                'transaction_date' => now(),
                'description' => 'REVERSAL: ' . $originalJournal->description,
                'reference_no' => 'REV-' . $referenceNo,
                'status' => 'posted',
                'created_by' => Auth::id() ?? 1,
                'posted_at' => now(),
            ]);

            // Reverse all items (swap debit/credit)
            foreach ($originalJournal->items as $item) {
                JournalItem::create([
                    'journal_id' => $reversalJournal->id,
                    'account_id' => $item->account_id,
                    'debit' => $item->credit, // Swap
                    'credit' => $item->debit, // Swap
                    'note' => 'Reversal: ' . $item->note,
                ]);

                // Update account balance
                $account = Account::find($item->account_id);
                if ($account) {
                    $account->decrement('current_balance', $item->debit);
                    $account->increment('current_balance', $item->credit);
                }
            }

            DB::commit();

            return $reversalJournal;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Journal reversal failed: ' . $e->getMessage());
            return null;
        }
    }
}
