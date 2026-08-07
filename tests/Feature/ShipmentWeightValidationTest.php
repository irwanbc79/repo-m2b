<?php

namespace Tests\Feature;

use App\Livewire\Admin\ShipmentDetail;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentWeightValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $customer = Customer::create([
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Contoh',
        ]);

        $this->shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-260807-962',
            'shipment_type' => 'sea',
            'service_type' => 'import',
            'origin' => 'Shanghai, China',
            'destination' => 'Belawan, Indonesia',
            'status' => 'pending',
        ]);
    }

    private function form(array $ubah = []): array
    {
        return array_merge([
            'origin' => 'Shanghai, China',
            'destination' => 'Belawan, Indonesia',
            'weight' => 15051.2,
            'volume' => 30,
            'pieces' => 321,
        ], $ubah);
    }

    public function test_berat_salah_ketik_ditolak_dengan_pesan_jelas(): void
    {
        // Kasus nyata 7 Agustus 2026: staf memasukkan 15.051.200 kg untuk
        // kontainer 1x20 (maksimal ~28 ton). Sebelumnya ini lolos validasi dan
        // meledak jadi error SQL mentah di layar.
        Livewire::actingAs($this->admin)
            ->test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('edit')
            ->set('form', $this->form(['weight' => 15051200]))
            ->call('save')
            ->assertHasErrors(['form.weight']);

        $this->assertNull($this->shipment->fresh()->weight, 'berat tidak boleh tersimpan');
    }

    public function test_berat_wajar_tetap_bisa_disimpan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('edit')
            ->set('form', $this->form(['weight' => 15051.2]))
            ->call('save')
            ->assertHasNoErrors(['form.weight']);

        $this->assertEquals(15051.2, $this->shipment->fresh()->weight);
    }

    public function test_kiriman_besar_yang_sah_tidak_ikut_terblokir(): void
    {
        // 20x40ft bitumen di data nyata: 525.360 kg. Plafon lama decimal(8,2)
        // hanya 999.999,99 — terlalu mepet untuk kiriman yang lebih besar.
        Livewire::actingAs($this->admin)
            ->test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('edit')
            ->set('form', $this->form(['weight' => 1500000]))
            ->call('save')
            ->assertHasNoErrors(['form.weight']);

        $this->assertEquals(1500000, $this->shipment->fresh()->weight);
    }

    public function test_volume_dan_koli_ikut_dijaga(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('edit')
            ->set('form', $this->form(['volume' => 999999, 'pieces' => 99999999]))
            ->call('save')
            ->assertHasErrors(['form.volume', 'form.pieces']);
    }

    public function test_berat_boleh_dikosongkan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('edit')
            ->set('form', $this->form(['weight' => null]))
            ->call('save')
            ->assertHasNoErrors(['form.weight']);
    }
}
