<?php

namespace Tests\Feature;

use App\Livewire\Customer\CustomsCalculator;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCustomsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'customer_code' => 'CUST-001',
            'company_name' => 'PT Sumber Rejeki Abadi',
            'address' => 'Jakarta Barat',
        ]);
    }

    public function test_customer_calculator_renders_successfully()
    {
        Livewire::actingAs($this->user)
            ->test(CustomsCalculator::class)
            ->assertStatus(200)
            ->assertSee('Kalkulator Pabean')
            ->assertSee('Layanan Klien M2B')
            ->assertSee('Buat Booking dari Hasil Simulasi Ini');
    }

    public function test_customer_cif_breakdown_calculates_correctly()
    {
        Livewire::actingAs($this->user)
            ->test(CustomsCalculator::class)
            ->set('cifMethod', 'breakdown')
            ->set('nilai_fob', 20000)
            ->set('nilai_freight', 2500)
            ->set('nilai_insurance', 100)
            ->set('kurs', 16000)
            ->assertSet('nilai_barang', 22600)
            ->assertSet('nilai_pabean', 361600000); // 22,600 * 16,000
    }

    public function test_customer_status_importir_sets_pph_rate()
    {
        Livewire::actingAs($this->user)
            ->test(CustomsCalculator::class)
            ->set('status_importir', 'api')
            ->assertSet('tarif_pph', 2.5)
            ->set('status_importir', 'non_api')
            ->assertSet('tarif_pph', 7.5)
            ->set('status_importir', 'no_npwp')
            ->assertSet('tarif_pph', 15.0);
    }

    public function test_customer_form_e_facility_sets_bm_to_zero()
    {
        Livewire::actingAs($this->user)
            ->test(CustomsCalculator::class)
            ->set('tarif_bm', 15)
            ->set('skema_fasilitas', 'form_e')
            ->assertSet('tarif_bm', 0);
    }

    public function test_customer_calculator_focuses_strictly_on_customs_without_tax_accounting()
    {
        Livewire::actingAs($this->user)
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
            ->assertDontSee('Pajak Masukan')
            ->assertDontSee('Harga Pokok Penjualan')
            ->assertDontSee('HPP');
    }

    public function test_customer_convert_to_booking_redirects()
    {
        Livewire::actingAs($this->user)
            ->test(CustomsCalculator::class)
            ->set('nilai_barang', 15000)
            ->set('mata_uang', 'USD')
            ->call('convertToBooking')
            ->assertRedirect(route('customer.shipments.create', [
                'declared_value' => 15000,
                'currency' => 'USD',
            ]));
    }

    public function test_customer_download_pdf()
    {
        Livewire::actingAs($this->user)
            ->test(CustomsCalculator::class)
            ->set('nilai_barang', 5000)
            ->set('kurs', 16000)
            ->call('downloadPdf')
            ->assertFileDownloaded();
    }
}
