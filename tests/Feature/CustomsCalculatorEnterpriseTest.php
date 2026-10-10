<?php

namespace Tests\Feature;

use App\Livewire\Admin\CustomsCalculator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomsCalculatorEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'roles' => ['super_admin'],
        ]);
    }

    public function test_calculator_renders_successfully()
    {
        Livewire::actingAs($this->admin)
            ->test(CustomsCalculator::class)
            ->assertStatus(200)
            ->assertSee('Kalkulator Pabean')
            ->assertSee('Mode Operasional')
            ->assertSee('Mode Perpajakan');
    }

    public function test_cif_breakdown_calculates_total_cif_correctly()
    {
        Livewire::actingAs($this->admin)
            ->test(CustomsCalculator::class)
            ->set('cifMethod', 'breakdown')
            ->set('nilai_fob', 10000)
            ->set('nilai_freight', 1500)
            ->set('nilai_insurance', 50)
            ->set('kurs', 16000)
            ->assertSet('nilai_barang', 11550)
            ->assertSet('nilai_pabean', 184800000); // 11,550 * 16,000
    }

    public function test_status_importir_updates_pph_rate()
    {
        Livewire::actingAs($this->admin)
            ->test(CustomsCalculator::class)
            ->set('status_importir', 'api')
            ->assertSet('tarif_pph', 2.5)
            ->set('status_importir', 'non_api')
            ->assertSet('tarif_pph', 7.5)
            ->set('status_importir', 'no_npwp')
            ->assertSet('tarif_pph', 15.0);
    }

    public function test_trade_facility_form_e_sets_bm_to_zero()
    {
        Livewire::actingAs($this->admin)
            ->test(CustomsCalculator::class)
            ->set('tarif_bm', 15)
            ->set('skema_fasilitas', 'form_e')
            ->assertSet('tarif_bm', 0);
    }

    public function test_tax_analysis_separates_cogs_and_tax_credits()
    {
        Livewire::actingAs($this->admin)
            ->test(CustomsCalculator::class)
            ->set('cifMethod', 'direct')
            ->set('nilai_barang', 10000)
            ->set('kurs', 16000) // Nilai pabean = 160,000,000
            ->set('tarif_bm', 10) // BM = 16,000,000. Nilai Impor = 176,000,000
            ->set('tarif_ppn', 11) // PPN = 19,360,000
            ->set('tarif_ppnbm', 0)
            ->set('tarif_pph', 2.5) // PPh = 4,400,000
            ->assertSet('nilai_pabean', 160000000)
            ->assertSet('bayar_bm', 16000000)
            ->assertSet('bayar_ppn', 19360000)
            ->assertSet('bayar_pph', 4400000)
            ->assertSet('total_pungutan', 39760000)
            ->assertSet('beban_pokok', 176000000) // Nilai Pabean + BM = 176,000,000
            ->assertSet('total_kredit_pajak', 23760000); // PPN (19,360,000) + PPh (4,400,000)
    }

    public function test_download_pdf_returns_stream_download()
    {
        Livewire::actingAs($this->admin)
            ->test(CustomsCalculator::class)
            ->set('nilai_barang', 5000)
            ->set('kurs', 16000)
            ->call('downloadPdf')
            ->assertFileDownloaded();
    }

    public function test_save_and_load_history()
    {
        $component = Livewire::actingAs($this->admin)
            ->test(CustomsCalculator::class)
            ->set('nilai_barang', 8000)
            ->set('kurs', 16000)
            ->set('tarif_bm', 5)
            ->call('saveToHistory');

        $history = $component->get('calculationHistory');
        $this->assertNotEmpty($history);
        $this->assertEquals(8000, $history[0]['nilai_barang']);

        // Load history item
        $historyId = $history[0]['id'];
        $component->set('nilai_barang', 1000)
            ->call('loadFromHistory', $historyId)
            ->assertSet('nilai_barang', 8000)
            ->assertSet('tarif_bm', 5);
    }
}
