<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use App\Services\CashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: updateRelatedRecords dulu memaksa invoice unpaid → paid
 * untuk SEMUA pembayaran, termasuk cicilan sebagian. Kini status mengikuti
 * total pembayaran riil bila invoice punya record InvoicePayment.
 */
class CashierInvoiceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);

        foreach ([
            ['code' => '1103', 'name' => 'Bank Mandiri', 'type' => 'kas_bank'],
            ['code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'piutang'],
            ['code' => '4101', 'name' => 'Pendapatan Jasa', 'type' => 'pendapatan'],
        ] as $account) {
            Account::create($account + ['opening_balance' => 0, 'current_balance' => 0]);
        }
    }

    protected function makeInvoice(float $grandTotal): Invoice
    {
        $customer = Customer::create([
            'user_id' => $this->admin->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Uji Cicilan',
        ]);

        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV/TEST/' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'status' => 'unpaid',
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    public function test_cicilan_sebagian_membuat_status_partial_bukan_paid(): void
    {
        $invoice = $this->makeInvoice(10_000_000);

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 4_000_000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        app(CashierService::class)->processPayment([
            'type' => 'in',
            'category' => 'payment_from_customer',
            'amount' => 4_000_000,
            'transaction_date' => now()->toDateString(),
            'invoice_id' => $invoice->id,
            'invoice_payment_id' => $payment->id,
            'customer_id' => $invoice->customer_id,
            'description' => 'Cicilan pertama',
        ]);

        $this->assertSame('partial', $invoice->fresh()->status);
    }

    public function test_pelunasan_penuh_membuat_status_paid(): void
    {
        $invoice = $this->makeInvoice(5_000_000);

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 5_000_000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        app(CashierService::class)->processPayment([
            'type' => 'in',
            'category' => 'payment_from_customer',
            'amount' => 5_000_000,
            'transaction_date' => now()->toDateString(),
            'invoice_id' => $invoice->id,
            'invoice_payment_id' => $payment->id,
            'customer_id' => $invoice->customer_id,
            'description' => 'Pelunasan penuh',
        ]);

        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_dua_cicilan_di_invoice_sama_masing_masing_punya_cash_transaction(): void
    {
        // Regression: index server-local uniq_cash_invoice (UNIQUE invoice_id)
        // dulu memblokir cash transaction untuk cicilan ke-2.
        $invoice = $this->makeInvoice(10_000_000);
        $cashier = app(CashierService::class);

        foreach ([4_000_000, 6_000_000] as $amount) {
            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'transfer',
                'recorded_by' => $this->admin->id,
            ]);

            $cashier->processPayment([
                'type' => 'in',
                'category' => 'payment_from_customer',
                'amount' => $amount,
                'transaction_date' => now()->toDateString(),
                'invoice_id' => $invoice->id,
                'invoice_payment_id' => $payment->id,
                'customer_id' => $invoice->customer_id,
                'description' => 'Cicilan',
            ]);
        }

        $this->assertSame(
            2,
            \App\Models\CashTransaction::where('invoice_id', $invoice->id)->count(),
            'Kedua cicilan harus punya cash transaction masing-masing'
        );
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_cash_transaction_manual_tanpa_payment_record_tetap_menandai_paid(): void
    {
        $invoice = $this->makeInvoice(1_000_000);

        app(CashierService::class)->processPayment([
            'type' => 'in',
            'category' => 'payment_from_customer',
            'amount' => 1_000_000,
            'transaction_date' => now()->toDateString(),
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'description' => 'Entry manual kasir',
        ]);

        $this->assertSame('paid', $invoice->fresh()->status);
    }
}
