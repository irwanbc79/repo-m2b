<?php

namespace App\Services\Business;

use App\Models\{
    Invoice,
    Journal,
    Account,
    JobCost,
    CashTransaction
};
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingWorkflowService
{
    /**
     * =====================================================
     * PROFORMA INVOICE PAID → DP
     * =====================================================
     */
    public function handleProformaPaid(Invoice $invoice): void
    {
        if ($invoice->type !== 'proforma') {
            return;
        }

        DB::transaction(function () use ($invoice) {

            $cash = Account::cashOrBank();
            $dp   = Account::advanceFromCustomer();

            $journal = Journal::create([
                'journal_number'    => Journal::generateNumber(),
                'transaction_date' => $invoice->payment_date,
                'description'      => 'DP Proforma '.$invoice->invoice_number,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            $journal->items()->createMany([
                [
                    'account_id' => $cash->id,
                    'debit'      => $invoice->grand_total,
                    'credit'     => 0,
                ],
                [
                    'account_id' => $dp->id,
                    'debit'      => 0,
                    'credit'     => $invoice->grand_total,
                ],
            ]);

            $this->recalculateBalances($journal);
        });
    }

    /**
     * =====================================================
     * COMMERCIAL INVOICE PAID → REVENUE
     * =====================================================
     */
    public function handleCommercialPaid(Invoice $invoice): void
    {
        if ($invoice->type !== 'commercial') {
            return;
        }

        DB::transaction(function () use ($invoice) {

            $cash    = Account::cashOrBank();
            $dp      = Account::advanceFromCustomer();
            $revenue = Account::revenueService();

            $dpUsed   = $invoice->down_payment ?? 0;
            $cashPaid = $invoice->grand_total - $dpUsed;

            $journal = Journal::create([
                'journal_number'    => Journal::generateNumber(),
                'transaction_date' => $invoice->payment_date,
                'description'      => 'Revenue Invoice '.$invoice->invoice_number,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            $items = [];

            if ($cashPaid > 0) {
                $items[] = [
                    'account_id' => $cash->id,
                    'debit'      => $cashPaid,
                    'credit'     => 0,
                ];
            }

            if ($dpUsed > 0) {
                $items[] = [
                    'account_id' => $dp->id,
                    'debit'      => $dpUsed,
                    'credit'     => 0,
                ];
            }

            $items[] = [
                'account_id' => $revenue->id,
                'debit'      => 0,
                'credit'     => $invoice->grand_total,
            ];

            $journal->items()->createMany($items);

            $this->recalculateBalances($journal);
        });
    }

    /**
     * =====================================================
     * JOB COST PAID → EXPENSE
     * =====================================================
     */
    public function handleJobCostPaid(JobCost $cost): void
    {
        DB::transaction(function () use ($cost) {

            $cash    = Account::cashOrBank();
            $expense = Account::findOrFail($cost->coa_id);

            $journal = Journal::create([
                'journal_number'    => Journal::generateNumber(),
                'transaction_date' => $cost->date_paid,
                'description'      => 'Job Cost '.$cost->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            $journal->items()->createMany([
                [
                    'account_id' => $expense->id,
                    'debit'      => $cost->amount,
                    'credit'     => 0,
                ],
                [
                    'account_id' => $cash->id,
                    'debit'      => 0,
                    'credit'     => $cost->amount,
                ],
            ]);

            $this->recalculateBalances($journal);
        });
    }

    /**
     * =====================================================
     * CASHIER → INVOICE PAYMENT (ANTI DOUBLE PAYMENT)
     * =====================================================
     * Dipanggil oleh Cashier (bukan Finance UI)
     */
    public function recordInvoicePayment(array $data): CashTransaction
    {
        return DB::transaction(function () use ($data) {

            // 1. ANTI DOUBLE PAYMENT (LOGIC UTAMA)
            if (!empty($data['invoice_id'])) {
                $exists = CashTransaction::where('invoice_id', $data['invoice_id'])->exists();
                if ($exists) {
                    throw new Exception('Invoice ini sudah pernah dibayar dan tercatat di kas.');
                }
            }

            // 2. SIMPAN CASH TRANSACTION
            $cash = CashTransaction::create([
                'transaction_date'   => $data['transaction_date'],
                'type'               => 'in',
                'amount'             => $data['amount'],
                'account_id'         => $data['account_id'],          // Kas / Bank
                'counter_account_id' => $data['counter_account_id'],  // Piutang / Revenue
                'invoice_id'         => $data['invoice_id'] ?? null,
                'description'        => $data['description'] ?? 'Pelunasan Invoice',
                'proof_file'         => $data['proof_file'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            // 3. AUTO JOURNAL DARI CASH
            $journal = $this->createJournalFromCash($cash);

            $cash->update([
                'journal_id' => $journal->id
            ]);

            return $cash;
        });
    }
    
    /**
 * =====================================================
 * CASHIER → CASH OUT (EXPENSE / JOB COST)
 * =====================================================
 * Dipanggil oleh CashierManager
 */
public function recordCashOut(array $data): CashTransaction
{
    return DB::transaction(function () use ($data) {

        // 1. VALIDASI MINIMAL (SAFETY NET)
        if (empty($data['account_id']) || empty($data['counter_account_id'])) {
            throw new \Exception('Akun kas dan akun beban wajib diisi.');
        }

        if ($data['amount'] <= 0) {
            throw new \Exception('Nominal pengeluaran tidak valid.');
        }

        // 2. SIMPAN CASH TRANSACTION (UANG KELUAR)
        $cash = CashTransaction::create([
            'transaction_date'   => $data['transaction_date'],
            'type'               => 'out',
            'amount'             => $data['amount'],
            'account_id'         => $data['account_id'],          // Kas / Bank
            'counter_account_id' => $data['counter_account_id'],  // Beban / Aset
            'shipment_id'        => $data['shipment_id'] ?? null,
            'vendor_id'          => $data['vendor_id'] ?? null,
            'description'        => $data['description'] ?? 'Pengeluaran kas',
            'proof_file'         => $data['proof_file'] ?? null,
            'created_by'         => auth()->id(),
        ]);

        // 3. AUTO JOURNAL (DEBIT EXPENSE / CREDIT CASH)
        $journal = $this->createJournalFromCash($cash);

        $cash->update([
            'journal_id' => $journal->id,
        ]);

        // 4. AUTO JOB COST (JIKA TERKAIT SHIPMENT)
        if (!empty($data['shipment_id'])) {

            JobCost::create([
                'shipment_id' => $data['shipment_id'],
                'vendor_id'   => $data['vendor_id'] ?? null,
                'coa_id'      => $data['counter_account_id'], // akun beban
                'amount'      => $data['amount'],
                'description' => $data['description'] ?? 'Biaya operasional shipment',
                'status'      => 'paid',
                'date_paid'   => $data['transaction_date'],
                'reference'   => 'CASH-'.$cash->id,
            ]);
        }

        return $cash;
    });
}


    /**
     * =====================================================
     * AUTO JOURNAL DARI CASH TRANSACTION
     * =====================================================
     */
    protected function createJournalFromCash(CashTransaction $cash): Journal
    {
        $journal = Journal::create([
            'journal_number'    => Journal::generateNumber(),
            'transaction_date' => $cash->transaction_date,
            'description'      => $cash->description,
            'status'           => 'posted',
            'created_by'       => $cash->created_by,
        ]);

        if ($cash->type === 'in') {
            // CASH IN
            $journal->items()->createMany([
                [
                    'account_id' => $cash->account_id,
                    'debit'      => $cash->amount,
                    'credit'     => 0,
                ],
                [
                    'account_id' => $cash->counter_account_id,
                    'debit'      => 0,
                    'credit'     => $cash->amount,
                ],
            ]);
        } else {
            // CASH OUT
            $journal->items()->createMany([
                [
                    'account_id' => $cash->counter_account_id,
                    'debit'      => $cash->amount,
                    'credit'     => 0,
                ],
                [
                    'account_id' => $cash->account_id,
                    'debit'      => 0,
                    'credit'     => $cash->amount,
                ],
            ]);
        }

        $this->recalculateBalances($journal);

        return $journal;
    }

    /**
     * =====================================================
     * UPDATE SALDO (SATU-SATUNYA TEMPAT)
     * =====================================================
     */
    protected function recalculateBalances(Journal $journal): void
    {
        foreach ($journal->items as $item) {
            $account = $item->account;
            $account->current_balance += ($item->debit - $item->credit);
            $account->save();
        }
    }
}
