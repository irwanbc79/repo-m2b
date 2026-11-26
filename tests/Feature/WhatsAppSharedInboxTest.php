<?php

namespace Tests\Feature;

use App\Livewire\Admin\MoraLeadManager;
use App\Models\MoraLeadNotification;
use App\Models\User;
use App\Models\WaDevice;
use App\Models\WaMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppSharedInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_webhook_verification_accepts_valid_token_and_rejects_invalid(): void
    {
        config(['whatsapp.verify_token' => 'm2b-verify-secret-2026']);

        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=m2b-verify-secret-2026&hub.challenge=CHALLENGE_OK_999')
            ->assertOk()
            ->assertSee('CHALLENGE_OK_999');

        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong-token&hub.challenge=NOPE')
            ->assertForbidden();
    }

    public function test_meta_webhook_ingests_inbound_message_and_coexistence_echo_idempotently(): void
    {
        config(['whatsapp.app_secret' => '']);

        $inboundPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [[
                            'wa_id' => '6281234567890',
                            'profile' => ['name' => 'Budi Importir'],
                        ]],
                        'messages' => [[
                            'from' => '6281234567890',
                            'id' => 'wamid.INBOUND_001',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Halo M2B, mau tanya biaya impor 1x20ft dari Ningbo ke Belawan'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/api/webhooks/whatsapp', $inboundPayload)
            ->assertOk()
            ->assertJson(['status' => 'ok', 'processed' => 1]);

        // Duplicate delivery must be ignored (idempotent)
        $this->postJson('/api/webhooks/whatsapp', $inboundPayload)
            ->assertOk()
            ->assertJson(['status' => 'ok', 'processed' => 0]);

        $lead = MoraLeadNotification::where('phone', '6281234567890')->firstOrFail();
        $this->assertSame('Budi Importir', $lead->name);
        $this->assertSame('whatsapp_direct', $lead->source);
        $this->assertSame('new', $lead->status);
        $this->assertNull($lead->read_at);

        // Coexistence Echo: balasan langsung dari HP WA Business M2B
        $echoPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'smb_message_echoes',
                    'value' => [
                        'message_echoes' => [[
                            'to' => '6281234567890',
                            'id' => 'wamid.ECHO_001',
                            'timestamp' => (string) now()->addMinute()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Halo Pak Budi, boleh infokan HS Code barangnya?'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/api/webhooks/whatsapp', $echoPayload)
            ->assertOk()
            ->assertJson(['status' => 'ok', 'processed' => 1]);

        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertNotNull($lead->first_responded_at);
        $this->assertSame(2, $lead->waMessages()->count());

        $echoMsg = WaMessage::where('wa_message_id', 'wamid.ECHO_001')->firstOrFail();
        $this->assertSame(WaMessage::DIRECTION_OUTBOUND, $echoMsg->direction);
        $this->assertSame(WaMessage::ORIGIN_COEXISTENCE_ECHO, $echoMsg->origin);
    }

    public function test_meta_webhook_rejects_invalid_hmac_signature_when_app_secret_set(): void
    {
        config(['whatsapp.app_secret' => 'super-secret-meta-app']);

        $this->postJson('/api/webhooks/whatsapp', ['entry' => []], [
            'X-Hub-Signature-256' => 'sha256=invalidhash',
        ])->assertForbidden();
    }

    public function test_staff_can_claim_lead_and_reply_via_meta_cloud_api_in_shared_inbox(): void
    {
        config([
            'whatsapp.enabled'         => true,
            'whatsapp.api_version'     => 'v21.0',
            'whatsapp.phone_number_id' => '123456789012345',
            'whatsapp.access_token'    => 'EAAG_TEST_TOKEN',
        ]);

        Http::fake([
            'https://graph.facebook.com/v21.0/123456789012345/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.OUTBOUND_API_001']],
            ], 200),
        ]);

        $staff = User::factory()->create([
            'name' => 'Nurul Sales',
            'role' => 'staff_sales',
            'roles' => ['staff_sales'],
            'is_active' => true,
        ]);

        $lead = MoraLeadNotification::create([
            'name' => 'PT Prospek Maju',
            'phone' => '081299998888',
            'source' => 'mora_chat',
            'score' => 'hot',
            'service_interest' => 'import',
            'status' => 'new',
        ]);

        Livewire::actingAs($staff)
            ->test(MoraLeadManager::class)
            ->call('selectLead', $lead->id)
            ->call('claimLead', $lead->id)
            ->set('waReplyText', 'Halo Pak, saya Nurul dari M2B siap bantu impornya.')
            ->call('sendWaReply')
            ->assertHasNoErrors();

        $lead->refresh();
        $this->assertSame($staff->id, $lead->assigned_to);
        $this->assertSame('contacted', $lead->status);
        $this->assertNotNull($lead->first_responded_at);

        $sent = WaMessage::where('wa_message_id', 'wamid.OUTBOUND_API_001')->firstOrFail();
        $this->assertSame('6281299998888', $sent->wa_phone);
        $this->assertSame($staff->id, $sent->sent_by);
        $this->assertSame('sent', $sent->status);
    }

    public function test_staff_reply_works_in_transition_mode_when_cloud_api_disabled(): void
    {
        config(['whatsapp.enabled' => false]);

        $staff = User::factory()->create([
            'name' => 'Nadila Ops',
            'role' => 'manager',
            'roles' => ['manager'],
            'is_active' => true,
        ]);

        $lead = MoraLeadNotification::create([
            'name' => 'CV Sukses Jaya',
            'phone' => '081377776666',
            'source' => 'mora_chat',
            'score' => 'warm',
            'service_interest' => 'export',
            'status' => 'new',
        ]);

        Livewire::actingAs($staff)
            ->test(MoraLeadManager::class)
            ->call('selectLead', $lead->id)
            ->set('waReplyText', 'Siap Kak, kami buatkan penawaran ekspornya ya.')
            ->call('sendWaReply')
            ->assertHasNoErrors()
            ->assertDispatched('open-wa-url');

        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertNotNull($lead->first_responded_at);
        $this->assertSame(1, $lead->waMessages()->count());
        $this->assertSame(WaMessage::ORIGIN_PORTAL_MANUAL, $lead->waMessages()->first()->origin);
    }

    public function test_multi_number_coexistence_maps_phone_number_id_to_marketing_device_and_handles_revoke(): void
    {
        config(['whatsapp.app_secret' => '']);

        $admin = User::factory()->create([
            'name' => 'Admin M2B',
            'role' => 'admin',
            'roles' => ['admin'],
            'is_active' => true,
        ]);
        $marketing = User::factory()->create([
            'name' => 'Nurul Marketing',
            'role' => 'staff_sales',
            'roles' => ['staff_sales'],
            'is_active' => true,
        ]);

        // Admin mendaftarkan nomor HP staf marketing lewat UI MoraLeadManager
        Livewire::actingAs($admin)
            ->test(MoraLeadManager::class)
            ->call('openDevicesModal')
            ->set('deviceLabel', 'Marketing - Nurul')
            ->set('devicePhone', '081211112222')
            ->set('devicePhoneNumberId', '987654321098765')
            ->set('deviceUserId', (string) $marketing->id)
            ->call('saveDevice')
            ->assertHasNoErrors();

        $device = WaDevice::where('phone_number_id', '987654321098765')->firstOrFail();
        $this->assertSame('6281211112222', $device->phone_number);
        $this->assertSame($marketing->id, $device->user_id);

        // Pesan masuk ke nomor marketing tersebut -> auto-assign ke Nurul & update last_synced_at
        $this->postJson('/api/webhooks/whatsapp', [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => [
                            'display_phone_number' => '6281211112222',
                            'phone_number_id' => '987654321098765',
                        ],
                        'contacts' => [[
                            'wa_id' => '6285512345678',
                            'profile' => ['name' => 'PT Klien Baru'],
                        ]],
                        'messages' => [[
                            'from' => '6285512345678',
                            'id' => 'wamid.MULTI_IN_001',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Halo Bu Nurul, minta penawaran undername'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk()->assertJson(['processed' => 1]);

        $device->refresh();
        $this->assertNotNull($device->last_synced_at);
        $this->assertSame('healthy', $device->coexistenceHealth());

        $lead = MoraLeadNotification::where('phone', '6285512345678')->firstOrFail();
        $this->assertSame($marketing->id, $lead->assigned_to);
        $this->assertSame($device->id, $lead->wa_device_id);

        // Echo revoke (pesan dihapus) ditangani rapi
        $this->postJson('/api/webhooks/whatsapp', [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'smb_message_echoes',
                    'value' => [
                        'metadata' => ['phone_number_id' => '987654321098765'],
                        'message_echoes' => [[
                            'type' => 'revoke',
                            'revoke' => ['original_message_id' => 'wamid.MULTI_IN_001'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $revoked = WaMessage::where('wa_message_id', 'wamid.MULTI_IN_001')->firstOrFail();
        $this->assertSame('revoked', $revoked->status);
    }
}
