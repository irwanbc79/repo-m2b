<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\CashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tiga koreksi yang dikerjakan 2026-09-08:
 * 1. saldo kolom akun tidak lagi melenceng dari buku besar saat transaksi kasir
 * 2. akun bisa dinonaktifkan (hilang dari pilihan, tetap ada di laporan)
 * 3. akun kewajiban yang salah bertipe piutang menampilkan saldo terbalik
 */
class KoreksiAkunKasTest extends TestCase
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

    /* ---------- 1. drift saldo ---------- */

    public function test_transaksi_kasir_tidak_lagi_membuat_saldo_kolom_melenceng(): void
    {
        $bank = Account::where('code', '1103')->first();

        app(CashierService::class)->processPayment([
            'type' => 'out',
            'cost_category' => 'shipment',
            'counterpart_type' => 'vendor',
            'amount' => 4_279_022,
            'transaction_date' => '2026-09-07',
            'description' => 'Pembayaran operasional lapangan',
            'created_by' => $this->kasir->id,
        ]);

        $bank->refresh();

        $this->assertEqualsWithDelta(
            (float) $bank->calculated_balance,
            (float) $bank->current_balance,
            0.01,
            'Saldo kolom harus sama dengan hitungan buku besar setelah transaksi kasir.'
        );
        $this->assertEqualsWithDelta(-4_279_022, (float) $bank->current_balance, 0.01);
    }

    public function test_verifikasi_transaksi_kasir_juga_menyegarkan_saldo(): void
    {
        $bank = Account::where('code', '1103')->first();

        $trx = app(CashierService::class)->submitForApproval([
            'type' => 'out',
            'cost_category' => 'shipment',
            'counterpart_type' => 'vendor',
            'amount' => 573_870,
            'transaction_date' => '2026-09-07',
            'description' => 'Pembayaran Lift On',
            'created_by' => $this->kasir->id,
        ]);

        // Selama menunggu verifikasi, saldo belum boleh bergerak sama sekali.
        $bank->refresh();
        $this->assertEqualsWithDelta(0, (float) $bank->current_balance, 0.01);

        $trx->update(['proof_file' => 'cash-transactions/bukti.jpg']);
        app(CashierService::class)->approveTransaction($trx->refresh(), $this->accounting->id);

        $bank->refresh();
        $this->assertEqualsWithDelta(
            (float) $bank->calculated_balance,
            (float) $bank->current_balance,
            0.01
        );
        $this->assertEqualsWithDelta(-573_870, (float) $bank->current_balance, 0.01);
    }

    /* ---------- 2. nonaktifkan akun ---------- */

    public function test_akun_baru_aktif_secara_bawaan(): void
    {
        $this->assertTrue(Account::where('code', '1103')->first()->is_active);
    }

    public function test_akun_nonaktif_hilang_dari_pilihan_tapi_tetap_ada_di_laporan(): void
    {
        $akun = $this->makeAccount(['code' => '110-00', 'name' => 'Kas', 'type' => 'kas_bank']);
        $akun->update(['is_active' => false]);

        $this->assertFalse(
            Account::aktif()->where('code', '110-00')->exists(),
            'Akun nonaktif tidak boleh muncul di daftar pilihan input.'
        );
        $this->assertTrue(
            Account::where('code', '110-00')->exists(),
            'Akun nonaktif harus tetap terbaca untuk laporan dan riwayat.'
        );
    }

    public function test_akun_berjurnal_tidak_boleh_dihapus_hanya_dinonaktifkan(): void
    {
        $bank = Account::where('code', '1103')->first();
        $this->assertTrue($bank->bolehDihapus(), 'Belum ada jurnal, masih boleh dihapus.');

        app(CashierService::class)->processPayment([
            'type' => 'out',
            'cost_category' => 'shipment',
            'counterpart_type' => 'vendor',
            'amount' => 100_000,
            'transaction_date' => '2026-09-07',
            'description' => 'Uji riwayat',
            'created_by' => $this->kasir->id,
        ]);

        $this->assertFalse(
            $bank->refresh()->bolehDihapus(),
            'Akun yang sudah punya riwayat jurnal tidak boleh dihapus.'
        );
    }

    /* ---------- 3. klasifikasi uang muka pelanggan ---------- */

    public function test_uang_muka_pelanggan_bersaldo_positif_sebagai_kewajiban(): void
    {
        // Uang muka diterima dari pelanggan: bank didebit, uang muka dikredit.
        $uangMuka = $this->makeAccount([
            'code' => '1211',
            'name' => 'Uang Muka Pelanggan',
            'type' => 'hutang_lancar',
        ]);
        $bank = Account::where('code', '1103')->first();

        $journal = \App\Models\Journal::create([
            'journal_number' => 'JR-TEST-0001',
            'transaction_date' => '2026-08-01',
            'description' => 'Terima uang muka pelanggan',
            'status' => 'posted',
            'created_by' => $this->accounting->id,
        ]);
        $journal->items()->createMany([
            ['account_id' => $bank->id, 'debit' => 630_113_457, 'credit' => 0],
            ['account_id' => $uangMuka->id, 'debit' => 0, 'credit' => 630_113_457],
        ]);

        $this->assertEqualsWithDelta(
            630_113_457,
            (float) $uangMuka->refresh()->calculated_balance,
            0.01,
            'Sebagai kewajiban, saldonya positif — bukan minus seperti waktu salah bertipe piutang.'
        );
    }

    public function test_tipe_piutang_membuat_saldo_uang_muka_terbalik_jadi_minus(): void
    {
        // Membuktikan asal-usul minus 630 juta di produksi: tipe akun yang keliru.
        $salahTipe = $this->makeAccount([
            'code' => '1211-SALAH',
            'name' => 'Uang Muka Pelanggan (tipe keliru)',
            'type' => 'piutang',
        ]);
        $bank = Account::where('code', '1103')->first();

        $journal = \App\Models\Journal::create([
            'journal_number' => 'JR-TEST-0002',
            'transaction_date' => '2026-08-01',
            'description' => 'Terima uang muka pelanggan',
            'status' => 'posted',
            'created_by' => $this->accounting->id,
        ]);
        $journal->items()->createMany([
            ['account_id' => $bank->id, 'debit' => 630_113_457, 'credit' => 0],
            ['account_id' => $salahTipe->id, 'debit' => 0, 'credit' => 630_113_457],
        ]);

        $this->assertEqualsWithDelta(
            -630_113_457,
            (float) $salahTipe->refresh()->calculated_balance,
            0.01,
            'Nilai transaksinya sama; hanya tandanya yang terbalik karena salah tipe.'
        );
    }
}
