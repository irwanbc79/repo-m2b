<?php

namespace Tests\Feature;

use App\Livewire\Customer\InvoiceList;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerInvoicePartialPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'company_name' => 'PT. DIRA BARAKA MULIA',
            'customer_code' => 'CUST-000044',
        ]);
    }

    public function test_customer_invoice_list_renders_partial_payment_and_correct_stats(): void
    {
        $this->actingAs($this->user);

        // 1 Invoice Paid: 5.000.000
        Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV/2604/0005',
            'type' => 'commercial',
            'invoice_date' => '2026-04-17',
            'due_date' => '2026-04-24',
            'grand_total' => 5000000,
            'total_paid' => 5000000,
            'status' => 'paid',
        ]);

        // 1 Invoice Partial: 20.000.000, paid 12.000.000, remaining 8.000.000
        $partialInv = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV/2607/0006',
            'type' => 'commercial',
            'invoice_date' => '2026-07-30',
            'due_date' => '2026-08-06',
            'grand_total' => 20000000,
            'total_paid' => 12000000,
            'status' => 'partial',
        ]);

        InvoicePayment::create([
            'invoice_id' => $partialInv->id,
            'amount' => 4000000,
            'payment_date' => '2026-08-11',
            'payment_method' => 'Transfer Bank',
            'notes' => 'Cicilan 1',
        ]);

        InvoicePayment::create([
            'invoice_id' => $partialInv->id,
            'amount' => 8000000,
            'payment_date' => '2026-08-14',
            'payment_method' => 'Transfer Bank',
            'notes' => 'Cicilan 2',
        ]);

        Livewire::test(InvoiceList::class)
            ->assertSee('INV/2607/0006')
            ->assertSee('Sebagian (Cicilan)')
            ->assertDontSee('✕ Dibatalkan')
            ->assertSee('8.000.000') // Sisa tagihan
            ->call('openPaymentHistory', $partialInv->id)
            ->assertSet('showPaymentHistoryModal', true)
            ->assertSee('Cicilan 1')
            ->assertSee('Cicilan 2')
            ->assertSee('4.000.000')
            ->assertSee('8.000.000');
    }
}
