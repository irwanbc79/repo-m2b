<?php

namespace Tests\Feature;

use App\Livewire\Admin\OutgoingEmails;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OutgoingEmailsScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function kirim(array $attr = []): EmailDelivery
    {
        return EmailDelivery::create(array_merge([
            'recipient_email' => 'finance@yossava.co.id',
            'subject'         => 'Tagihan Juli',
            'sent_at'         => now()->subHour(),
            'status'          => EmailDelivery::STATUS_DELIVERED,
            'mailer'          => 'kirimemail',
        ], $attr));
    }

    public function test_layar_menampilkan_email_keluar(): void
    {
        $this->kirim(['subject' => 'Tagihan PT Yossava']);

        Livewire::actingAs($this->admin)
            ->test(OutgoingEmails::class)
            ->assertOk()
            ->assertSee('Tagihan PT Yossava')
            ->assertSee('finance@yossava.co.id');
    }

    public function test_penyaring_mental_hanya_menampilkan_yang_gagal(): void
    {
        $this->kirim(['subject' => 'Email yang sampai']);
        $this->kirim([
            'subject'        => 'Email yang mental',
            'status'         => EmailDelivery::STATUS_BOUNCED,
            'failure_reason' => '550 alamat tidak ditemukan',
        ]);

        Livewire::actingAs($this->admin)
            ->test(OutgoingEmails::class)
            ->call('setKondisi', 'bounced')
            ->assertSee('Email yang mental')
            ->assertDontSee('Email yang sampai');
    }

    public function test_penyaring_belum_dibuka_mengabaikan_yang_sudah_dibuka(): void
    {
        // Inti nilai bisnisnya: memisahkan "sudah sampai tapi diabaikan" dari
        // "sudah dibaca" — dua hal itu menuntut tindakan yang berbeda.
        $this->kirim(['subject' => 'Belum disentuh', 'open_count' => 0]);
        $this->kirim([
            'subject'    => 'Sudah dibaca',
            'status'     => EmailDelivery::STATUS_OPENED,
            'open_count' => 3,
        ]);

        Livewire::actingAs($this->admin)
            ->test(OutgoingEmails::class)
            ->call('setKondisi', 'unopened')
            ->assertSee('Belum disentuh')
            ->assertDontSee('Sudah dibaca');
    }

    public function test_penyaring_jenis_sistem_hanya_email_tanpa_tautan(): void
    {
        $this->kirim(['subject' => 'Briefing harian']); // tanpa related
        $this->kirim([
            'subject'      => 'Email bertautan invoice',
            'related_type' => Invoice::class,
            'related_id'   => 123,
        ]);

        Livewire::actingAs($this->admin)
            ->test(OutgoingEmails::class)
            ->call('setJenis', 'sistem')
            ->assertSee('Briefing harian')
            ->assertDontSee('Email bertautan invoice');
    }

    public function test_pencarian_menyaring_berdasarkan_penerima(): void
    {
        $this->kirim(['recipient_email' => 'ops@asiagrow.co.id', 'subject' => 'Punya Asia Grow']);
        $this->kirim(['recipient_email' => 'admin@putraraja.co.id', 'subject' => 'Punya Putra Raja']);

        Livewire::actingAs($this->admin)
            ->test(OutgoingEmails::class)
            ->set('search', 'asiagrow')
            ->assertSee('Punya Asia Grow')
            ->assertDontSee('Punya Putra Raja');
    }

    public function test_ringkasan_menghitung_rasio_dengan_benar(): void
    {
        $this->kirim(['status' => EmailDelivery::STATUS_DELIVERED, 'open_count' => 0]);
        $this->kirim(['status' => EmailDelivery::STATUS_OPENED, 'open_count' => 2]);
        $this->kirim(['status' => EmailDelivery::STATUS_BOUNCED]);

        Livewire::actingAs($this->admin)
            ->test(OutgoingEmails::class)
            // 2 dari 3 sampai = 66,7%
            ->assertViewHas('ringkas', function ($r) {
                return $r['total'] === 3
                    && $r['gagal'] === 1
                    && $r['rasio_sampai'] === 66.7
                    // 1 dibuka dari 2 yang sampai
                    && $r['rasio_dibuka'] === 50.0;
            });
    }

    public function test_halaman_terbuka_lewat_route_beserta_layoutnya(): void
    {
        // Penting: lencana "email mental" di sidebar dihitung pada SETIAP
        // halaman admin. Kalau query itu rusak, seluruh halaman admin ikut
        // rusak — jadi harus diuji lewat permintaan HTTP sungguhan, bukan
        // hanya komponen yang berdiri sendiri.
        $this->kirim(['status' => EmailDelivery::STATUS_BOUNCED, 'subject' => 'Tagihan mental']);

        $this->actingAs($this->admin)
            ->get(route('admin.email-keluar'))
            ->assertOk()
            ->assertSee('Tagihan mental')
            // Sidebar ikut terpasang tanpa menjatuhkan halaman. Sejak menu
            // dilebur, penandanya adalah menu tunggal "Pusat Email".
            ->assertSee('Pusat Email');
    }

    public function test_partial_riwayat_menampilkan_perjalanan_email_entitas(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $customer = \App\Models\Customer::create([
            'user_id'       => $user->id,
            'customer_code' => \App\Models\Customer::generateCustomerCode(),
            'company_name'  => 'PT Yossava',
        ]);

        $email = $this->kirim([
            'subject'      => 'Tagihan Juli PT Yossava',
            'related_type' => \App\Models\Customer::class,
            'related_id'   => $customer->id,
            'status'       => EmailDelivery::STATUS_OPENED,
            'open_count'   => 2,
        ]);

        \App\Models\EmailDeliveryEvent::create([
            'email_delivery_id' => $email->id,
            'provider_event_id' => 'evt-partial-1',
            'event_type'        => EmailDelivery::STATUS_DELIVERED,
            'occurred_at'       => now()->subMinutes(30),
        ]);

        $html = view('partials.email-history', ['entity' => $customer])->render();

        $this->assertStringContainsString('Tagihan Juli PT Yossava', $html);
        $this->assertStringContainsString('dibuka 2×', $html);
        $this->assertStringContainsString('Diterima server penerima', $html);
    }

    public function test_partial_riwayat_ramah_saat_belum_ada_data(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $customer = \App\Models\Customer::create([
            'user_id'       => $user->id,
            'customer_code' => \App\Models\Customer::generateCustomerCode(),
            'company_name'  => 'PT Belum Pernah Dikirimi',
        ]);

        $html = view('partials.email-history', ['entity' => $customer])->render();

        $this->assertStringContainsString('Belum ada email yang tercatat', $html);
    }

    public function test_tanpa_data_rasio_tidak_dipaksa_jadi_nol(): void
    {
        // Menampilkan "0%" saat belum ada data itu menyesatkan — bedakan
        // "belum ada data" dari "benar-benar nol".
        Livewire::actingAs($this->admin)
            ->test(OutgoingEmails::class)
            ->assertViewHas('ringkas', fn ($r) => $r['rasio_sampai'] === null && $r['rasio_dibuka'] === null);
    }
}
