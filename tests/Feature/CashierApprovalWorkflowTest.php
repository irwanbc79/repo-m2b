<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\User;
use App\Services\CashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 — maker/checker kasir.
 *
 * Input kasir tidak boleh langsung masuk buku: tersimpan sebagai 'pending'
 * tanpa jurnal, dan baru terbentuk jurnalnya saat accounting menyetujui.
 */
class CashierApprovalWorkflowTest extends TestCase
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
            ['code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'piutang'],
            ['code' => '4101', 'name' => 'Pendapatan Jasa', 'type' => 'pendapatan'],
            ['code' => '5101', 'name' => 'Biaya Operasional', 'type' => 'beban_operasional'],
            ['code' => '5199', 'name' => 'Biaya Lain-lain', 'type' => 'beban_lain'],
        ] as $account) {
            // Sebagian akun sudah di-seed oleh migrasi, jadi jangan paksa create.
            Account::firstOrCreate(
                ['code' => $account['code']],
                $account + ['opening_balance' => 0, 'current_balance' => 0]
            );
        }
    }

    /**
     * Bukti sengaja langsung dilampirkan: test di kelas ini menguji alur
     * verifikasi, bukan aturan bukti wajib (itu di CashierPhase1ControlsTest).
     */
    protected function submitAsKasir(array $overrides = []): CashTransaction
    {
        $tx = $this->submitTanpaBukti($overrides);
        $tx->update(['proof_file' => 'cash-transactions/bukti-test.jpg']);

        return $tx->refresh();
    }

    protected function submitTanpaBukti(array $overrides = []): CashTransaction
    {
        return app(CashierService::class)->submitForApproval(array_merge([
            'type' => 'out',
            'cost_category' => 'shipment',
            'counterpart_type' => 'vendor',
            'amount' => 500000,
            'transaction_date' => '2026-09-01',
            'description' => 'Pembayaran Lift On',
            'created_by' => $this->kasir->id,
        ], $overrides));
    }

    public function test_input_kasir_tersimpan_pending_tanpa_jurnal(): void
    {
        $tx = $this->submitTanpaBukti();

        $this->assertTrue($tx->isPending());
        $this->assertNull($tx->journal_id, 'Transaksi pending tidak boleh punya jurnal.');
        $this->assertFalse((bool) $tx->is_posted);
        $this->assertNotNull($tx->submitted_at);
        $this->assertSame(0, \App\Models\Journal::count(), 'Belum ada jurnal apa pun sebelum verifikasi.');
    }

    public function test_verifikasi_accounting_membentuk_jurnal_seimbang(): void
    {
        $tx = $this->submitAsKasir();

        app(CashierService::class)->approveTransaction($tx, $this->accounting->id);
        $tx->refresh();

        $this->assertTrue($tx->isApproved());
        $this->assertNotNull($tx->journal_id);
        $this->assertTrue((bool) $tx->is_posted);
        $this->assertSame($this->accounting->id, $tx->approved_by);

        $journal = $tx->journal;
        $this->assertSame('posted', $journal->status);
        $this->assertEqualsWithDelta(
            $journal->items()->sum('debit'),
            $journal->items()->sum('credit'),
            0.01,
            'Jurnal hasil verifikasi harus balance.'
        );
        $this->assertEqualsWithDelta(500000, $journal->items()->sum('debit'), 0.01);
    }

    public function test_akun_hasil_verifikasi_sama_dengan_posting_langsung(): void
    {
        // Jaminan Fase 2 tidak mengubah pemetaan akun: jalur draft→approve
        // harus menghasilkan pasangan akun yang identik dengan processPayment().
        $payload = [
            'type' => 'out',
            'cost_category' => 'shipment',
            'counterpart_type' => 'vendor',
            'amount' => 750000,
            'transaction_date' => '2026-09-02',
            'description' => 'Pembayaran SP2',
            'created_by' => $this->kasir->id,
        ];

        $langsung = app(CashierService::class)->processPayment($payload);

        $draft = app(CashierService::class)->submitForApproval($payload);
        $draft->update(['proof_file' => 'cash-transactions/bukti-test.jpg']);
        app(CashierService::class)->approveTransaction($draft->refresh(), $this->accounting->id);
        $draft->refresh();

        $this->assertSame($langsung->account_id, $draft->account_id);
        $this->assertSame($langsung->counter_account_id, $draft->counter_account_id);
    }

    public function test_tidak_bisa_memverifikasi_transaksi_input_sendiri(): void
    {
        $tx = $this->submitAsKasir(['created_by' => $this->accounting->id]);

        $this->expectExceptionMessage('Anda tidak bisa memverifikasi transaksi yang Anda input sendiri.');

        app(CashierService::class)->approveTransaction($tx, $this->accounting->id);
    }

    public function test_transaksi_yang_sudah_disetujui_tidak_bisa_diedit(): void
    {
        $tx = $this->submitAsKasir();
        app(CashierService::class)->approveTransaction($tx, $this->accounting->id);

        $this->expectExceptionMessage('Transaksi sudah diverifikasi accounting dan tidak bisa diedit');

        app(CashierService::class)->updateTransaction($tx->id, [
            'type' => 'out',
            'cost_category' => 'shipment',
            'amount' => 999000,
            'transaction_date' => '2026-09-03',
            'description' => 'Ubah diam-diam',
        ]);
    }

    public function test_penolakan_menyimpan_alasan_dan_tidak_membentuk_jurnal(): void
    {
        $tx = $this->submitAsKasir();

        app(CashierService::class)->rejectTransaction(
            $tx,
            $this->accounting->id,
            'Biaya ini sudah diinput di job costing.'
        );
        $tx->refresh();

        $this->assertTrue($tx->isRejected());
        $this->assertNull($tx->journal_id);
        $this->assertSame('Biaya ini sudah diinput di job costing.', $tx->rejection_reason);
        $this->assertSame(0, \App\Models\Journal::count());
    }

    public function test_revisi_transaksi_ditolak_kembali_ke_antrian(): void
    {
        $tx = $this->submitAsKasir();
        app(CashierService::class)->rejectTransaction($tx, $this->accounting->id, 'Nominal tidak sesuai bukti.');

        app(CashierService::class)->updateTransaction($tx->id, [
            'type' => 'out',
            'cost_category' => 'shipment',
            'amount' => 505152,
            'transaction_date' => '2026-09-01',
            'description' => 'Pembayaran SP2 (revisi)',
        ]);
        $tx->refresh();

        $this->assertTrue($tx->isPending(), 'Revisi harus mengembalikan transaksi ke antrian verifikasi.');
        $this->assertNull($tx->rejection_reason);
        $this->assertNull($tx->journal_id);
        $this->assertEqualsWithDelta(505152, (float) $tx->amount, 0.01);
    }

    public function test_jalur_sistem_tetap_langsung_terbukukan(): void
    {
        // Job costing / invoice / backfill tidak ikut antrian verifikasi.
        $tx = app(CashierService::class)->processPayment([
            'type' => 'out',
            'cost_category' => 'shipment',
            'counterpart_type' => 'vendor',
            'amount' => 300000,
            'transaction_date' => '2026-09-01',
            'description' => 'Auto-book dari job costing',
        ]);

        $this->assertTrue($tx->isApproved());
        $this->assertNotNull($tx->journal_id);
    }

    public function test_kasir_tidak_punya_permission_verifikasi(): void
    {
        $this->assertFalse($this->kasir->hasPermission('cashier.verify'));
        $this->assertTrue($this->kasir->hasPermission('cashier.input'));
        $this->assertTrue($this->accounting->hasPermission('cashier.verify'));
    }
}
