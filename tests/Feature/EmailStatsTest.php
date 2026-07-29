<?php

namespace Tests\Feature;

use App\Livewire\Admin\EmailStats;
use App\Models\Email;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use App\Services\EmailStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailStatsTest extends TestCase
{
    use RefreshDatabase;

    private EmailStatsService $stats;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stats = app(EmailStatsService::class);
    }

    private function masuk(array $attr = []): Email
    {
        static $uid = 1;

        return Email::create(array_merge([
            'mailbox'    => 'sales',
            'uid'        => $uid++,
            'subject'    => 'Permintaan penawaran',
            'from_email' => 'purchasing@bryna.co.id',
            'from_name'  => 'CV Bryna',
            'body'       => 'isi',
            'is_read'    => false,
            'email_date' => now()->subHours(2),
        ], $attr));
    }

    private function keluar(array $attr = []): EmailDelivery
    {
        return EmailDelivery::create(array_merge([
            'recipient_email' => 'finance@yossava.co.id',
            'subject'         => 'Tagihan',
            'sent_at'         => now()->subHours(2),
            'status'          => EmailDelivery::STATUS_DELIVERED,
            'mailer'          => 'kirimemail',
        ], $attr));
    }

    // ── Kesehatan kanal ────────────────────────────────────────────────

    public function test_rasio_sampai_dihitung_benar(): void
    {
        $this->keluar(['status' => EmailDelivery::STATUS_DELIVERED]);
        $this->keluar(['status' => EmailDelivery::STATUS_OPENED]);
        $this->keluar(['status' => EmailDelivery::STATUS_BOUNCED]);

        $kanal = $this->stats->kesehatanKanal();

        $this->assertSame(3, $kanal['total_keluar']);
        $this->assertSame(1, $kanal['mental']);
        $this->assertSame(66.7, $kanal['rasio_sampai']);
    }

    public function test_tanpa_data_rasio_null_bukan_nol(): void
    {
        // "0%" saat belum ada data itu menyesatkan — bisa memicu panik
        // padahal tidak ada apa-apa.
        $this->assertNull($this->stats->kesehatanKanal()['rasio_sampai']);
    }

    // ── Waktu balas ────────────────────────────────────────────────────

    public function test_rata_rata_waktu_balas_dihitung_di_php_bukan_sql(): void
    {
        // Perhitungan sengaja tidak memakai fungsi tanggal SQL supaya
        // hasilnya sama di MySQL (production) & SQLite (test) — portal ini
        // sudah punya satu halaman yang rusak di SQLite gara-gara MONTH().
        $this->masuk([
            'email_date' => now()->subHours(5),
            'replied_at' => now()->subHours(4),   // 60 menit
        ]);
        $this->masuk([
            'email_date' => now()->subHours(5),
            'replied_at' => now()->subHours(2),   // 180 menit
        ]);

        $this->assertSame(120.0, $this->stats->operasional()['menit_balas']);
    }

    public function test_email_belum_dibalas_tidak_ikut_rata_rata(): void
    {
        $this->masuk(['email_date' => now()->subHours(5), 'replied_at' => now()->subHours(4)]);
        $this->masuk(['email_date' => now()->subHours(5)]); // belum dibalas

        $operasional = $this->stats->operasional();

        $this->assertSame(60.0, $operasional['menit_balas']);
        $this->assertSame(1, $operasional['belum_dibalas']);
    }

    public function test_tanpa_balasan_sama_sekali_waktu_balas_null(): void
    {
        $this->masuk();

        $this->assertNull($this->stats->operasional()['menit_balas']);
    }

    public function test_jeda_negatif_diabaikan(): void
    {
        // Jaga-jaga bila jam server sempat bergeser: satu baris aneh tidak
        // boleh menarik rata-rata jadi minus.
        $this->masuk(['email_date' => now()->subHours(2), 'replied_at' => now()->subHours(3)]);
        $this->masuk(['email_date' => now()->subHours(3), 'replied_at' => now()->subHours(2)]); // 60 menit

        $this->assertSame(30.0, $this->stats->operasional()['menit_balas']);
    }

    public function test_menggantung_hanya_yang_lewat_24_jam(): void
    {
        $this->masuk(['email_date' => now()->subHours(30)]);          // menggantung
        $this->masuk(['email_date' => now()->subHours(3)]);           // masih wajar
        $this->masuk(['email_date' => now()->subHours(30), 'replied_at' => now()]); // sudah dibalas

        $this->assertSame(1, $this->stats->operasional()['menggantung']);
    }

    public function test_belum_dibalas_dipecah_per_akun(): void
    {
        $this->masuk(['mailbox' => 'sales']);
        $this->masuk(['mailbox' => 'sales']);
        $this->masuk(['mailbox' => 'import']);

        $perAkun = $this->stats->operasional()['per_akun'];

        $this->assertSame(2, $perAkun['sales']);
        $this->assertSame(1, $perAkun['import']);
    }

    // ── Corong bisnis ──────────────────────────────────────────────────

    public function test_quotation_panas_hanya_yang_dibuka_tiga_kali_atau_lebih(): void
    {
        $this->keluar(['related_type' => Quotation::class, 'related_id' => 1, 'open_count' => 5]);
        $this->keluar(['related_type' => Quotation::class, 'related_id' => 2, 'open_count' => 3]);
        $this->keluar(['related_type' => Quotation::class, 'related_id' => 3, 'open_count' => 2]);

        $this->assertSame(2, $this->stats->corongBisnis()['quotation_panas']);
    }

    public function test_rasio_invoice_dibuka(): void
    {
        $this->keluar(['related_type' => Invoice::class, 'related_id' => 1, 'open_count' => 2]);
        $this->keluar(['related_type' => Invoice::class, 'related_id' => 2, 'open_count' => 0]);
        // Email non-invoice tidak boleh ikut menghitung.
        $this->keluar(['open_count' => 9]);

        $corong = $this->stats->corongBisnis();

        $this->assertSame(2, $corong['invoice_terkirim']);
        $this->assertSame(50.0, $corong['rasio_invoice_dibuka']);
    }

    // ── Periode ────────────────────────────────────────────────────────

    public function test_periode_membatasi_cakupan(): void
    {
        $this->keluar(['sent_at' => now()->subDays(3)]);
        $this->keluar(['sent_at' => now()->subDays(45)]);

        $this->assertSame(2, $this->stats->untukPeriode(90)->kesehatanKanal()['total_keluar']);
        $this->assertSame(1, $this->stats->untukPeriode(7)->kesehatanKanal()['total_keluar']);
    }

    // ── Layar ──────────────────────────────────────────────────────────

    public function test_layar_terbuka_dan_periode_bisa_diganti(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->keluar(['sent_at' => now()->subDays(45), 'status' => EmailDelivery::STATUS_BOUNCED]);

        Livewire::actingAs($admin)
            ->test(EmailStats::class)
            ->assertOk()
            // 30 hari: email 45 hari lalu di luar jangkauan.
            ->assertViewHas('kanal', fn ($k) => $k['total_keluar'] === 0)
            ->call('setPeriode', 90)
            ->assertViewHas('kanal', fn ($k) => $k['total_keluar'] === 1);
    }

    public function test_periode_ngawur_dari_url_diabaikan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(EmailStats::class)
            ->call('setPeriode', 99999)
            ->assertSet('periode', 30);
    }

    public function test_halaman_terbuka_lewat_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.email-statistik'))
            ->assertOk()
            ->assertSee('Kesehatan Kanal')
            ->assertSee('Statistik Email');
    }
}
