<?php

namespace Tests\Feature;

use App\Livewire\Admin\PettyCashManager;
use App\Models\Account;
use App\Models\PettyCashFund;
use App\Models\PettyCashTransaction;
use App\Models\User;
use App\Services\PettyCashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PettyCashEditUiTest extends TestCase
{
    use RefreshDatabase;

    private PettyCashFund $fund;
    private User $nurul;
    private User $orangLain;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([['1102', 'kas_bank'], ['6202', 'beban_operasional'], ['6201', 'beban_operasional']] as [$kode, $tipe]) {
            Account::create(['code' => $kode, 'name' => 'Akun ' . $kode, 'type' => $tipe]);
        }

        $this->nurul     = User::factory()->create(['role' => 'staff', 'name' => 'Nurul Asyikin']);
        $this->orangLain = User::factory()->create(['role' => 'staff', 'name' => 'Staf Lain']);

        $this->fund = PettyCashFund::create([
            'name' => 'Kas Kecil', 'plafon' => 1000000, 'current_balance' => 1000000,
            'max_transaction' => 750000, 'is_active' => true,
            'holder_user_id' => $this->nurul->id,
            'approver_user_id' => User::factory()->create(['role' => 'admin'])->id,
        ]);
    }

    private function transaksiOleh(User $user): PettyCashTransaction
    {
        $this->actingAs($user);

        return app(PettyCashService::class)->createTransaction($this->fund, [
            'amount' => 45000, 'category' => 'konsumsi', 'description' => 'GALON',
            'proof_file' => 'bukti/x.jpg', 'transaction_date' => now()->toDateString(),
        ]);
    }

    public function test_nurul_bisa_mengubah_transaksinya(): void
    {
        $t = $this->transaksiOleh($this->nurul);

        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->call('openEdit', $t->id)
            ->assertSet('showEditModal', true)
            ->set('editJumlah', 50000)
            ->set('editAlasan', 'salah ketik nominal')
            ->call('saveEdit')
            ->assertSet('showEditModal', false);

        $this->assertEquals(50000, $t->fresh()->amount);
        $this->assertEquals(950000, $this->fund->fresh()->current_balance);
    }

    public function test_alasan_perubahan_wajib_diisi(): void
    {
        // Tanpa alasan, jejaknya tidak bermakna saat ditelusuri belakangan.
        $t = $this->transaksiOleh($this->nurul);

        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->call('openEdit', $t->id)
            ->set('editJumlah', 50000)
            ->set('editAlasan', '')
            ->call('saveEdit')
            ->assertHasErrors(['editAlasan']);

        $this->assertEquals(45000, $t->fresh()->amount);
    }

    public function test_staf_lain_tidak_bisa_mengubah_transaksi_bukan_miliknya(): void
    {
        $t = $this->transaksiOleh($this->nurul);

        Livewire::actingAs($this->orangLain)
            ->test(PettyCashManager::class)
            ->call('openEdit', $t->id)
            ->assertSet('showEditModal', false);

        $this->assertEquals(45000, $t->fresh()->amount);
    }

    public function test_pembatalan_lewat_ui_mengembalikan_saldo(): void
    {
        $t = $this->transaksiOleh($this->nurul);

        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->call('openBatal', $t->id)
            ->set('batalAlasan', 'dobel input')
            ->call('confirmBatal')
            ->assertSet('showBatalModal', false);

        $this->assertSame('cancelled', $t->fresh()->status);
        $this->assertEquals(1000000, $this->fund->fresh()->current_balance);
    }

    public function test_transaksi_dibatalkan_tidak_bisa_diedit_lagi(): void
    {
        $t = $this->transaksiOleh($this->nurul);
        $this->actingAs($this->nurul);
        app(PettyCashService::class)->cancelTransaction($t, 'dobel');

        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->call('openEdit', $t->id)
            ->assertSet('showEditModal', false);
    }

    public function test_job_bisa_dipasang_dan_dilepas(): void
    {
        $customer = \App\Models\Customer::create([
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'customer_code' => \App\Models\Customer::generateCustomerCode(),
            'company_name' => 'PT Dira Baraka Mulia',
        ]);
        $shipment = \App\Models\Shipment::create([
            'customer_id' => $customer->id,
            'awb_number'  => 'IMP-260722-758',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'Shanghai',
            'destination' => 'Surabaya',
            'status' => 'pending',
        ]);

        $t = $this->transaksiOleh($this->nurul);

        // Pasang job
        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->call('openEdit', $t->id)
            ->set('editJob', $shipment->id)
            ->set('editAlasan', 'lupa isi job')
            ->call('saveEdit');

        $this->assertEquals($shipment->id, $t->fresh()->shipment_id);

        // Lepas job lagi
        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->call('openEdit', $t->id)
            ->set('editJob', '')
            ->set('editAlasan', 'ternyata bukan untuk job itu')
            ->call('saveEdit');

        $this->assertNull($t->fresh()->shipment_id);
    }

    public function test_kotak_pencarian_job_terisi_awal_dengan_job_terpasang(): void
    {
        // Kalau kotaknya kosong, staf mengira job-nya belum terisi lalu
        // tanpa sadar melepasnya.
        $customer = \App\Models\Customer::create([
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'customer_code' => \App\Models\Customer::generateCustomerCode(),
            'company_name' => 'PT ATS Inti Sampoerna',
        ]);
        $shipment = \App\Models\Shipment::create([
            'customer_id' => $customer->id,
            'awb_number'  => 'IMP-260706-824',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'Shanghai',
            'destination' => 'Surabaya',
            'status' => 'pending',
        ]);

        $t = $this->transaksiOleh($this->nurul);
        $t->update(['shipment_id' => $shipment->id]);

        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->call('openEdit', $t->id)
            ->assertSet('editJobLabel', 'IMP-260706-824');
    }

    public function test_halaman_terbuka_dan_menampilkan_penanda(): void
    {
        $t = $this->transaksiOleh($this->nurul);
        $this->actingAs($this->nurul);
        app(PettyCashService::class)->updateTransaction($t, ['amount' => 50000], 'koreksi');

        Livewire::actingAs($this->nurul)
            ->test(PettyCashManager::class)
            ->assertOk()
            ->assertSee('DIUBAH');
    }
}
