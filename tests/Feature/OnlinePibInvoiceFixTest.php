<?php

namespace Tests\Feature;

use App\Livewire\Admin\InvoiceManager;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class OnlinePibInvoiceFixTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Kinan Accounting',
            'email' => 'kinan@m2b.co.id',
            'role' => 'admin',
        ]);
    }

    public function test_open_payment_modal_prefills_remaining_balance()
    {
        $admin = $this->admin();
        $customerUser = User::factory()->create(['email' => 'client@pib.com']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'PT Impor Jaya',
            'customer_code' => 'IMP-001',
            'phone' => '0812345678',
            'address' => 'Jakarta',
        ]);

        $inv = Invoice::create([
            'invoice_number' => 'INV/2608/0003',
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'status' => 'unpaid',
            'grand_total' => 150000,
            'subtotal' => 150000,
            'service_total' => 150000,
            'total_paid' => 0,
        ]);

        Livewire::actingAs($admin)
            ->test(InvoiceManager::class)
            ->call('openPaymentModal', $inv->id)
            ->assertSet('isPaymentModalOpen', true)
            ->assertSet('amount', 150000)
            ->assertSet('selectedInvoiceForPayment.id', $inv->id);
    }

    public function test_save_and_delete_payment_updates_invoice_status_correctly()
    {
        $admin = $this->admin();
        $customerUser = User::factory()->create(['email' => 'client2@pib.com']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'PT Impor Berkah',
            'customer_code' => 'IMP-002',
            'phone' => '0812345679',
            'address' => 'Jakarta',
        ]);

        $inv = Invoice::create([
            'invoice_number' => 'INV/2608/0004',
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'status' => 'unpaid',
            'grand_total' => 150000,
            'subtotal' => 150000,
            'service_total' => 150000,
            'total_paid' => 0,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(InvoiceManager::class)
            ->call('openPaymentModal', $inv->id)
            ->set('amount', 150000)
            ->call('savePaymentNew');

        $inv->refresh();
        $this->assertEquals('paid', $inv->status);
        $this->assertEquals(150000, $inv->total_paid);

        $payment = InvoicePayment::where('invoice_id', $inv->id)->first();
        $this->assertNotNull($payment);

        // Delete payment
        $component->call('deletePayment', $payment->id);

        $inv->refresh();
        $this->assertEquals('unpaid', $inv->status);
        $this->assertEquals(0, $inv->total_paid);
    }

    public function test_daily_accounting_briefing_runs_without_error()
    {
        $exitCode = Artisan::call('finance:daily-briefing');
        $this->assertEquals(0, $exitCode);
    }
}
