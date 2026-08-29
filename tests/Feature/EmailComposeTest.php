<?php

namespace Tests\Feature;

use App\Livewire\Admin\EmailInbox;
use App\Models\ActivityLog;
use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class EmailComposeTest extends TestCase
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

    public function test_open_compose_modal_menyiapkan_state_compose_dan_default_cc_staf()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailInbox::class)
            ->call('openComposeModal')
            ->assertSet('showReplyModal', true)
            ->assertSet('emailMode', 'compose')
            ->assertSet('replyTo', '')
            ->assertSet('replyCc', 'operasional@m2b.co.id')
            ->assertSet('replySubject', '')
            ->assertSet('replyBody', '');
    }

    public function test_toggle_reply_cc_preset_menambah_dan_menghapus_preset()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailInbox::class)
            ->set('replyCc', 'operasional@m2b.co.id')
            ->call('toggleReplyCcPreset', 'finance@m2b.co.id')
            ->assertSet('replyCc', 'operasional@m2b.co.id, finance@m2b.co.id')
            ->call('toggleReplyCcPreset', 'finance@m2b.co.id')
            ->assertSet('replyCc', 'operasional@m2b.co.id');
    }

    public function test_send_compose_email_sukses_dan_tersimpan_di_sent_emails_dan_activity_log()
    {
        Mail::fake();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailInbox::class)
            ->call('openComposeModal')
            ->set('activeAccount', 'sales')
            ->set('replyTo', 'customer@pt-abadi.com')
            ->set('replyCc', 'operasional@m2b.co.id, finance@m2b.co.id')
            ->set('replySubject', 'Penawaran Tarif Pengiriman Door-to-Door')
            ->set('replyBody', 'Halo Bapak/Ibu, berikut penawaran tarif pengiriman cargo laut...')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSet('showReplyModal', false);

        // Pastikan tersimpan di sent_emails
        $this->assertDatabaseHas('sent_emails', [
            'mailbox' => 'sales',
            'to_email' => 'customer@pt-abadi.com',
            'cc_email' => 'operasional@m2b.co.id, finance@m2b.co.id',
            'subject' => 'Penawaran Tarif Pengiriman Door-to-Door',
            'user_id' => $admin->id,
            'user_name' => 'Staf Operasional',
        ]);

        // Pastikan tercatat di activity_logs
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'COMPOSE_EMAIL',
            'user_id' => $admin->id,
        ]);
    }

    public function test_validasi_input_compose_email()
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EmailInbox::class)
            ->call('openComposeModal')
            ->set('replyTo', '')
            ->set('replySubject', '')
            ->set('replyBody', 'pendek')
            ->call('sendReply')
            ->assertHasErrors(['replyTo', 'replySubject'])
            ->assertSet('showReplyModal', true);
    }
}
