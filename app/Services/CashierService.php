<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\JobCost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashierService
{
    /**
     * Process payment transaction from Simple Cashier
     * 
     * @param array $data
     * @return CashTransaction
     */
    public function processPayment(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Determine accounts based on transaction type
            $accounts = $this->determineAccounts($data);
            
            // 2. Create Journal Entry
            $journal = $this->createJournal($data, $accounts);
            
            // 3. Create Cash Transaction
            $cashTransaction = $this->createCashTransaction($data, $accounts, $journal);
            
            // 4. Update related records
            $this->updateRelatedRecords($cashTransaction, $data);
            
            return $cashTransaction;
        });
    }

    /**
     * Determine debit and credit accounts
     */
    protected function determineAccounts(array $data)
    {
        $type = $data['type'] ?? $data['transaction_type'] ?? 'in'; // Support both keys
        $category = $data['category'] ?? $data['cost_category'] ?? 'general'; // Support both keys
        
        // Bank account (always 1103 - Bank Mandiri for now)
        $bankAccount = Account::where('code', '1103')->first();
        
        if ($type === 'in') {
            // Cash In: Debit Bank, Credit other account
            switch ($category) {
                case 'payment_from_customer':
                    // Debit: Bank, Credit: Piutang
                    $creditAccount = Account::where('code', '1201')->first(); // Piutang Usaha
                    break;
                    
                case 'down_payment':
                    // Debit: Bank, Credit: Uang Muka Customer
                    $creditAccount = Account::where('code', '2103')->first() 
                                  ?? Account::where('code', '1201')->first(); // Fallback
                    break;
                    
                default:
                    // Debit: Bank, Credit: Revenue
                    $creditAccount = Account::where('code', '4101')->first(); // Pendapatan Jasa
            }
            
            return [
                'debit_account' => $bankAccount,
                'credit_account' => $creditAccount,
            ];
            
        } else {
            // Cash Out: Debit expense/payable, Credit Bank
            switch ($category) {
                case 'payment_to_vendor':
                    // Debit: Biaya or Hutang Vendor, Credit: Bank
                    $debitAccount = Account::where('code', '5101')->first() // Biaya Operasional
                                 ?? Account::where('code', '2101')->first(); // Hutang Usaha
                    break;
                    
                case 'operational_expense':
                    // Debit: Biaya Operasional, Credit: Bank
                    $debitAccount = Account::where('code', '5101')->first();
                    break;
                    
                default:
                    // Debit: Biaya Lain-lain, Credit: Bank
                    $debitAccount = Account::where('code', '5199')->first() // Biaya Lain-lain
                                 ?? Account::where('code', '5101')->first(); // Fallback
            }
            
            return [
                'debit_account' => $debitAccount,
                'credit_account' => $bankAccount,
            ];
        }
    }

    /**
     * Create Journal Entry
     */
    protected function createJournal(array $data, array $accounts)
    {
        // Generate journal number
        $date = Carbon::parse($data['transaction_date'] ?? now());
        $lastJournal = Journal::whereDate('created_at', $date)->orderBy('created_at', 'desc')->first();
        $sequence = $lastJournal ? intval(substr($lastJournal->journal_number, -4)) + 1 : 1;
        $journalNumber = 'JV-' . $date->format('Ymd') . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        // Create Journal
        $journal = Journal::create([
            'journal_number' => $journalNumber,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'description' => $data['description'] ?? 'Cash Transaction',
            'created_by' => Auth::id(),
        ]);
        
        // Create Journal Entries (Debit & Credit)
        $journal->items()->create([
            'account_id' => $accounts['debit_account']->id,
            'debit' => $data['amount'] ?? 0,
            'credit' => 0,
        ]);
        
        $journal->items()->create([
            'account_id' => $accounts['credit_account']->id,
            'debit' => 0,
            'credit' => $data['amount'] ?? 0,
        ]);
        
        
        // Auto-post journal
        $journal->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);
        return $journal;
    }

    /**
     * Create Cash Transaction record
     */
    protected function createCashTransaction(array $data, array $accounts, Journal $journal)
    {
        return CashTransaction::create([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'type' => (stripos($data['type'] ?? $data['transaction_type'] ?? 'in', 'in') !== false) ? 'in' : 'out',
            'amount' => $data['amount'] ?? 0,
            'account_id' => $accounts['debit_account']->id,
            'counter_account_id' => $accounts['credit_account']->id,
            'invoice_id' => $data['invoice_id'] ?? null,
            'shipment_id' => $data['shipment_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'description' => $data['description'] ?? '',
            'proof_file' => $data['proof_file'] ?? null,
            'journal_id' => $journal->id,
            'is_posted' => true,
            'posted_at' => now(),
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update related records (Invoice, Job Cost, VendorBill)
     */
    protected function updateRelatedRecords(CashTransaction $cashTransaction, array $data)
    {
        // Update Invoice status if payment is for invoice
        if ($cashTransaction->invoice_id) {
            $invoice = Invoice::find($cashTransaction->invoice_id);
            if ($invoice && $invoice->status === 'unpaid') {
                $invoice->update([
                    'status' => 'paid',
                    'payment_date' => $cashTransaction->transaction_date,
                    'payment_proof' => $cashTransaction->proof_file,
                ]);
            }
        }

        // ===== AUTO-RECONCILIATION: Update VendorBill if linked =====
        $vendorBillId = $data['vendor_bill_id'] ?? $cashTransaction->vendor_bill_id ?? null;
        if ($vendorBillId) {
            $vendorBill = \App\Models\VendorBill::find($vendorBillId);
            if ($vendorBill && $vendorBill->status !== 'paid') {
                $oldStatus = $vendorBill->status;
                $vendorBill->recordPayment(
                    $cashTransaction->amount,
                    $cashTransaction->transaction_date
                );

                // Audit trail for VendorBill payment
                \App\Models\ActivityLog::record(
                    'VendorBill',
                    'PAYMENT',
                    'VB-' . $vendorBill->bill_number,
                    'Pembayaran Rp ' . number_format($cashTransaction->amount, 0, ',', '.') . ' via Cashier | Vendor: ' . ($vendorBill->vendor->name ?? 'N/A'),
                    ['status' => $oldStatus, 'paid_amount' => $vendorBill->paid_amount - $cashTransaction->amount],
                    ['status' => $vendorBill->status, 'paid_amount' => $vendorBill->paid_amount]
                );

                // Also mark matching JobCost as paid if found
                $matchingJobCost = JobCost::where('shipment_id', $vendorBill->shipment_id)
                    ->where('vendor_id', $vendorBill->vendor_id)
                    ->where('status', 'unpaid')
                    ->where('amount', $vendorBill->amount)
                    ->first();

                if ($matchingJobCost) {
                    $matchingJobCost->update([
                        'status' => 'paid',
                        'date_paid' => $cashTransaction->transaction_date,
                    ]);
                    // Don't create a new JobCost since we updated the existing one
                    return;
                }
            }
        }
        
        // Create/Update Job Cost if shipment related (only if not already handled above)
        if ($cashTransaction->shipment_id) {
            $type = $data['type'] ?? $data['transaction_type'] ?? 'in';
            
            if ($type === 'out') {
                // Check if a matching unpaid JobCost already exists (avoid duplicates)
                $existing = JobCost::where('shipment_id', $cashTransaction->shipment_id)
                    ->where('amount', $cashTransaction->amount)
                    ->where('status', 'unpaid')
                    ->when($cashTransaction->vendor_id, function($q) use ($cashTransaction) {
                        $q->where('vendor_id', $cashTransaction->vendor_id);
                    })
                    ->first();

                if ($existing) {
                    // Update existing JobCost to paid instead of creating duplicate
                    $existing->update([
                        'status' => 'paid',
                        'date_paid' => $cashTransaction->transaction_date,
                    ]);
                } else {
                    // No matching unpaid cost found, create new one as paid
                    JobCost::create([
                        'shipment_id' => $cashTransaction->shipment_id,
                        'description' => $data['description'] ?? 'Payment',
                        'amount' => $cashTransaction->amount,
                        'vendor_id' => $cashTransaction->vendor_id,
                        'status' => 'paid',
                        'date_paid' => $cashTransaction->transaction_date,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        }
    }

    /**
     * Get account pairing preview for UI
     */
    public function getAccountPairingPreview(array $data)
    {
        $accounts = $this->determineAccounts($data);
        
        $amount = $data['amount'] ?? 0;
        $date = Carbon::parse($data['transaction_date'] ?? now());
        $journalNumber = 'JV-' . $date->format('Ymd') . '-PREVIEW';
        
        return [
            'journal_number' => $journalNumber,
            'entries' => [
                [
                    'account_code' => $accounts['debit_account']->code,
                    'account_name' => $accounts['debit_account']->name,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => $accounts['credit_account']->code,
                    'account_name' => $accounts['credit_account']->name,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
            'debit_account' => [
                'id' => $accounts['debit_account']->id,
                'code' => $accounts['debit_account']->code,
                'name' => $accounts['debit_account']->name,
                'amount' => $amount,
            ],
            'credit_account' => [
                'id' => $accounts['credit_account']->id,
                'code' => $accounts['credit_account']->code,
                'name' => $accounts['credit_account']->name,
                'amount' => $amount,
            ],
            'explanation' => $this->getExplanation($data['type'] ?? 'in', $data['category'] ?? 'general'),
            'total' => [
                'debit' => $amount,
                'credit' => $amount,
            ],
        ];
    }

    /**
     * Get human-readable explanation
     */
    protected function getExplanation($type, $category)
    {
        if ($type === 'in') {
            switch ($category) {
                case 'payment_from_customer':
                    return 'Customer membayar invoice, uang masuk ke bank, piutang berkurang';
                case 'down_payment':
                    return 'Uang muka dari customer, masuk ke bank';
                default:
                    return 'Pendapatan masuk ke bank';
            }
        } else {
            switch ($category) {
                case 'payment_to_vendor':
                    return 'Bayar vendor/supplier, uang keluar dari bank';
                case 'operational_expense':
                    return 'Biaya operasional, uang keluar dari bank';
                default:
                    return 'Pengeluaran lain-lain dari bank';
            }
        }
    }

    /**
     * Calculate customer outstanding (for preview)
     */
    public function getCustomerOutstanding($customerId)
    {
        $unpaidInvoices = Invoice::where('customer_id', $customerId)
            ->where('status', 'unpaid')
            ->sum('grand_total');
            
        return $unpaidInvoices;
    }

    /**
     * Get shipment profitability (for preview)
     */
    public function getShipmentProfitability($shipmentId)
    {
        $shipment = Shipment::find($shipmentId);
        
        if (!$shipment) {
            return null;
        }
        
        // Calculate total costs
        $totalCosts = JobCost::where('shipment_id', $shipmentId)->sum('amount');
        
        // Calculate revenue (from invoices)
        $totalRevenue = Invoice::where('shipment_id', $shipmentId)->sum('grand_total');
        
        $profit = $totalRevenue - $totalCosts;
        $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;
        
        return [
            'revenue' => $totalRevenue,
            'costs' => $totalCosts,
            'profit' => $profit,
            'margin_percent' => round($profitMargin, 2),
        ];
    }

    /**
     * Update existing cash transaction
     */
    public function updateTransaction($id, $data, $attachment = null)
    {
        DB::beginTransaction();
        
        try {
            $cashTransaction = CashTransaction::with(['journal'])->findOrFail($id);
            
            if (!in_array(($cashTransaction->journal->status ?? 'draft'), ['draft', 'pending', 'posted'])) {
                throw new \Exception('Hanya transaksi Draft yang dapat diupdate!');
            }
            
            $accounts = $this->determineAccounts($data);
            
            // Update journal yang existing (bukan delete & recreate)
            $journal = $cashTransaction->journal;
            if ($journal) {
                $journal->update([
                    'transaction_date' => $data['transaction_date'] ?? now(),
                    'description' => $data['description'] ?? 'Cash Transaction',
                ]);
                
                // Hapus journal entries lama dan buat baru
                $journal->items()->delete();
                
                $journal->items()->create([
                    'account_id' => $accounts['debit_account']->id,
                    'debit' => $data['amount'] ?? 0,
                    'credit' => 0,
                ]);
                
                $journal->items()->create([
                    'account_id' => $accounts['credit_account']->id,
                    'debit' => 0,
                    'credit' => $data['amount'] ?? 0,
                ]);
            } else {
                // Jika tidak ada journal (edge case), buat baru
                $journal = $this->createJournal($data, $accounts);
            }
            
            $cashTransaction->update([
                'transaction_date' => $data['transaction_date'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'account_id' => $accounts['debit_account']->id,
                'counter_account_id' => $accounts['credit_account']->id,
                'customer_id' => $data['customer_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'shipment_id' => $data['shipment_id'] ?? null,
                'description' => $data['description'] ?? null,
                'journal_id' => $journal->id,
                'is_posted' => true,
                'posted_at' => $cashTransaction->posted_at ?? now(),
            ]);
            
            if ($attachment) {
                $path = $attachment->store('cash-transactions', 'public');
                $cashTransaction->update(['proof_file' => $path]);
            }
            
            DB::commit();
            return $cashTransaction;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
