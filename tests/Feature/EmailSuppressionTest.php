<?php

namespace Tests\Feature;

use App\Models\EmailDelivery;
use App\Models\EmailSuppression;
use App\Services\EmailDeliveryTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailSuppressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.from.address' => 'no_reply@m2b.co.id']);
    }

    private function kirimKe(string $email, string $subjek = 'Tagihan'): void
    {
        Mail::raw('isi', function ($m) use ($email, $subjek) {
            $m->to($email)->subject($subjek);
        });
    }

    public function test_pengiriman_dibatalkan_dan_tetap_tercatat(): void
    {
        EmailSuppression::tandai('mati@sinarkencana.co.id', '550');

        $this->kirimKe('mati@sinarkencana.co.id', 'Invoice INV/2607/0009');

        $baris = EmailDelivery::sole();

        // Bukti pembatalan: statusnya suppressed, bukan queued.
        $this->assertSame(EmailDelivery::STATUS_SUPPRESSED, $baris->status);
        $this->assertSame('mati@sinarkencana.co.id', $baris->recipient_email);
        $this->assertSame('Invoice INV/2607/0009', $baris->subject);
    }

    public function test_alamat_bersih_tetap_terkirim(): void
    {
        $this->kirimKe('finance@yossava.co.id');

        $this->assertSame(EmailDelivery::STATUS_QUEUED, EmailDelivery::sole()->status);
    }

    public function test_alamat_internal_tidak_pernah_diblokir(): void
    {
        // Pagar terpenting: peringatan keuangan & briefing dikirim ke domain
        // sendiri. Memblokirnya akan mematikan sistem pemantauan diam-diam —
        // jauh lebih berbahaya daripada mengirim ke kotak internal bermasalah.
        EmailSuppression::tandai('finance@m2b.co.id', '550');

        $this->kirimKe('finance@m2b.co.id', 'Peringatan pembukuan');

        $this->assertSame(EmailDelivery::STATUS_QUEUED, EmailDelivery::sole()->status);
    }

    public function test_besar_kecil_huruf_tidak_membuat_lolos(): void
    {
        EmailSuppression::tandai('Mati@Sinarkencana.co.id', '550');

        $this->kirimKe('MATI@sinarkencana.co.id');

        $this->assertSame(EmailDelivery::STATUS_SUPPRESSED, EmailDelivery::sole()->status);
    }

    public function test_email_mental_otomatis_memblokir_alamatnya(): void
    {
        EmailDelivery::create([
            'recipient_email' => 'mati@sinarkencana.co.id',
            'subject'         => 'Tagihan',
            'sent_at'         => now()->subMinutes(5),
            'status'          => EmailDelivery::STATUS_QUEUED,
            'mailer'          => 'kirimemail',
        ]);

        app(EmailDeliveryTracker::class)->record([
            'provider_event_id' => 'evt-1',
            'event_type'        => EmailDelivery::STATUS_BOUNCED,
            'recipient'         => 'mati@sinarkencana.co.id',
            'subject'           => 'Tagihan',
            'occurred_at'       => now()->timestamp,
            'detail'            => '550 alamat tidak ditemukan',
        ]);

        $this->assertTrue(EmailSuppression::diblokir('mati@sinarkencana.co.id'));
    }

    public function test_blokir_dicabut_saat_email_berhasil_sampai(): void
    {
        EmailSuppression::tandai('finance@yossava.co.id', '550');

        EmailDelivery::create([
            'recipient_email' => 'finance@yossava.co.id',
            'subject'         => 'Tagihan',
            'sent_at'         => now()->subMinutes(5),
            'status'          => EmailDelivery::STATUS_QUEUED,
            'mailer'          => 'kirimemail',
        ]);

        app(EmailDeliveryTracker::class)->record([
            'provider_event_id' => 'evt-2',
            'event_type'        => EmailDelivery::STATUS_DELIVERED,
            'recipient'         => 'finance@yossava.co.id',
            'subject'           => 'Tagihan',
            'occurred_at'       => now()->timestamp,
            'detail'            => '250 OK',
        ]);

        $this->assertFalse(EmailSuppression::diblokir('finance@yossava.co.id'));
    }
}
