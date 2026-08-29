<?php

namespace Tests\Feature;

use App\Livewire\Admin\InvoiceManager;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceSendEmailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Finance Staff',
            'email' => 'finance.staff@m2b.co.id',
            'role' => 'admin',
        ]);
    }

    public function test_modal_kirim_invoice_menyiapkan_recipient_dan_default_cc_staf()
    {
        $admin = $this->admin();
        $customerUser = User::factory()->create(['email' => 'finance@eastchem.com']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'PT Eastchem Agroscience',
            'customer_code' => 'CUST-000001',
            'phone' => '08123456789',
            'address' => 'Kawasan Industri',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV/2608/0005',
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'type' => 'Commercial',
            'status' => 'unpaid',
            'grand_total' => 25000000,
        ]);

        Livewire::actingAs($admin)
            ->test(InvoiceManager::class)
            ->call('openSendModal', $invoice->id)
            ->assertSet('isSendModalOpen', true)
            ->assertSet('email_recipient', 'finance@eastchem.com')
            ->assertSet('email_cc', 'finance.staff@m2b.co.id')
            ->assertSee('INV/2608/0005');
    }

    public function test_toggle_cc_preset_invoice_menambah_dan_menghapus_email()
    {
        $admin = $this->admin();
        $customerUser = User::factory()->create(['email' => 'client@corp.com']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'PT Corp Test',
            'customer_code' => 'CUST-000003',
            'phone' => '08123456781',
            'address' => 'Jl. Merdeka',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV/2608/0006',
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'type' => 'Commercial',
            'status' => 'unpaid',
            'grand_total' => 10000000,
        ]);

        $test = Livewire::actingAs($admin)
            ->test(InvoiceManager::class)
            ->call('openSendModal', $invoice->id)
            ->assertSet('email_cc', 'finance.staff@m2b.co.id')
            ->call('toggleCcPreset', 'finance@m2b.co.id');

        $this->assertStringContainsString('finance@m2b.co.id', $test->get('email_cc'));
        $this->assertStringContainsString('finance.staff@m2b.co.id', $test->get('email_cc'));

        // Toggle off
        $test->call('toggleCcPreset', 'finance@m2b.co.id');
        $this->assertStringNotContainsString('finance@m2b.co.id', $test->get('email_cc'));
    }

    public function test_mengirim_invoice_dengan_cc_ke_staf()
    {
        Mail::fake();

        $admin = $this->admin();
        $customerUser = User::factory()->create(['email' => 'ap@clientcorp.com']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'PT Client Corp',
            'customer_code' => 'CUST-000002',
            'phone' => '08123456780',
            'address' => 'Gedung Wisma',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV/2608/0007',
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'type' => 'Commercial',
            'status' => 'unpaid',
            'grand_total' => 35000000,
        ]);

        Livewire::actingAs($admin)
            ->test(InvoiceManager::class)
            ->call('openSendModal', $invoice->id)
            ->set('email_recipient', 'ap@clientcorp.com')
            ->set('email_cc', 'finance.staff@m2b.co.id, finance@m2b.co.id')
            ->call('sendEmail')
            ->assertHasNoErrors()
            ->assertSet('isSendModalOpen', false);

        // Assert SentEmail record in database
        $this->assertDatabaseHas('sent_emails', [
            'to_email' => 'ap@clientcorp.com',
            'cc_email' => 'finance.staff@m2b.co.id, finance@m2b.co.id',
            'mailbox' => 'finance',
        ]);

        // Assert ActivityLog
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Invoice',
            'action' => 'SEND_EMAIL',
            'target_ref' => 'INV/2608/0007',
        ]);
    }
}
