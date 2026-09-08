<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 1 — pengendalian di titik input & verifikasi:
 * peringatan dugaan duplikat, bukti wajib saat verifikasi, dan kategori biaya
 * terstruktur.
 */
class CashierPhase1ControlsTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;
    protected User $accounting;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create(['role' => 'cashier']);
        $this->accounting = User::factory()->create(['role' => 'staff_accounting']);
        $this->vendor = Vendor::create([
            'code' => 'VEN-MTC',
            'name' => 'MASAJI TATANAN CONTAINER',
            'category' => 'Depo Container',
        ]);

        foreach ([
            ['code' => '1103', 'name' => 'Bank Mandiri', 'type' => 'kas_bank'],
            ['code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'piutang'],
            ['code' => '4101', 'name' => 'Pendapatan Jasa', 'type' => 'pendapatan'],
            ['code' => '5101', 'name' => 'Biaya Operasional', 'type' => 'beban_operasional'],
            ['code' => '5199', 'name' => 'Biaya Lain-lain', 'type' => 'beban_lain'],
        ] as $account) {
            $this->makeAccount($account);
        }
    }

    protected function makeShipment(): Shipment
    {
        $customer = Customer::create([
            'company_name' => 'CV BRYNA BALAKOSA ABADI',
            'customer_code' => 'CUST-BBA',
        ]);

        return Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'EXP-260821-743',
            'origin' => 'Belawan',
            'destination' => 'Shanghai',
            'service_type' => 'export',
            'shipment_type' => 'sea',
            'status' => 'pending',
        ]);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'out',
            'cost_category' => 'shipment',
            'expense_category' => 'lift_on_off',
            'counterpart_type' => 'vendor',
            'vendor_id' => $this->vendor->id,
            'amount' => 573870,
            'transaction_date' => '2026-09-07',
            'description' => 'Pembayaran Lift On',
            'created_by' => $this->kasir->id,
        ], $overrides);
    }

    public function test_kategori_biaya_tersimpan_dan_punya_label(): void
    {
        $tx = app(CashierService::class)->submitForApproval($this->payload());

        $this->assertSame('lift_on_off', $tx->expense_category);
        $this->assertSame('Lift On / Lift Off', $tx->expense_category_label);
        $this->assertFalse($tx->isTalangan());
    }

    public function test_kategori_talangan_ditandai_terpisah_dari_beban_sendiri(): void
    {
        $tx = app(CashierService::class)->submitForApproval(
            $this->payload(['expense_category' => 'bea_masuk_pajak'])
        );

        $this->assertTrue(
            $tx->isTalangan(),
            'Bea masuk & pajak impor ditalangi atas nama customer, bukan beban M2B.'
        );
    }

    public function test_pembayaran_kembar_di_shipment_sama_terdeteksi(): void
    {
        $shipment = $this->makeShipment();

        app(CashierService::class)->submitForApproval(
            $this->payload(['shipment_id' => $shipment->id])
        );

        $dugaan = app(CashierService::class)->findPossibleDuplicates(
            $this->payload(['shipment_id' => $shipment->id, 'transaction_date' => '2026-09-09'])
        );

        $this->assertCount(1, $dugaan, 'Nominal & shipment sama dalam 7 hari harus terdeteksi.');
    }

    public function test_nominal_sama_di_luar_rentang_waktu_tidak_dianggap_duplikat(): void
    {
        $shipment = $this->makeShipment();

        app(CashierService::class)->submitForApproval(
            $this->payload(['shipment_id' => $shipment->id, 'transaction_date' => '2026-09-07'])
        );

        $dugaan = app(CashierService::class)->findPossibleDuplicates(
            $this->payload(['shipment_id' => $shipment->id, 'transaction_date' => '2026-10-20'])
        );

        $this->assertCount(0, $dugaan);
    }

    public function test_transaksi_yang_ditolak_tidak_ikut_dihitung_sebagai_duplikat(): void
    {
        $shipment = $this->makeShipment();

        $tx = app(CashierService::class)->submitForApproval(
            $this->payload(['shipment_id' => $shipment->id])
        );
        app(CashierService::class)->rejectTransaction($tx, $this->accounting->id, 'Salah input, dobel.');

        $dugaan = app(CashierService::class)->findPossibleDuplicates(
            $this->payload(['shipment_id' => $shipment->id])
        );

        $this->assertCount(0, $dugaan);
    }

    public function test_verifikasi_ditolak_bila_belum_ada_bukti(): void
    {
        $tx = app(CashierService::class)->submitForApproval($this->payload());

        $this->expectExceptionMessage('Transaksi ini belum ada bukti');

        app(CashierService::class)->approveTransaction($tx, $this->accounting->id);
    }

    public function test_verifikasi_tanpa_bukti_boleh_dengan_alasan_tertulis(): void
    {
        $tx = app(CashierService::class)->submitForApproval($this->payload());

        app(CashierService::class)->approveTransaction(
            $tx,
            $this->accounting->id,
            'Bayar tunai ke porter di lapangan, tidak ada kwitansi. Sudah dikonfirmasi ke Tasya.'
        );
        $tx->refresh();

        $this->assertTrue($tx->isApproved());
        $this->assertNotNull($tx->journal_id);
        $this->assertStringContainsString('porter', $tx->approval_note);
    }

    public function test_verifikasi_lancar_bila_bukti_sudah_dilampirkan(): void
    {
        $tx = app(CashierService::class)->submitForApproval($this->payload());
        $tx->update(['proof_file' => 'cash-transactions/bukti-lift-on.jpg']);

        app(CashierService::class)->approveTransaction($tx->refresh(), $this->accounting->id);
        $tx->refresh();

        $this->assertTrue($tx->isApproved());
        $this->assertNull($tx->approval_note, 'Tidak perlu alasan kalau buktinya ada.');
    }

    public function test_kategori_biaya_ikut_terbawa_saat_diverifikasi(): void
    {
        $tx = app(CashierService::class)->submitForApproval($this->payload(['expense_category' => 'sp2']));
        $tx->update(['proof_file' => 'cash-transactions/sp2.pdf']);

        app(CashierService::class)->approveTransaction($tx->refresh(), $this->accounting->id);

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $tx->id,
            'expense_category' => 'sp2',
            'approval_status' => CashTransaction::STATUS_APPROVED,
        ]);
    }
}
