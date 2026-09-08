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

    /* ==================================================================
     | FASE 2 — MAKER / CHECKER
     |
     | Kasir menyimpan transaksi → tersimpan sebagai 'pending' TANPA jurnal
     | dan tanpa efek samping (invoice/job cost/vendor bill tidak disentuh).
     | Accounting memverifikasi → baru jurnal dibentuk lewat jalur yang sama
     | persis dengan processPayment(), jadi pemilihan akunnya tidak berubah.
     ================================================================== */

    /**
     * Fase 1 — cari transaksi yang kemungkinan duplikat.
     *
     * Kriteria: shipment, lawan transaksi, dan nominal sama dalam rentang hari
     * yang diatur di config/cashier.php. Ini yang selama ini baru ketahuan
     * belakangan lewat `reconcile:auto-jobcosts`; sekarang diangkat ke depan.
     *
     * Sengaja PERINGATAN, bukan larangan — pembayaran kembar yang sah memang
     * ada (mis. dua lift on di shipment yang sama, tarif sama).
     */
    public function findPossibleDuplicates(array $data, ?int $excludeId = null)
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            return collect();
        }

        $window = (int) config('cashier.duplicate_window_days', 7);
        $date = Carbon::parse($data['transaction_date'] ?? now());

        return CashTransaction::query()
            ->where('amount', $amount)
            ->whereBetween('transaction_date', [
                $date->copy()->subDays($window)->toDateString(),
                $date->copy()->addDays($window)->toDateString(),
            ])
            ->where('approval_status', '!=', CashTransaction::STATUS_REJECTED)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->when(
                ! empty($data['shipment_id']),
                fn ($q) => $q->where('shipment_id', $data['shipment_id']),
                // Tanpa shipment, kemiripan hanya berarti kalau lawan
                // transaksinya sama — kalau tidak, terlalu banyak false alarm.
                fn ($q) => $q->whereNull('shipment_id')
                    ->where(function ($q) use ($data) {
                        $q->where(fn ($q) => $q->whereNotNull('vendor_id')->where('vendor_id', $data['vendor_id'] ?? 0))
                          ->orWhere(fn ($q) => $q->whereNotNull('customer_id')->where('customer_id', $data['customer_id'] ?? 0));
                    })
            )
            ->with(['vendor', 'customer', 'creator'])
            ->orderByDesc('transaction_date')
            ->limit(5)
            ->get();
    }

    /**
     * Simpan input kasir sebagai transaksi menunggu verifikasi.
     */
    public function submitForApproval(array $data, $attachment = null): CashTransaction
    {
        return DB::transaction(function () use ($data, $attachment) {
            // Akun ditentukan sekarang juga supaya kasir tetap melihat preview
            // jurnal yang benar, dan supaya input yang tidak bisa dipetakan ke
            // akun gagal di depan — bukan nanti waktu diverifikasi.
            $accounts = $this->determineAccounts($data);

            $cashTransaction = CashTransaction::create([
                'transaction_date' => $data['transaction_date'] ?? now(),
                'type' => (stripos($data['type'] ?? $data['transaction_type'] ?? 'in', 'in') !== false) ? 'in' : 'out',
                'amount' => $data['amount'] ?? 0,
                'account_id' => $accounts['debit_account']->id,
                'counter_account_id' => $accounts['credit_account']->id,
                'invoice_id' => $data['invoice_id'] ?? null,
                'invoice_payment_id' => $data['invoice_payment_id'] ?? null,
                'shipment_id' => $data['shipment_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'job_cost_id' => $data['job_cost_id'] ?? null,
                'vendor_bill_id' => $data['vendor_bill_id'] ?? null,
                'description' => $data['description'] ?? '',
                'counterpart_type' => $data['counterpart_type'] ?? null,
                'cost_category' => $this->normalizeCostCategory($data),
                'expense_category' => $data['expense_category'] ?? null,
                'currency' => $data['currency'] ?? 'IDR',
                'exchange_rate' => $data['exchange_rate'] ?? 1,

                // Belum masuk buku
                'journal_id' => null,
                'is_posted' => false,
                'posted_at' => null,
                'approval_status' => CashTransaction::STATUS_PENDING,
                'submitted_at' => now(),

                'created_by' => $data['created_by'] ?? Auth::id() ?? 1,
            ]);

            if ($attachment) {
                $path = $attachment->store('cash-transactions', 'public');
                $cashTransaction->update(['proof_file' => $path]);
            }

            return $cashTransaction->refresh();
        });
    }

    /**
     * Verifikasi transaksi kasir: bentuk jurnal, posting, dan baru jalankan
     * efek samping ke invoice / job cost / vendor bill.
     */
    public function approveTransaction(CashTransaction $cashTransaction, int $approverId, ?string $note = null): CashTransaction
    {
        if ($cashTransaction->isApproved()) {
            throw new \Exception('Transaksi ini sudah diverifikasi sebelumnya.');
        }

        if ((int) $cashTransaction->created_by === $approverId) {
            throw new \Exception('Anda tidak bisa memverifikasi transaksi yang Anda input sendiri.');
        }

        // Fase 1 — tanpa bukti tidak bisa diverifikasi. Masih boleh sebagai
        // pengecualian, tapi alasannya wajib ditulis dan ikut tersimpan.
        if (config('cashier.require_proof_on_approval', true)
            && ! $cashTransaction->hasProof()
            && blank($note)
        ) {
            throw new \Exception('Transaksi ini belum ada bukti. Lampirkan bukti dulu, atau setujui sebagai pengecualian dengan menuliskan alasannya.');
        }

        return DB::transaction(function () use ($cashTransaction, $approverId, $note) {
            $data = $this->payloadFromTransaction($cashTransaction);
            $accounts = $this->determineAccounts($data);

            $journal = $this->createJournal($data, $accounts);

            $cashTransaction->update([
                'account_id' => $accounts['debit_account']->id,
                'counter_account_id' => $accounts['credit_account']->id,
                'journal_id' => $journal->id,
                'is_posted' => true,
                'posted_at' => now(),
                'approval_status' => CashTransaction::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'rejection_reason' => null,
                'approval_note' => $note,
            ]);

            // Efek samping sengaja ditahan sampai titik ini: selama pending,
            // invoice & job cost tidak boleh ikut berubah status.
            $this->updateRelatedRecords($cashTransaction->refresh(), $data);

            return $cashTransaction->refresh();
        });
    }

    /**
     * Tolak transaksi kasir dengan alasan. Tidak ada jurnal yang dibentuk,
     * datanya tetap tersimpan sebagai jejak.
     */
    public function rejectTransaction(CashTransaction $cashTransaction, int $approverId, string $reason): CashTransaction
    {
        if ($cashTransaction->isApproved()) {
            throw new \Exception('Transaksi sudah diverifikasi — koreksinya lewat jurnal balik, bukan ditolak.');
        }

        $cashTransaction->update([
            'approval_status' => CashTransaction::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $cashTransaction->refresh();
    }

    /**
     * Susun ulang payload dari baris transaksi yang tersimpan.
     *
     * cost_category yang disimpan sama persis dengan yang dikirim kasir
     * (normalizeCostCategory meloloskan nilai shipment/overhead/other apa
     * adanya), jadi determineAccounts() menghasilkan pasangan akun yang identik
     * dengan kalau transaksinya diposting langsung waktu disimpan.
     */
    protected function payloadFromTransaction(CashTransaction $cashTransaction): array
    {
        return [
            'transaction_date' => $cashTransaction->transaction_date,
            'type' => $cashTransaction->type,
            'transaction_type' => $cashTransaction->type,
            'amount' => $cashTransaction->amount,
            'cost_category' => $cashTransaction->cost_category,
            'category' => $cashTransaction->cost_category,
            'description' => $cashTransaction->description,
            'invoice_id' => $cashTransaction->invoice_id,
            'invoice_payment_id' => $cashTransaction->invoice_payment_id,
            'shipment_id' => $cashTransaction->shipment_id,
            'vendor_id' => $cashTransaction->vendor_id,
            'customer_id' => $cashTransaction->customer_id,
            'job_cost_id' => $cashTransaction->job_cost_id,
            'vendor_bill_id' => $cashTransaction->vendor_bill_id,
            'counterpart_type' => $cashTransaction->counterpart_type,
            'expense_category' => $cashTransaction->expense_category,
            'currency' => $cashTransaction->currency,
            'exchange_rate' => $cashTransaction->exchange_rate,
            'proof_file' => $cashTransaction->proof_file,
            'created_by' => $cashTransaction->created_by,
        ];
    }

    /**
     * Determine debit and credit accounts
     */
    protected function determineAccounts(array $data)
    {
        $type = $data['type'] ?? $data['transaction_type'] ?? 'in'; // Support both keys
        $category = $data['category'] ?? $data['cost_category'] ?? 'general'; // Support both keys
        
        // Bank account (from config)
        $bankAccount = Account::where('code', config('accounting.default_accounts.bank', '1103'))->first();
        
        if ($type === 'in') {
            // Cash In: Debit Bank, Credit other account
            switch ($category) {
                case 'payment_from_customer':
                    // Debit: Bank, Credit: Piutang
                    $creditAccount = Account::where('code', config('accounting.default_accounts.piutang', '1201'))->first(); // Piutang Usaha
                    break;
                    
                case 'down_payment':
                    // Debit: Bank, Credit: Uang Muka Customer
                    $creditAccount = Account::where('code', config('accounting.default_accounts.uang_muka', '2103'))->first() 
                                  ?? Account::where('code', config('accounting.default_accounts.piutang', '1201'))->first(); // Fallback
                    break;
                    
                default:
                    // Debit: Bank, Credit: Revenue
                    $creditAccount = Account::where('code', config('accounting.default_accounts.pendapatan', '4101'))->first(); // Pendapatan Jasa
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
                    $debitAccount = Account::where('code', config('accounting.default_accounts.biaya_ops', '5101'))->first() // Biaya Operasional
                                 ?? Account::where('code', '2101')->first(); // Hutang Usaha (keep 2101 as fallback)
                    break;
                    
                case 'operational_expense':
                    // Debit: Biaya Operasional, Credit: Bank
                    $debitAccount = Account::where('code', config('accounting.default_accounts.biaya_ops', '5101'))->first();
                    break;
                    
                default:
                    // Debit: Biaya Lain-lain, Credit: Bank
                    $debitAccount = Account::where('code', config('accounting.default_accounts.biaya_lain', '5199'))->first() // Biaya Lain-lain
                                 ?? Account::where('code', config('accounting.default_accounts.biaya_ops', '5101'))->first(); // Fallback
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
        // Generate journal number — sequence dicari dari nomor jurnal dengan
        // prefix tanggal transaksi yang sama, BUKAN dari created_at. (Dulu:
        // whereDate(created_at) + nomor dari transaction_date → jurnal
        // backdate bertabrakan nomor dengan jurnal lama.)
        $date = Carbon::parse($data['transaction_date'] ?? now());
        $prefix = 'JV-' . $date->format('Ymd') . '-';
        $lastNumber = Journal::where('journal_number', 'like', $prefix . '%')
            ->orderByDesc('journal_number')
            ->value('journal_number');
        $sequence = $lastNumber ? intval(substr($lastNumber, -4)) + 1 : 1;
        $journalNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        // Create Journal
        $journal = Journal::create([
            'journal_number' => $journalNumber,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'description' => $data['description'] ?? 'Cash Transaction',
            'created_by' => $data['created_by'] ?? Auth::id() ?? 1,
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

        $this->segarkanSaldo($accounts);

        return $journal;
    }

    /**
     * Hitung ulang accounts.current_balance untuk akun yang tersentuh.
     *
     * Dulu jalur kasir sama sekali tidak memperbarui kolom saldo, sementara
     * AccountingService & JournalEntry melakukannya — jadi tiap transaksi kasir
     * menambah selisih antara kolom saldo dan buku besar. Sengaja memakai
     * recalculateBalance() (hitung ulang dari journal_items), bukan
     * increment/decrement, supaya hasilnya benar berapa kali pun dijalankan.
     */
    protected function segarkanSaldo(array $accounts): void
    {
        collect($accounts)
            ->filter()
            ->unique(fn ($akun) => $akun->id)
            ->each(fn ($akun) => $akun->refresh()->recalculateBalance());
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
            'invoice_payment_id' => $data['invoice_payment_id'] ?? null,
            'shipment_id' => $data['shipment_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'job_cost_id' => $data['job_cost_id'] ?? null,
            'vendor_bill_id' => $data['vendor_bill_id'] ?? null,
            'description' => $data['description'] ?? '',
            'proof_file' => $data['proof_file'] ?? null,
            'counterpart_type' => $data['counterpart_type'] ?? null,
            'cost_category' => $this->normalizeCostCategory($data),
            'journal_id' => $journal->id,
            'is_posted' => true,
            'posted_at' => now(),
            // Jalur sistem (job costing, invoice, backfill) tetap langsung
            // terbukukan — antrian verifikasi hanya untuk input kasir manual.
            'approval_status' => CashTransaction::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
            // Kolom NOT NULL di prod; dari console Auth::id() null → fallback
            // user sistem #1 (konsisten dgn AccountingService).
            'created_by' => $data['created_by'] ?? Auth::id() ?? 1,
        ]);
    }

    /**
     * Kolom cash_transactions.cost_category = enum('shipment','overhead','other').
     * Nilai kategori akuntansi (payment_from_customer, payment_to_vendor, dll)
     * hanya dipakai untuk penentuan akun — di luar enum insert akan gagal
     * (SQLSTATE 01000 Data truncated) dan seluruh transaksi ikut rollback.
     */
    protected function normalizeCostCategory(array $data): ?string
    {
        $category = $data['cost_category'] ?? $data['category'] ?? null;

        if (in_array($category, ['shipment', 'overhead', 'other'], true)) {
            return $category;
        }

        $type = (stripos($data['type'] ?? $data['transaction_type'] ?? 'in', 'in') !== false) ? 'in' : 'out';
        if ($type === 'in') {
            return null; // uang masuk bukan biaya
        }

        if (!empty($data['shipment_id']) || !empty($data['job_cost_id'])) {
            return 'shipment';
        }

        return match ($category) {
            'operational_expense' => 'overhead',
            default => 'other',
        };
    }

    /**
     * Update related records (Invoice, Job Cost, VendorBill)
     */
    protected function updateRelatedRecords(CashTransaction $cashTransaction, array $data)
    {
        // Update Invoice status if payment is for invoice
        if ($cashTransaction->invoice_id) {
            $invoice = Invoice::find($cashTransaction->invoice_id);
            if ($invoice && $invoice->payments()->exists()) {
                // Ada record InvoicePayment → status mengikuti total pembayaran
                // riil (partial/paid), jangan dipaksa 'paid' saat baru cicilan
                // sebagian. Berlaku juga saat status masih 'partial' agar
                // pelunasan cicilan terakhir naik ke 'paid'.
                if (in_array($invoice->status, ['unpaid', 'partial'], true)) {
                    $invoice->recalculateTotalPaid();
                    if ($cashTransaction->proof_file && !$invoice->payment_proof) {
                        $invoice->update(['payment_proof' => $cashTransaction->proof_file]);
                    }
                }
            } elseif ($invoice && $invoice->status === 'unpaid') {
                // Cash transaction manual tanpa record payment (mis. dari
                // SimpleCashier) — pertahankan perilaku lama.
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
                // If job_cost_id is explicitly set (e.g. from JobCostingManager), skip auto-match
                if (!empty($data['job_cost_id'])) {
                    return;
                }

                // Step 1: try exact match (same shipment + amount + vendor)
                $existing = JobCost::where('shipment_id', $cashTransaction->shipment_id)
                    ->where('amount', $cashTransaction->amount)
                    ->where('status', 'unpaid')
                    ->when($cashTransaction->vendor_id, fn($q) =>
                        $q->where('vendor_id', $cashTransaction->vendor_id)
                    )
                    ->first();

                // Step 2: fallback — same shipment + amount, any vendor (catches vendor mismatch)
                if (!$existing) {
                    $existing = JobCost::where('shipment_id', $cashTransaction->shipment_id)
                        ->where('amount', $cashTransaction->amount)
                        ->where('status', 'unpaid')
                        ->whereNotExists(function ($q) {
                            // Exclude job costs that already have a linked cash transaction
                            $q->select(\DB::raw(1))
                              ->from('cash_transactions')
                              ->whereColumn('cash_transactions.job_cost_id', 'job_costs.id');
                        })
                        ->first();
                }

                if ($existing) {
                    // Update existing JobCost to paid instead of creating duplicate
                    $existing->update([
                        'status' => 'paid',
                        'date_paid' => $cashTransaction->transaction_date,
                    ]);
                    // Link the cash transaction back to this job cost
                    $cashTransaction->update(['job_cost_id' => $existing->id]);
                } else {
                    // No matching unpaid cost found — create new one as paid
                    JobCost::create([
                        'shipment_id' => $cashTransaction->shipment_id,
                        'description' => $data['description'] ?? 'Payment',
                        'amount' => $cashTransaction->amount,
                        'vendor_id' => $cashTransaction->vendor_id,
                        'status' => 'paid',
                        'date_paid' => $cashTransaction->transaction_date,
                        'created_by' => $data['created_by'] ?? Auth::id() ?? 1,
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

            // Fase 2: yang sudah diverifikasi sudah punya jurnal resmi —
            // koreksinya lewat jurnal balik, tidak boleh diedit diam-diam.
            if ($cashTransaction->isApproved()) {
                throw new \Exception('Transaksi sudah diverifikasi accounting dan tidak bisa diedit. Buat jurnal balik bila perlu koreksi.');
            }

            $accounts = $this->determineAccounts($data);

            // Transaksi yang masih menunggu verifikasi belum punya jurnal, dan
            // memang belum boleh punya. Cukup perbarui datanya saja.
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
                'invoice_id' => $data['invoice_id'] ?? null,
                'vendor_bill_id' => $data['vendor_bill_id'] ?? null,
                'cost_category' => $this->normalizeCostCategory($data),
                'expense_category' => $data['expense_category'] ?? null,
                'description' => $data['description'] ?? null,
                'journal_id' => $journal?->id,
                'is_posted' => (bool) $journal,
                'posted_at' => $journal ? ($cashTransaction->posted_at ?? now()) : null,
                // Revisi setelah ditolak mengembalikan transaksi ke antrian.
                'approval_status' => CashTransaction::STATUS_PENDING,
                'submitted_at' => now(),
                'rejection_reason' => null,
            ]);
            
            if ($attachment) {
                $path = $attachment->store('cash-transactions', 'public');
                $cashTransaction->update(['proof_file' => $path]);
            }
            
            $this->segarkanSaldo($accounts);

            DB::commit();
            return $cashTransaction;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
