<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailHubTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public static function halamanEmail(): array
    {
        return [
            'status keluar' => ['admin.email-keluar'],
            'statistik'     => ['admin.email-statistik'],
        ];
    }

    /**
     * @dataProvider halamanEmail
     */
    public function test_bilah_tab_muncul_di_halaman_email(string $namaRoute): void
    {
        $this->actingAs($this->admin)
            ->get(route($namaRoute))
            ->assertOk()
            ->assertSee('Status Keluar')
            ->assertSee('Statistik')
            ->assertSee('Masuk')
            ->assertSee('Terkirim');
    }

    public function test_menu_sidebar_dilebur_jadi_satu(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.email-keluar'))
            ->assertOk()
            ->getContent();

        // Menu tunggal menggantikan empat baris lama.
        $this->assertStringContainsString('📬 Pusat Email', $html);
        $this->assertStringNotContainsString('📧 Email Inbox', $html);
        $this->assertStringNotContainsString('📤 Email Terkirim', $html);
        $this->assertStringNotContainsString('📊 Status Email Keluar', $html);
        $this->assertStringNotContainsString('📈 Statistik Email', $html);
    }

    public function test_bilah_tab_tidak_muncul_di_halaman_non_email(): void
    {
        // Halaman lain tidak boleh kebagian bilah tab email.
        $html = $this->actingAs($this->admin)
            ->get(route('admin.profit-report'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('aria-label="Pusat Email"', $html);
    }

    public function test_route_lama_tetap_hidup(): void
    {
        // Bookmark & tautan lama staf tidak boleh mati gara-gara peleburan menu.
        foreach (['inbox.index', 'sent-emails.index'] as $namaRoute) {
            $this->assertTrue(
                app('router')->has($namaRoute),
                "route {$namaRoute} harus tetap ada setelah peleburan menu"
            );
        }
    }
}
