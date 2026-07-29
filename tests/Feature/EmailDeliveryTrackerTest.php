<?php

namespace Tests\Feature;

use App\Models\EmailDelivery;
use App\Models\EmailDeliveryEvent;
use App\Services\EmailDeliveryTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailDeliveryTrackerTest extends TestCase
{
    use RefreshDatabase;

    private EmailDeliveryTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = app(EmailDeliveryTracker::class);
    }

    private function kirim(array $attr = []): EmailDelivery
    {
        return EmailDelivery::create(array_merge([
            'recipient_email' => 'finance@yossava.co.id',
            'subject'         => 'Invoice INV/2607/0012',
            'sent_at'         => now()->subMinutes(5),
            'status'          => EmailDelivery::STATUS_QUEUED,
            'mailer'          => 'kirimemail',
        ], $attr));
    }

    private function peristiwa(array $attr = []): array
    {
        return array_merge([
            'provider_event_id' => 'evt-' . uniqid(),
            'message_guid'      => 'guid-abc',
            'event_type'        => EmailDelivery::STATUS_DELIVERED,
            'recipient'         => 'finance@yossava.co.id',
            'subject'           => 'Invoice INV/2607/0012',
            'occurred_at'       => now()->timestamp,
            'detail'            => '250 OK',
        ], $attr);
    }

    public function test_peristiwa_cocok_lewat_penerima_dan_subject(): void
    {
        $kirim = $this->kirim();

        $this->tracker->record($this->peristiwa());

        $kirim->refresh();
        $this->assertSame(EmailDelivery::STATUS_DELIVERED, $kirim->status);
        $this->assertSame('guid-abc', $kirim->provider_message_guid);
        $this->assertNotNull($kirim->delivered_at);
    }

    public function test_peristiwa_berikutnya_cocok_lewat_message_guid(): void
    {
        // Subject sengaja dibuat beda: sekali terikat, pencocokan tidak lagi
        // bergantung pada subject.
        $kirim = $this->kirim(['provider_message_guid' => 'guid-abc']);

        $this->tracker->record($this->peristiwa([
            'event_type' => EmailDelivery::STATUS_OPENED,
            'subject'    => 'subject yang sama sekali berbeda',
        ]));

        $kirim->refresh();
        $this->assertSame(EmailDelivery::STATUS_OPENED, $kirim->status);
        $this->assertSame(1, $kirim->open_count);
    }

    public function test_subject_terenkode_mime_tetap_cocok(): void
    {
        // Kasus nyata: log provider mengembalikan subject ber-emoji dalam
        // bentuk MIME. Tanpa didekode, email terpenting justru yang gagal cocok.
        $kirim = $this->kirim(['subject' => '[Portal M2B] ✅ Buku kas aman']);

        $this->tracker->record($this->peristiwa([
            'subject' => '[Portal M2B] =?utf-8?Q?=E2=9C=85?= Buku kas aman',
        ]));

        $kirim->refresh();
        $this->assertSame(EmailDelivery::STATUS_DELIVERED, $kirim->status);
    }

    public function test_peristiwa_yang_sama_tidak_dicatat_dua_kali(): void
    {
        $kirim = $this->kirim();
        $peristiwa = $this->peristiwa(['event_type' => EmailDelivery::STATUS_OPENED]);

        $this->tracker->record($peristiwa);
        $this->tracker->record($peristiwa);

        $this->assertSame(1, EmailDeliveryEvent::count());
        $this->assertSame(1, $kirim->fresh()->open_count);
    }

    public function test_dibuka_berkali_kali_dihitung_benar(): void
    {
        $kirim = $this->kirim();

        foreach (range(1, 3) as $i) {
            $this->tracker->record($this->peristiwa([
                'provider_event_id' => "evt-buka-{$i}",
                'event_type'        => EmailDelivery::STATUS_OPENED,
                'occurred_at'       => now()->addMinutes($i)->timestamp,
            ]));
        }

        $kirim->refresh();
        $this->assertSame(3, $kirim->open_count);
        $this->assertNotNull($kirim->first_opened_at);
        $this->assertTrue($kirim->last_opened_at->greaterThan($kirim->first_opened_at));
    }

    public function test_status_tidak_mundur_saat_peristiwa_telat_datang(): void
    {
        $kirim = $this->kirim(['provider_message_guid' => 'guid-abc']);

        $this->tracker->record($this->peristiwa([
            'provider_event_id' => 'evt-buka',
            'event_type'        => EmailDelivery::STATUS_OPENED,
        ]));
        $this->tracker->record($this->peristiwa([
            'provider_event_id' => 'evt-sampai',
            'event_type'        => EmailDelivery::STATUS_DELIVERED,
        ]));

        $kirim->refresh();
        $this->assertSame(EmailDelivery::STATUS_OPENED, $kirim->status);
        // Waktu sampainya tetap dicatat walau statusnya tidak mundur.
        $this->assertNotNull($kirim->delivered_at);
    }

    public function test_email_mental_mencatat_alasannya(): void
    {
        $kirim = $this->kirim();

        $this->tracker->record($this->peristiwa([
            'event_type' => EmailDelivery::STATUS_BOUNCED,
            'detail'     => '550 5.1.1 alamat tidak ditemukan',
        ]));

        $kirim->refresh();
        $this->assertSame(EmailDelivery::STATUS_BOUNCED, $kirim->status);
        $this->assertNotNull($kirim->failed_at);
        $this->assertStringContainsString('550', $kirim->failure_reason);
    }

    public function test_peristiwa_tanpa_pasangan_tetap_disimpan(): void
    {
        // Tidak ada catatan pengiriman yang cocok — peristiwanya tetap tidak
        // boleh dibuang, supaya bisa ditelusuri belakangan.
        $this->tracker->record($this->peristiwa(['recipient' => 'orang@asing.com']));

        $event = EmailDeliveryEvent::sole();
        $this->assertNull($event->email_delivery_id);
        $this->assertSame('orang@asing.com', $event->recipient);
    }

    public function test_peristiwa_di_luar_jendela_waktu_tidak_dicocokkan(): void
    {
        // Email lama dengan subject sama tidak boleh dirampas peristiwa baru.
        $this->kirim(['sent_at' => now()->subDays(30)]);

        $this->tracker->record($this->peristiwa());

        $this->assertNull(EmailDeliveryEvent::sole()->email_delivery_id);
    }

    public function test_subject_kembar_diambil_yang_belum_terikat(): void
    {
        $lama = $this->kirim([
            'sent_at'               => now()->subDays(2),
            'provider_message_guid' => 'guid-lama',
        ]);
        $baru = $this->kirim(['sent_at' => now()->subMinutes(2)]);

        $this->tracker->record($this->peristiwa(['message_guid' => 'guid-baru']));

        $this->assertSame('guid-lama', $lama->fresh()->provider_message_guid);
        $this->assertSame('guid-baru', $baru->fresh()->provider_message_guid);
    }
}
