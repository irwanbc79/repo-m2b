<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mode --link-existing pada cashier:backfill-payments: payment yang
 * invoice-nya sudah dijurnal PAY-* mendapat CashTransaction TANPA jurnal
 * baru (link ke jurnal eksisting); nominal jurnal ≠ payment → di-skip.
 */
class BackfillLinkExistingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Account $bank;
    protected Account $piutang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);

        $this->bank = Account::create(['code' => '1103', 'name' => 'Bank Mandiri', 'type' => 'kas_bank', 'opening_balance' => 0, 'current_balance' => 0]);
        $this->piutang = Account::create(['code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'piutang', 'opening_balance' => 0, 'current_balance' => 0]);
    }

    protected function makePaidInvoiceWithPayJournal(float $payAmount, float $journalAmount): InvoicePayment
    {
        $customer = Customer::create([
            'user_id' => $this->admin->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Link Existing',
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV/LINK/' . uniqid(),
            'invoice_date' => '2026-01-10',
            'status' => 'paid',
            'subtotal' => $payAmount,
            'grand_total' => $payAmount,
            'total_paid' => $payAmount,
        ]);

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $payAmount,
            'payment_date' => '2026-01-10',
            'payment_method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        $journal = Journal::create([
            'journal_number' => 'JRN-TEST-' . uniqid(),
            'transaction_date' => '2026-01-10',
            'description' => 'Pembayaran ' . $invoice->invoice_number,
            'reference_no' => 'PAY-' . $invoice->id,
            'status' => 'posted',
            'created_by' => $this->admin->id,
        ]);
        $journal->items()->create(['account_id' => $this->bank->id, 'debit' => $journalAmount, 'credit' => 0]);
        $journal->items()->create(['account_id' => $this->piutang->id, 'debit' => 0, 'credit' => $journalAmount]);

        return $payment;
    }

    public function test_link_existing_membuat_cash_tx_tanpa_jurnal_baru(): void
    {
        $payment = $this->makePaidInvoiceWithPayJournal(1_000_000, 1_000_000);
        $journalCountBefore = Journal::count();

        $this->artisan('cashier:backfill-payments --link-existing --yes')
            ->assertExitCode(0);

        $tx = CashTransaction::where('invoice_payment_id', $payment->id)->first();
        $this->assertNotNull($tx, 'CashTransaction harus dibuat');
        $this->assertSame(Journal::where('reference_no', 'PAY-' . $payment->invoice_id)->first()->id, $tx->journal_id);
        $this->assertSame($this->bank->id, $tx->account_id);
        $this->assertSame($this->piutang->id, $tx->counter_account_id);
        $this->assertNull($tx->cost_category);
        $this->assertSame($this->admin->id, $tx->created_by, 'created_by harus terisi (NOT NULL di prod)');
        $this->assertSame($journalCountBefore, Journal::count(), 'TIDAK boleh ada jurnal baru');
    }

    public function test_nominal_jurnal_beda_di_skip(): void
    {
        // Replikasi anomali payment #5: bayar 2.977.680, jurnal hanya 1.250.000
        $payment = $this->makePaidInvoiceWithPayJournal(2_977_680, 1_250_000);

        $this->artisan('cashier:backfill-payments --link-existing --yes')
            ->assertExitCode(0);

        $this->assertNull(
            CashTransaction::where('invoice_payment_id', $payment->id)->first(),
            'Payment dengan nominal jurnal beda TIDAK boleh di-link'
        );
    }

    public function test_link_existing_idempotent(): void
    {
        $payment = $this->makePaidInvoiceWithPayJournal(500_000, 500_000);

        $this->artisan('cashier:backfill-payments --link-existing --yes')->assertExitCode(0);
        $this->artisan('cashier:backfill-payments --link-existing --yes')->assertExitCode(0);

        $this->assertSame(1, CashTransaction::where('invoice_payment_id', $payment->id)->count());
    }
}
