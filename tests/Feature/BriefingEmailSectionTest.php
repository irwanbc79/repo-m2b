<?php

namespace Tests\Feature;

use App\Models\Email;
use App\Models\EmailDelivery;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BriefingEmailSectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Briefing dicetak sebagai satu blok teks, jadi isinya diperiksa langsung
     * — bukan lewat expectsOutputToContain berganda, yang mencocokkan per
     * keluaran dan hanya menangkap yang pertama.
     */
    private function isiBriefing(): string
    {
        Artisan::call('finance:daily-briefing');

        return Artisan::output();
    }

    public function test_briefing_menampilkan_sinyal_email_yang_perlu_ditindaklanjuti(): void
    {
        EmailDelivery::create([
            'recipient_email' => 'mati@sinarkencana.co.id',
            'subject'         => 'Tagihan',
            'sent_at'         => now()->subHours(2),
            'status'          => EmailDelivery::STATUS_BOUNCED,
            'mailer'          => 'kirimemail',
        ]);

        EmailDelivery::create([
            'recipient_email' => 'purchasing@bryna.co.id',
            'subject'         => 'Penawaran',
            'sent_at'         => now()->subHours(3),
            'status'          => EmailDelivery::STATUS_OPENED,
            'open_count'      => 4,
            'related_type'    => Quotation::class,
            'related_id'      => 1,
            'mailer'          => 'kirimemail',
        ]);

        Email::create([
            'mailbox'    => 'sales',
            'uid'        => 1,
            'subject'    => 'Menggantung',
            'from_email' => 'a@b.co.id',
            'body'       => 'x',
            'is_read'    => false,
            'email_date' => now()->subHours(30),
        ]);

        $isi = $this->isiBriefing();

        $this->assertStringContainsString('EMAIL', $isi);
        $this->assertStringContainsString('MENTAL', $isi);
        $this->assertStringContainsString('menggantung', $isi);
        $this->assertStringContainsString('quotation dibuka', $isi);
    }

    public function test_briefing_ringkas_saat_email_semua_aman(): void
    {
        $isi = $this->isiBriefing();

        $this->assertStringContainsString('Tidak ada yang perlu ditindaklanjuti', $isi);
    }

    public function test_waktu_balas_ditandai_bila_melewati_target(): void
    {
        Email::create([
            'mailbox' => 'sales', 'uid' => 1, 'subject' => 'A',
            'from_email' => 'a@b.co.id', 'body' => 'x', 'is_read' => true,
            'email_date' => now()->subHours(6),
            'replied_at' => now()->subHours(1), // 5 jam
        ]);

        $this->assertStringContainsString('di atas target 2 jam', $this->isiBriefing());
    }

    public function test_briefing_tetap_jalan_walau_bagian_email_bermasalah(): void
    {
        // Briefing keuangan jauh lebih penting daripada statistik email —
        // kalau bagian ini bermasalah, briefingnya tetap harus terkirim.
        \Illuminate\Support\Facades\Schema::drop('email_deliveries');

        $isi = $this->isiBriefing();

        $this->assertStringContainsString('BRIEFING HARIAN', $isi);
        $this->assertStringNotContainsString('EMAIL', $isi);
    }
}
