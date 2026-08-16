<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opsi "Sembunyikan otomatis" di sidebar admin.
 *
 * Perilakunya sendiri (menyempit/mengembang) murni CSS + Alpine di sisi
 * browser, jadi yang dijaga di sini adalah kerangkanya: kotak centang ada,
 * penanda ikon/label terpasang, dan skrip pembaca preferensi ikut dikirim
 * lebih dulu supaya sidebar tidak berkedip saat halaman dimuat.
 */
class SidebarAutoHideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_kotak_centang_sembunyikan_otomatis_tersedia(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.email-keluar'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sembunyikan otomatis', $html);
        $this->assertStringContainsString('x-model="autoHide"', $html);
    }

    public function test_preferensi_dibaca_sebelum_halaman_dilukis(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.email-keluar'))
            ->assertOk()
            ->getContent();

        // Skrip anti-kedip harus berada di <head>, sebelum sidebar dirender.
        $posisiSkrip = strpos($html, 'm2b_sidebar_autohide');
        $posisiSidebar = strpos($html, 'id="sidebar"');

        $this->assertNotFalse($posisiSkrip);
        $this->assertNotFalse($posisiSidebar);
        $this->assertLessThan($posisiSidebar, $posisiSkrip);
    }

    public function test_menu_punya_penanda_ikon_dan_label(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.email-keluar'))
            ->assertOk()
            ->getContent();

        // Label yang bisa disembunyikan saat mode rel ikon.
        $this->assertStringContainsString('<span class="sb-ico">📦</span>', $html);
        $this->assertStringContainsString('<span class="sb-txt">Manage Shipments</span>', $html);

        // Judul grup jadi garis pemisah saat menyempit.
        $this->assertStringContainsString('sb-section', $html);
    }
}
