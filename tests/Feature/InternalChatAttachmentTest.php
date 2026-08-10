<?php

namespace Tests\Feature;

use App\Livewire\Admin\InternalChat;
use App\Models\InternalMessage;
use App\Models\User;
use App\Services\InternalChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InternalChatAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $nama, string $role, ?array $roles = null): User
    {
        return User::factory()->create(['name' => $nama, 'role' => $role, 'roles' => $roles]);
    }

    private function svc(): InternalChatService
    {
        return app(InternalChatService::class);
    }

    /** Buat pesan berlampiran beserta file aslinya di disk. */
    private function pesanBerlampiran(User $pengirim, ?int $lawan = null): InternalMessage
    {
        Storage::disk('local')->put('chat-internal/uji.pdf', 'isi pdf');

        return $this->svc()->kirim($pengirim, 'lihat ini', $lawan, [
            'path' => 'chat-internal/uji.pdf',
            'name' => 'Invoice.pdf',
            'mime' => 'application/pdf',
            'size' => 7,
        ]);
    }

    // ── Cara lampiran dibuka ───────────────────────────────────────────

    public function test_gambar_dibuka_sebagai_pratinjau_melayang_bukan_tab_baru(): void
    {
        // Pindah ke tab baru membuat staf kehilangan konteks percakapan hanya
        // untuk melihat satu tangkapan layar. Dikunci di sini supaya tidak
        // diam-diam kembali jadi <a target="_blank"> saat markup dirapikan.
        $eka = $this->user('Eka', 'director');
        $this->user('Nurul', 'staff');

        Storage::disk('local')->put('chat-internal/bl.png', 'data gambar');
        $this->svc()->kirim($eka, 'ini BL nya', null, [
            'path' => 'chat-internal/bl.png',
            'name' => 'bl.png',
            'mime' => 'image/png',
            'size' => 11,
        ]);

        // Penanda dipilih yang KHAS per jenis lampiran: 'bukaPratinjau' dan
        // target="_blank" sama-sama selalu ada di markup (fungsi x-data akar
        // dan tautan "Tab baru" di dalam pratinjau), jadi keduanya tidak bisa
        // membedakan apa pun.
        Livewire::actingAs($eka)->test(InternalChat::class)
            ->call('toggle')
            ->assertSee('Klik untuk memperbesar')
            ->assertSee('@click="bukaPratinjau($event', false);
    }

    public function test_pdf_tetap_dibuka_di_tab_baru(): void
    {
        // PDF sengaja TIDAK ikut pratinjau melayang: peramban sudah punya
        // penampil PDF sendiri yang lebih baik (cari teks, halaman, cetak).
        $eka = $this->user('Eka', 'director');
        $this->user('Nurul', 'staff');

        $this->pesanBerlampiran($eka);

        Livewire::actingAs($eka)->test(InternalChat::class)
            ->call('toggle')
            ->assertSee('Invoice.pdf')
            ->assertSee('target="_blank"', false)
            ->assertDontSee('Klik untuk memperbesar')
            ->assertDontSee('@click="bukaPratinjau($event', false);
    }

    // ── Unggah ─────────────────────────────────────────────────────────

    public function test_gambar_bisa_dilampirkan_dan_diperkecil(): void
    {
        Storage::fake('local');
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->set('berkas', UploadedFile::fake()->image('foto.jpg', 3000, 2000))
            ->set('isi', 'bukti bayar')
            ->call('kirim')
            ->assertHasNoErrors();

        $m = InternalMessage::sole();

        $this->assertTrue($m->punyaLampiran());
        $this->assertSame('image/jpeg', $m->attachment_mime);
        $this->assertSame('foto.jpg', $m->attachment_name);
        Storage::disk('local')->assertExists($m->attachment_path);
    }

    public function test_pesan_boleh_kosong_asal_ada_lampiran(): void
    {
        Storage::fake('local');
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->set('berkas', UploadedFile::fake()->image('foto.jpg'))
            ->set('isi', '')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertSame(1, InternalMessage::count());
    }

    public function test_jenis_file_selain_gambar_dan_pdf_ditolak(): void
    {
        // Bukan cuma soal disk — mencegah file berbahaya diunggah lalu
        // terunduh orang lain.
        Storage::fake('local');
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->set('berkas', UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'))
            ->set('isi', 'coba')
            ->call('kirim')
            ->assertHasErrors('isi');

        $this->assertSame(0, InternalMessage::count());
    }

    public function test_pdf_melebihi_batas_ditolak(): void
    {
        Storage::fake('local');
        $nurul = $this->user('Nurul', 'staff', ['staff']);

        Livewire::actingAs($nurul)
            ->test(InternalChat::class)
            ->call('toggle')
            ->set('berkas', UploadedFile::fake()->create('besar.pdf', 11000, 'application/pdf'))
            ->set('isi', 'coba')
            ->call('kirim')
            ->assertHasErrors('isi');

        $this->assertSame(0, InternalMessage::count());
    }

    // ── Hak akses file ─────────────────────────────────────────────────

    public function test_peserta_bisa_membuka_lampiran_obrolan_semua(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);

        $m = $this->pesanBerlampiran($nurul);

        $this->actingAs($eka)->get(route('admin.chat.lampiran', $m->id))->assertOk();
    }

    public function test_lampiran_japri_tidak_bisa_dibuka_orang_ketiga(): void
    {
        // Mode gagal terburuk: URL lampiran tersebar lalu isi percakapan
        // pribadi ikut terbuka.
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $eka   = $this->user('Eka', 'director', ['director']);
        $tasya = $this->user('Tasya', 'staff', ['staff']);

        $m = $this->pesanBerlampiran($nurul, $eka->id);

        $this->actingAs($eka)->get(route('admin.chat.lampiran', $m->id))->assertOk();
        $this->actingAs($tasya)->get(route('admin.chat.lampiran', $m->id))->assertForbidden();
    }

    public function test_auditor_tidak_bisa_membuka_lampiran(): void
    {
        $nurul   = $this->user('Nurul', 'staff', ['staff']);
        $auditor = $this->user('Ikamedia', 'auditor', ['auditor']);

        $m = $this->pesanBerlampiran($nurul);

        $this->actingAs($auditor)->get(route('admin.chat.lampiran', $m->id))->assertForbidden();
    }

    public function test_konsultan_pajak_tidak_bisa_membuka_lampiran(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $pajak = $this->user('Konsultan Pajak', 'staff', ['konsultan_pajak']);

        $m = $this->pesanBerlampiran($nurul);

        $this->actingAs($pajak)->get(route('admin.chat.lampiran', $m->id))->assertForbidden();
    }

    // ── Pembersihan 90 hari ────────────────────────────────────────────

    public function test_file_ikut_terhapus_saat_pembersihan(): void
    {
        // Kalau baris dihapus tapi filenya tidak, file menumpuk selamanya
        // tanpa ada yang bisa melihat atau menghapusnya.
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $m = $this->pesanBerlampiran($nurul);
        $path = $m->attachment_path;

        InternalMessage::where('id', $m->id)->update(['created_at' => now()->subDays(120)]);

        Storage::disk('local')->assertExists($path);
        $this->artisan('chat:cleanup')->assertSuccessful();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('internal_messages', ['id' => $m->id]);
    }

    public function test_file_pesan_disematkan_tidak_ikut_terhapus(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $m = $this->pesanBerlampiran($nurul);
        $path = $m->attachment_path;

        InternalMessage::where('id', $m->id)
            ->update(['created_at' => now()->subDays(200), 'is_pinned' => true]);

        $this->artisan('chat:cleanup')->assertSuccessful();

        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseHas('internal_messages', ['id' => $m->id]);
    }

    public function test_dry_run_tidak_menghapus_file(): void
    {
        $nurul = $this->user('Nurul', 'staff', ['staff']);
        $m = $this->pesanBerlampiran($nurul);
        $path = $m->attachment_path;

        InternalMessage::where('id', $m->id)->update(['created_at' => now()->subDays(120)]);

        $this->artisan('chat:cleanup --dry-run')->assertSuccessful();

        Storage::disk('local')->assertExists($path);
    }
}
