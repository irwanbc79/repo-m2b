<?php

namespace Tests\Feature;

use App\Models\EmailWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KirimEmailWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-uji-fase-nol';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kirimemail.webhook_token' => self::TOKEN]);
    }

    private function url(?string $token = null): string
    {
        return '/api/webhooks/kirimemail/' . ($token ?? self::TOKEN);
    }

    public function test_token_salah_ditolak(): void
    {
        $this->postJson($this->url('token-ngawur'), ['event_type' => 'delivered'])
            ->assertStatus(401);

        $this->assertSame(0, EmailWebhookEvent::count());
    }

    public function test_tanpa_token_terkonfigurasi_semua_ditolak(): void
    {
        config(['services.kirimemail.webhook_token' => null]);

        $this->postJson($this->url(), ['event_type' => 'delivered'])
            ->assertStatus(401);
    }

    public function test_payload_disimpan_utuh_apa_pun_bentuknya(): void
    {
        // Bentuk asli belum diketahui — kunci yang tidak dikenal pun wajib selamat.
        $payload = [
            'event_type'      => 'delivered',
            'message_guid'    => '0453bb84-e955-493f-b98e-d42de38bf0e8',
            'recipient'       => 'finance@yossava.co.id',
            'subject'         => 'Invoice INV/2607/0012',
            'kunci_tak_dikenal' => ['a' => 1],
        ];

        $this->postJson($this->url(), $payload)
            ->assertOk()
            ->assertJson(['success' => true, 'received' => 1]);

        $row = EmailWebhookEvent::sole();

        $this->assertSame('delivered', $row->event_type);
        $this->assertSame('0453bb84-e955-493f-b98e-d42de38bf0e8', $row->message_guid);
        $this->assertSame('finance@yossava.co.id', $row->recipient);
        $this->assertSame('Invoice INV/2607/0012', $row->subject);
        $this->assertSame($payload, $row->payload, 'payload harus tersimpan persis seperti diterima');
        $this->assertNull($row->processed_at);
    }

    public function test_nama_kunci_alternatif_ikut_terbaca(): void
    {
        $this->postJson($this->url(), [
            'event' => 'opened',
            'guid'  => 'abc-123',
            'to'    => 'ops@asiagrow.co.id',
        ])->assertOk();

        $row = EmailWebhookEvent::sole();

        $this->assertSame('opened', $row->event_type);
        $this->assertSame('abc-123', $row->message_guid);
        $this->assertSame('ops@asiagrow.co.id', $row->recipient);
    }

    public function test_payload_bentuk_daftar_dipecah_jadi_beberapa_baris(): void
    {
        $this->postJson($this->url(), [
            ['event_type' => 'delivered', 'recipient' => 'a@m2b.co.id'],
            ['event_type' => 'opened',    'recipient' => 'b@m2b.co.id'],
        ])->assertOk()->assertJson(['received' => 2]);

        $this->assertSame(2, EmailWebhookEvent::count());
        $this->assertSame(
            ['delivered', 'opened'],
            EmailWebhookEvent::orderBy('id')->pluck('event_type')->all()
        );
    }

    public function test_payload_tanpa_kunci_yang_dikenal_tetap_diterima(): void
    {
        // Justru kasus inilah yang paling mungkin terjadi di fase 00:
        // formatnya sama sekali di luar dugaan, dan tetap tidak boleh hilang.
        $this->postJson($this->url(), ['sesuatu_yang_asing' => 'nilai'])
            ->assertOk();

        $row = EmailWebhookEvent::sole();

        $this->assertNull($row->event_type);
        $this->assertNull($row->message_guid);
        $this->assertSame(['sesuatu_yang_asing' => 'nilai'], $row->payload);
    }
}
