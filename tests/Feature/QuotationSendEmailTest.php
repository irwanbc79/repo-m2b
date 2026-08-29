<?php

namespace Tests\Feature;

use App\Livewire\Admin\QuotationManager;
use App\Mail\QuotationMail;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class QuotationSendEmailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Admin Staff',
            'email' => 'admin.staff@m2b.co.id',
            'role' => 'admin',
        ]);
    }

    public function test_modal_kirim_menyiapkan_email_customer_dan_default_cc_staf()
    {
        $admin = $this->admin();
        $customerUser = User::factory()->create(['email' => 'buyer@client.com']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_name' => 'PT Client Maju',
            'customer_code' => 'CLI-001',
            'phone' => '08123456789',
            'address' => 'Jl. Sudirman No 1',
        ]);

        $q = Quotation::create([
            'quotation_number' => 'QT.2026.08.0099',
            'customer_id' => $customer->id,
            'quotation_date' => now(),
            'valid_until' => now()->addDays(7),
            'service_type' => 'import',
            'status' => 'draft',
            'grand_total' => 5000000,
        ]);

        Livewire::actingAs($admin)
            ->test(QuotationManager::class)
            ->call('openSendModal', $q->id)
            ->assertSet('isSendModalOpen', true)
            ->assertSet('sendToEmail', 'buyer@client.com')
            ->assertSet('sendToCc', 'admin.staff@m2b.co.id');
    }

    public function test_toggle_cc_preset_menambah_dan_menghapus_email()
    {
        $admin = $this->admin();
        $q = Quotation::create([
            'quotation_number' => 'QT.2026.08.0100',
            'manual_email' => 'manual@client.com',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(7),
            'service_type' => 'import',
            'status' => 'draft',
            'grand_total' => 10000000,
        ]);

        $test = Livewire::actingAs($admin)
            ->test(QuotationManager::class)
            ->call('openSendModal', $q->id)
            ->assertSet('sendToCc', 'admin.staff@m2b.co.id')
            ->call('toggleCcPreset', 'sales@m2b.co.id');

        $this->assertStringContainsString('sales@m2b.co.id', $test->get('sendToCc'));
        $this->assertStringContainsString('admin.staff@m2b.co.id', $test->get('sendToCc'));

        // Toggle off
        $test->call('toggleCcPreset', 'sales@m2b.co.id');
        $this->assertStringNotContainsString('sales@m2b.co.id', $test->get('sendToCc'));
    }

    public function test_mengirim_quotation_dengan_cc_ke_staf()
    {
        Mail::fake();

        $admin = $this->admin();
        $q = Quotation::create([
            'quotation_number' => 'QT.2026.08.0101',
            'manual_company' => 'PT Mitra Sejahtera',
            'manual_email' => 'mitra@domain.com',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(7),
            'service_type' => 'export',
            'status' => 'draft',
            'grand_total' => 15000000,
        ]);

        Livewire::actingAs($admin)
            ->test(QuotationManager::class)
            ->call('openSendModal', $q->id)
            ->set('sendToCc', 'admin.staff@m2b.co.id, sales@m2b.co.id')
            ->call('sendQuotation')
            ->assertHasNoErrors()
            ->assertSet('isSendModalOpen', false);

        // Assert Mailable was sent with To and CC, identical quotation instance & PDF attachment
        Mail::assertSent(QuotationMail::class, function ($mail) {
            return $mail->hasTo('mitra@domain.com') &&
                   $mail->hasCc('admin.staff@m2b.co.id') &&
                   $mail->hasCc('sales@m2b.co.id') &&
                   $mail->quotation->quotation_number === 'QT.2026.08.0101' &&
                   $mail->quotation->manual_company === 'PT Mitra Sejahtera' &&
                   count($mail->attachments()) > 0;
        });

        // Assert SentEmail record
        $this->assertDatabaseHas('sent_emails', [
            'to_email' => 'mitra@domain.com',
            'cc_email' => 'admin.staff@m2b.co.id, sales@m2b.co.id',
            'mailbox' => 'sales',
        ]);

        // Assert ActivityLog
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Quotation',
            'action' => 'SEND_EMAIL',
            'target_ref' => 'QT.2026.08.0101',
        ]);

        // Assert status updated to sent
        $this->assertEquals('sent', $q->fresh()->status);
        $this->assertNotNull($q->fresh()->approval_token);
    }
}
