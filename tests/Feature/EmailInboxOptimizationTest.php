<?php

namespace Tests\Feature;

use App\Livewire\Admin\EmailInbox;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class EmailInboxOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Staf Operasional',
            'email' => 'operasional@m2b.co.id',
            'role' => 'admin',
        ]);
    }

    public function test_live_search_filters_emails_by_subject_and_sender()
    {
        $admin = $this->admin();

        $id1 = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 101,
            'subject' => 'Inquiry Export Garment to Hamburg',
            'from_email' => 'buyer@germany-textile.de',
            'from_name' => 'Hans Schmidt',
            'body' => 'Need freight quotation',
            'is_read' => true,
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id2 = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 102,
            'subject' => 'Tagihan Maintenance Server',
            'from_email' => 'billing@hosting-vendor.com',
            'from_name' => 'Vendor Cloud',
            'body' => 'Invoice terlampir',
            'is_read' => false,
            'email_date' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(EmailInbox::class)
            ->assertCount('emails', 2)
            ->set('search', 'Garment')
            ->assertCount('emails', 1)
            ->assertSet('emails.0.db_id', $id1)
            ->set('search', 'Vendor Cloud')
            ->assertCount('emails', 1)
            ->assertSet('emails.0.db_id', $id2)
            ->call('clearSearch')
            ->assertSet('search', '')
            ->assertCount('emails', 2);
    }

    public function test_quick_filter_pills_filter_unread_and_attachments()
    {
        $admin = $this->admin();

        $idRead = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 201,
            'subject' => 'Read Email without Attachment',
            'from_email' => 'client1@example.com',
            'is_read' => true,
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $idUnread = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 202,
            'subject' => 'Unread Email with Attachment',
            'from_email' => 'client2@example.com',
            'is_read' => false,
            'email_date' => now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('email_attachments')->insert([
            'email_id' => $idUnread,
            'filename' => 'BillOfLading.pdf',
            'file_path' => 'attachments/bol.pdf',
            'file_size' => 102400,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Filter unread
        Livewire::actingAs($admin)
            ->test(EmailInbox::class)
            ->call('setFilter', 'unread')
            ->assertCount('emails', 1)
            ->assertSet('emails.0.db_id', $idUnread)
            // Filter attachments
            ->call('setFilter', 'attachments')
            ->assertCount('emails', 1)
            ->assertSet('emails.0.db_id', $idUnread)
            // Back to all
            ->call('setFilter', 'all')
            ->assertCount('emails', 2);
    }

    public function test_sender_badge_type_identification()
    {
        $admin = $this->admin();

        $customerUser = User::factory()->create([
            'name' => 'PT Mitra Sejahtera',
            'email' => 'logistics@mitrasejahtera.com',
            'role' => 'customer',
        ]);

        Customer::create([
            'user_id' => $customerUser->id,
            'customer_code' => 'CUST-001',
            'company_name' => 'PT Mitra Sejahtera',
        ]);

        $component = new EmailInbox();

        // 1. Internal Team
        $internalBadge = $component->getSenderBadgeType('finance@m2b.co.id');
        $this->assertEquals('M2B Team', $internalBadge['label']);

        // 2. Verified Customer
        $customerBadge = $component->getSenderBadgeType('logistics@mitrasejahtera.com');
        $this->assertEquals('Verified Customer', $customerBadge['label']);

        // 3. Public Mail
        $gmailBadge = $component->getSenderBadgeType('someone@gmail.com');
        $this->assertEquals('Public Mail', $gmailBadge['label']);

        // 4. Corporate Partner
        $partnerBadge = $component->getSenderBadgeType('agent@maersk-line.com');
        $this->assertEquals('Corporate Partner', $partnerBadge['label']);
    }

    public function test_open_convert_modal_auto_detects_customer_and_logistics_specs()
    {
        $admin = $this->admin();

        $customerUser = User::factory()->create([
            'name' => 'PT Global Indo Raya',
            'email' => 'cargo@globalindo.co.id',
            'role' => 'customer',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'customer_code' => 'CUST-999',
            'company_name' => 'PT Global Indo Raya',
        ]);

        $emailId = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 301,
            'subject' => 'URGENT: AIRFREIGHT EXPORT BOOKING CGK-SIN 5 PALLETS',
            'from_email' => 'cargo@globalindo.co.id',
            'from_name' => 'Cargo Dept Global Indo',
            'body' => 'Please quote rate and space for air shipment export.',
            'is_read' => false,
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(EmailInbox::class)
            ->call('selectEmail', $emailId)
            ->call('openConvertModal')
            ->assertSet('showConvertModal', true)
            ->assertSet('customer_id', $customer->id)
            ->assertSet('service_type', 'export')
            ->assertSet('shipment_type', 'air')
            ->assertSet('autoDetectedContext.customer_name', 'PT Global Indo Raya')
            ->assertSet('autoDetectedContext.service', 'EXPORT')
            ->assertSet('autoDetectedContext.transport', 'AIR FREIGHT');
    }

    public function test_active_mailbox_stats_returns_accurate_dashboard_metrics()
    {
        $admin = $this->admin();

        $id1 = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 401,
            'subject' => 'Email 1',
            'is_read' => false,
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id2 = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 402,
            'subject' => 'Email 2',
            'is_read' => true,
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('email_attachments')->insert([
            'email_id' => $id1,
            'filename' => 'doc.pdf',
            'file_path' => 'doc.pdf',
            'file_size' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component = Livewire::actingAs($admin)->test(EmailInbox::class);
        $stats = $component->get('activeMailboxStats');

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['unread']);
        $this->assertEquals(1, $stats['attachments']);
        $this->assertEquals('sales@m2b.co.id', $stats['email_address']);
    }

    public function test_background_sync_is_scheduled_every_five_minutes()
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $syncEvent = $events->first(function ($event) {
            return preg_match('/\bemail:sync\b/', $event->command ?? '') && !str_contains($event->command ?? '', 'sync-delivery-logs');
        });

        $this->assertNotNull($syncEvent, 'Command email:sync must be registered in routes/console.php schedule.');
        $this->assertEquals('*/5 * * * *', $syncEvent->expression);
        $this->assertTrue($syncEvent->withoutOverlapping);
        $this->assertTrue($syncEvent->runInBackground);
    }
}
