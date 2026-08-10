<?php

namespace Tests\Feature;

use App\Models\InternalMessage;
use App\Models\User;
use App\Services\InternalChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalChatTest extends TestCase
{
    use RefreshDatabase;

    private InternalChatService $chat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chat = app(InternalChatService::class);
    }

    private function user(string $nama, string $role, ?array $roles = null): User
    {
        return User::factory()->create([
            'name'  => $nama,
            'role'  => $role,
            'roles' => $roles, // kolom sudah di-cast 'array' oleh model — jangan json_encode manual
        ]);
    }

    // ── Kelayakan peserta ──────────────────────────────────────────────

    public function test_konsultan_pajak_dikecualikan_walau_kolom_role_nya_staff(): void
    {
        // Kasus NYATA di production: user #92 "Konsultan Pajak" punya
        // role='staff' dan hanya dikenali lewat roles=["konsultan_pajak"].
        // Menyaring pakai kolom `role` akan diam-diam memasukkan dia.
        $pajak = $this->user('Konsultan Pajak', 'staff', ['konsultan_pajak']);

        $this->assertFalse($this->chat->boleh($pajak));
        $this->assertFalse($this->chat->pesertaLayak()->contains('id', $pajak->id));
    }

    public function test_auditor_dikecualikan(): void
    {
        $auditor = $this->user('Ikamedia', 'auditor', ['auditor']);

        $this->assertFalse($this->chat->boleh($auditor));
    }

    public function test_customer_tidak_pernah_ikut(): void
    {
        $this->assertFalse($this->chat->boleh($this->user('Pelanggan', 'customer')));
    }

    public function test_tim_inti_tetap_layak(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff', 'cashier', 'staff_ppjk']);
        $eka   = $this->user('Eka', 'director', ['director', 'manager']);
        $admin = $this->user('Admin', 'admin', ['admin', 'super_admin']);

        foreach ([$nurul, $eka, $admin] as $u) {
            $this->assertTrue($this->chat->boleh($u), $u->name . ' seharusnya layak');
        }
        $this->assertCount(3, $this->chat->pesertaLayak());
    }

    public function test_daftar_peserta_hanya_memuat_yang_layak(): void
    {
        $this->user('Nurul', 'staff', ['staff']);
        $this->user('Eka', 'director', ['director']);
        $this->user('Konsultan Pajak', 'staff', ['konsultan_pajak']);
        $this->user('Ikamedia', 'auditor', ['auditor']);
        $this->user('Pelanggan', 'customer');

        $nama = $this->chat->pesertaLayak()->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Nurul', 'Eka'], $nama);
    }

    // ── Obrolan semua ──────────────────────────────────────────────────

    public function test_pesan_semua_terlihat_oleh_seluruh_peserta(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);

        $this->chat->kirim($nurul, 'Dokumen PIB sudah siap');

        $this->assertSame('Dokumen PIB sudah siap', $this->chat->pesan($eka)->first()->body);
    }

    // ── Japri ──────────────────────────────────────────────────────────

    public function test_japri_dua_arah_masuk_percakapan_yang_sama(): void
    {
        // Tanpa kunci yang dinormalkan, balasan akan jatuh di percakapan lain
        // dan obrolan terlihat terpotong.
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);

        $this->chat->kirim($nurul, 'Bu, invoice 0012 sudah dibayar?', $eka->id);
        $this->chat->kirim($eka, 'Sudah, tadi pagi', $nurul->id);

        $this->assertCount(2, $this->chat->pesan($nurul, $eka->id));
        $this->assertCount(2, $this->chat->pesan($eka, $nurul->id));
    }

    public function test_japri_tidak_bocor_ke_orang_ketiga(): void
    {
        // Mode gagal terburuk fitur ini.
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);
        $tasya = $this->user('Tasya', 'staff', ['staff']);

        $this->chat->kirim($nurul, 'rahasia gaji', $eka->id);

        $this->assertCount(0, $this->chat->pesan($tasya, $nurul->id));
        $this->assertCount(0, $this->chat->pesan($tasya, $eka->id));
        $this->assertCount(0, $this->chat->pesan($tasya));
    }

    public function test_japri_ke_auditor_ditolak(): void
    {
        $nurul   = $this->user('Nurul', 'staff', ['staff']);
        $auditor = $this->user('Ikamedia', 'auditor', ['auditor']);

        $this->expectExceptionMessage('tidak termasuk peserta');
        $this->chat->kirim($nurul, 'halo', $auditor->id);
    }

    public function test_yang_dikecualikan_tidak_bisa_mengirim(): void
    {
        $pajak = $this->user('Konsultan Pajak', 'staff', ['konsultan_pajak']);

        $this->expectExceptionMessage('tidak memiliki akses');
        $this->chat->kirim($pajak, 'halo semua');
    }

    public function test_pesan_kosong_ditolak(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        $this->expectExceptionMessage('tidak boleh kosong');
        $this->chat->kirim($nurul, '   ');
    }

    // ── Belum dibaca ───────────────────────────────────────────────────

    public function test_pesan_sendiri_tidak_dihitung_belum_dibaca(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $this->chat->kirim($nurul, 'catatan saya');

        $this->assertSame(0, $this->chat->belumDibaca($nurul));
    }

    public function test_belum_dibaca_berkurang_setelah_ditandai(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);

        $this->chat->kirim($nurul, 'satu');
        $this->chat->kirim($nurul, 'dua');
        $this->assertSame(2, $this->chat->belumDibaca($eka));

        $this->chat->tandaiTerbaca($eka);
        $this->assertSame(0, $this->chat->belumDibaca($eka));
    }

    public function test_total_belum_dibaca_menggabungkan_semua_dan_japri(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);
        $tasya = $this->user('Tasya', 'staff', ['staff']);

        $this->chat->kirim($nurul, 'ke semua');
        $this->chat->kirim($nurul, 'japri ke eka', $eka->id);
        $this->chat->kirim($tasya, 'japri ke nurul', $nurul->id); // bukan untuk Eka

        $this->assertSame(2, $this->chat->totalBelumDibaca($eka));
    }

    public function test_japri_orang_lain_tidak_menambah_hitungan(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);
        $tasya = $this->user('Tasya', 'staff', ['staff']);

        $this->chat->kirim($nurul, 'rahasia', $eka->id);

        $this->assertSame(0, $this->chat->totalBelumDibaca($tasya));
    }

    public function test_penanda_polling_berubah_hanya_saat_ada_pesan_baru(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);

        $awal = $this->chat->penandaTerbaru($eka);
        $this->chat->kirim($nurul, 'halo');

        $this->assertGreaterThan($awal, $this->chat->penandaTerbaru($eka));
    }

    // ── Pembersihan 90 hari ────────────────────────────────────────────

    public function test_pesan_lewat_90_hari_dihapus(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        $lama = $this->chat->kirim($nurul, 'pesan lama');
        // created_at tidak ada di $fillable — update() akan mengabaikannya
        // diam-diam, jadi dimundurkan lewat query builder.
        InternalMessage::where('id', $lama->id)->update(['created_at' => now()->subDays(120)]);
        $this->chat->kirim($nurul, 'pesan baru');

        $this->artisan('chat:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('internal_messages', ['id' => $lama->id]);
        $this->assertSame(1, InternalMessage::count());
    }

    public function test_pesan_disematkan_kebal_pembersihan(): void
    {
        // Di logistik, satu pesan bisa berisi instruksi yang masih dipakai
        // berbulan-bulan — menghapusnya diam-diam berbahaya.
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        $penting = $this->chat->kirim($nurul, 'DO dikirim ke Priok besok pagi');
        InternalMessage::where('id', $penting->id)
            ->update(['created_at' => now()->subDays(200), 'is_pinned' => true]);

        $this->artisan('chat:cleanup')->assertSuccessful();

        $this->assertDatabaseHas('internal_messages', ['id' => $penting->id]);
    }

    public function test_dry_run_tidak_menghapus_apa_pun(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $lama = $this->chat->kirim($nurul, 'lama');
        InternalMessage::where('id', $lama->id)->update(['created_at' => now()->subDays(120)]);

        $this->artisan('chat:cleanup --dry-run')->assertSuccessful();

        $this->assertSame(1, InternalMessage::count());
    }
}
