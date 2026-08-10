<?php

namespace Tests\Feature;

use App\Livewire\Admin\InternalChat;
use App\Models\User;
use App\Services\InternalChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bunyi ping pesan masuk.
 *
 * Yang dijaga di sini bukan suaranya (itu urusan peramban), melainkan KAPAN
 * peristiwa `chat-masuk` dilepas. Salah pemicu = staf mendengar ping dari
 * pesannya sendiri, atau berbunyi tiap kali berpindah halaman admin — dua
 * hal yang paling cepat membuat fitur ini dimatikan orang.
 */
class InternalChatSoundTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $nama, string $role = 'staff', ?array $roles = null): User
    {
        return User::factory()->create([
            'name'  => $nama,
            'role'  => $role,
            'roles' => $roles, // kolom di-cast 'array' oleh model — jangan json_encode manual
        ]);
    }

    private function chat(): InternalChatService
    {
        return app(InternalChatService::class);
    }

    public function test_pesan_masuk_dari_orang_lain_membunyikan_ping(): void
    {
        $saya = $this->user('Nurul');
        $dia  = $this->user('Eka');

        $komponen = Livewire::actingAs($saya)->test(InternalChat::class);

        $this->chat()->kirim($dia, 'Bu, dokumen sudah siap');

        $komponen->call('denyut')->assertDispatched('chat-masuk');
    }

    public function test_pesan_yang_kita_kirim_sendiri_tidak_membunyikan_ping(): void
    {
        // Inti bug yang dihindari: $penanda ikut naik saat KITA yang mengirim.
        // Kalau bunyi dipicu oleh $penanda, tiap kali menekan Kirim kita
        // mendengar ping dari pesan sendiri.
        $saya = $this->user('Nurul');
        $this->user('Eka');

        $komponen = Livewire::actingAs($saya)->test(InternalChat::class);

        $this->chat()->kirim($saya, 'Halo tim');

        $komponen->call('denyut')->assertNotDispatched('chat-masuk');
    }

    public function test_memuat_halaman_tidak_membunyikan_ping_untuk_pesan_lama(): void
    {
        // Komponen ini ikut di SETIAP halaman admin. Tanpa mount() yang
        // mengambil keadaan awal, denyut pertama selalu melihat "perubahan"
        // sehingga ping berbunyi tiap kali staf berpindah halaman.
        $saya = $this->user('Nurul');
        $dia  = $this->user('Eka');

        $this->chat()->kirim($dia, 'Pesan lama yang belum dibaca');

        Livewire::actingAs($saya)->test(InternalChat::class)
            ->call('denyut')
            ->assertNotDispatched('chat-masuk');
    }

    public function test_denyut_tanpa_pesan_baru_tidak_membunyikan_apa_pun(): void
    {
        $saya = $this->user('Nurul');
        $dia  = $this->user('Eka');

        $komponen = Livewire::actingAs($saya)->test(InternalChat::class);

        $this->chat()->kirim($dia, 'Pesan pertama');
        $komponen->call('denyut')->assertDispatched('chat-masuk');

        // Denyut berikutnya: tidak ada yang baru → hening.
        $komponen->call('denyut')->assertNotDispatched('chat-masuk');
    }

    public function test_pesan_baru_tetap_berbunyi_walau_panel_sedang_terbuka(): void
    {
        // Saat panel terbuka, denyut memanggil tandaiTerbaca() yang melunasi
        // obrolan yang sedang dilihat. Kalau $belumTerakhir tidak dihitung
        // ulang setelah itu, pesan berikutnya kehilangan pingnya.
        $saya = $this->user('Nurul');
        $dia  = $this->user('Eka');

        $komponen = Livewire::actingAs($saya)->test(InternalChat::class)
            ->call('toggle');   // buka panel

        $this->chat()->kirim($dia, 'Pesan pertama');
        $komponen->call('denyut')->assertDispatched('chat-masuk');

        $this->chat()->kirim($dia, 'Pesan kedua');
        $komponen->call('denyut')->assertDispatched('chat-masuk');
    }

    public function test_japri_dari_obrolan_lain_tetap_berbunyi_saat_obrolan_semua_dibuka(): void
    {
        // tandaiTerbaca() hanya melunasi obrolan yang sedang dibuka. Sisa dari
        // obrolan LAIN harus tetap terhitung, kalau tidak japri yang masuk
        // saat kita membuka "Semua" akan diam tanpa bunyi.
        $saya = $this->user('Nurul');
        $dia  = $this->user('Eka');

        $komponen = Livewire::actingAs($saya)->test(InternalChat::class)
            ->call('toggle')          // panel terbuka, obrolan "Semua"
            ->call('pilihLawan');     // pastikan di obrolan Semua

        $this->chat()->kirim($dia, 'Pengumuman untuk semua');
        $komponen->call('denyut')->assertDispatched('chat-masuk');

        // Japri masuk sementara kita masih melihat obrolan "Semua".
        $this->chat()->kirim($dia, 'Bu, ini japri', $saya->id);
        $komponen->call('denyut')->assertDispatched('chat-masuk');
    }

    public function test_ping_tetap_bunyi_walau_sebelumnya_ada_tumpukan_belum_dibaca(): void
    {
        // REGRESI (ketahuan di uji peramban, lolos dari test awal):
        // toggle() dan pilihLawan() memanggil tandaiTerbaca() yang MENURUNKAN
        // jumlah belum-dibaca. Kalau $belumTerakhir tidak ikut disegarkan, ia
        // tertinggal tinggi (mis. 2) sementara hitungan sesudahnya mulai dari
        // 0 lagi — sehingga pesan baru tidak pernah melampauinya dan pingnya
        // hilang diam-diam. Test lain lolos karena mulai dari nol unread.
        $saya = $this->user('Nurul');
        $dia  = $this->user('Eka');

        // Dua pesan lama menumpuk sebelum Nurul membuka portal.
        $this->chat()->kirim($dia, 'Pesan lama 1');
        $this->chat()->kirim($dia, 'Pesan lama 2');

        $komponen = Livewire::actingAs($saya)->test(InternalChat::class)
            ->call('toggle');   // dibaca semua -> unread turun ke 0

        $this->chat()->kirim($dia, 'Pesan BARU yang harus berbunyi');

        $komponen->call('denyut')->assertDispatched('chat-masuk');
    }

    public function test_role_dikecualikan_tidak_pernah_dapat_ping(): void
    {
        $pajak = $this->user('Konsultan Pajak', 'staff', ['konsultan_pajak']);
        $dia   = $this->user('Eka');

        $komponen = Livewire::actingAs($pajak)->test(InternalChat::class);

        $this->chat()->kirim($dia, 'Pesan internal');

        $komponen->call('denyut')->assertNotDispatched('chat-masuk');
    }
}
