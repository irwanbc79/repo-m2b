<?php

namespace Tests\Feature;

use App\Livewire\Admin\InternalChat;
use App\Models\User;
use App\Services\InternalChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InternalChatUiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $nama, string $role, ?array $roles = null): User
    {
        return User::factory()->create([
            'name' => $nama, 'role' => $role,
            'roles' => $roles, // kolom sudah di-cast 'array' oleh model — jangan json_encode manual
        ]);
    }

    public function test_panel_tertutup_tidak_memuat_peserta_maupun_pesan(): void
    {
        // Komponen ini ikut di SETIAP halaman admin, jadi saat tertutup ia
        // tidak boleh menjalankan query daftar peserta/pesan.
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $this->user('Eka', 'director', ['director']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->assertSet('terbuka', false)
            ->assertViewHas('peserta', fn ($p) => $p->isEmpty())
            ->assertViewHas('pesan', fn ($m) => $m->isEmpty());
    }

    public function test_membuka_panel_memunculkan_peserta(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $this->user('Eka', 'director', ['director']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->assertSet('terbuka', true)
            ->assertSee('Eka');
    }

    public function test_auditor_tidak_melihat_apa_pun(): void
    {
        $auditor = $this->user('Ikamedia', 'auditor', ['auditor']);

        Livewire::actingAs($auditor)
            ->test(InternalChat::class)
            ->assertViewHas('aktif', false)
            ->assertDontSee('Chat Internal');
    }

    public function test_konsultan_pajak_tidak_melihat_apa_pun(): void
    {
        $pajak = $this->user('Konsultan Pajak', 'staff', ['konsultan_pajak']);

        Livewire::actingAs($pajak)
            ->test(InternalChat::class)
            ->assertViewHas('aktif', false);
    }

    public function test_kirim_pesan_ke_semua(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->set('isi', 'Dokumen sudah dikirim')
            ->call('kirim')
            ->assertSet('isi', '')
            ->assertSee('Dokumen sudah dikirim');
    }

    public function test_pindah_ke_japri_menampilkan_percakapan_yang_benar(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);

        app(InternalChatService::class)->kirim($nurul, 'pesan ke semua');
        app(InternalChatService::class)->kirim($eka, 'ini japri', $nurul->id);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->call('pilihLawan', $eka->id)
            ->assertSee('ini japri')
            ->assertDontSee('pesan ke semua');
    }

    public function test_lencana_hilang_setelah_panel_dibuka(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);

        app(InternalChatService::class)->kirim($eka, 'halo Nurul');

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->assertViewHas('total', 1)
            ->call('toggle')
            ->assertViewHas('total', 0);
    }

    public function test_pesan_kosong_memunculkan_galat_bukan_pesan_kosong(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->set('isi', '   ')
            ->call('kirim')
            ->assertHasErrors('isi');
    }

    public function test_denyut_tidak_meledak_saat_belum_ada_pesan(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('denyut')
            ->assertOk();
    }
}
