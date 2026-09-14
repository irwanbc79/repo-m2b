<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\User;
use App\Services\CashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Smoke test tampilan kasir setelah Fase 1 & 2: memastikan komponen tetap
 * ter-render (root element Livewire tunggal, modal baru tidak memecah struktur)
 * dan tombol verifikasi hanya muncul untuk yang berhak.
 */
class SimpleCashierRenderTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $accounting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create(['role' => 'cashier']);
        $this->accounting = User::factory()->create(['role' => 'staff_accounting']);

        foreach ([
            ['code' => '1103', 'name' => 'Bank Mandiri', 'type' => 'kas_bank'],
            ['code' => '5101', 'name' => 'Biaya Operasional', 'type' => 'beban_operasional'],
            ['code' => '5199', 'name' => 'Biaya Lain-lain', 'type' => 'beban_lain'],
        ] as $account) {
            $this->makeAccount($account);
        }
    }

    protected function buatTransaksiPending(array $overrides = []): CashTransaction
    {
        return app(CashierService::class)->submitForApproval(array_merge([
            'type' => 'out',
            'cost_category' => 'shipment',
            'expense_category' => 'lift_on_off',
            'counterpart_type' => 'vendor',
            'amount' => 573870,
            'transaction_date' => now()->toDateString(),
            'description' => 'Pembayaran Lift On',
            'created_by' => $this->kasir->id,
        ], $overrides));
    }

    public function test_halaman_kasir_ter_render_untuk_kasir(): void
    {
        Livewire::actingAs($this->kasir)
            ->test(\App\Livewire\Admin\SimpleCashier::class)
            ->assertOk();
    }

    public function test_antrian_verifikasi_muncul_saat_ada_transaksi_pending(): void
    {
        $this->buatTransaksiPending();

        Livewire::actingAs($this->accounting)
            ->test(\App\Livewire\Admin\SimpleCashier::class)
            ->assertSet('pendingCount', 1)
            ->assertSee('menunggu verifikasi accounting');
    }

    public function test_show_pending_only_menampilkan_transaksi_pending_walau_tanggal_mundur(): void
    {
        $this->buatTransaksiPending([
            'transaction_date' => '2025-10-07',
            'description' => 'Pembayaran Lift On Backdated',
        ]);

        $test = Livewire::actingAs($this->accounting)
            ->test(\App\Livewire\Admin\SimpleCashier::class);

        // Sebelum klik antrian: tabel kosong karena default filter tanggal bulan berjalan
        $this->assertCount(0, $test->get('recentTransactions'));
        $test->assertSet('pendingCount', 1);

        // Setelah klik "Lihat antrian": batasan tanggal di-reset dan transaksi langsung muncul
        $test->call('showPendingOnly')
            ->assertSet('filterStatus', CashTransaction::STATUS_PENDING)
            ->assertSet('filterDateFrom', null)
            ->assertSet('filterDateTo', null)
            ->assertSee('Pembayaran Lift On Backdated');

        $this->assertCount(1, $test->get('recentTransactions'));
    }

    public function test_kasir_tidak_bisa_memanggil_aksi_verifikasi(): void
    {
        $trx = $this->buatTransaksiPending();

        Livewire::actingAs($this->kasir)
            ->test(\App\Livewire\Admin\SimpleCashier::class)
            ->call('approveTransaction', $trx->id)
            ->assertForbidden();
    }

    public function test_verifikasi_tanpa_bukti_memunculkan_modal_pengecualian(): void
    {
        $trx = $this->buatTransaksiPending();

        Livewire::actingAs($this->accounting)
            ->test(\App\Livewire\Admin\SimpleCashier::class)
            ->call('approveTransaction', $trx->id)
            ->assertSet('showApproveNoProofModal', true)
            ->assertSet('approveNoProofId', $trx->id);

        $this->assertTrue($trx->refresh()->isPending(), 'Belum boleh tersetujui sebelum alasannya diisi.');
    }

    public function test_pengeluaran_tanpa_kategori_biaya_ditolak_validasi(): void
    {
        Livewire::actingAs($this->kasir)
            ->test(\App\Livewire\Admin\SimpleCashier::class)
            ->set('transaction_type', 'cash_out')
            ->set('counterpart_type', 'vendor')
            ->set('counterpart_id', 1)
            ->set('amount', 100000)
            ->set('expense_category', '')
            ->call('save')
            ->assertHasErrors(['expense_category']);
    }

    public function test_penolakan_wajib_menyertakan_alasan(): void
    {
        $trx = $this->buatTransaksiPending();

        Livewire::actingAs($this->accounting)
            ->test(\App\Livewire\Admin\SimpleCashier::class)
            ->call('openRejectModal', $trx->id)
            ->set('rejectReason', '')
            ->call('submitRejection')
            ->assertHasErrors(['rejectReason']);
    }
}
