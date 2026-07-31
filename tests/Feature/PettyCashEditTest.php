<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalItem;
use App\Models\PettyCashFund;
use App\Models\PettyCashTransaction;
use App\Models\PettyCashTransactionLog;
use App\Models\User;
use App\Services\PettyCashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PettyCashEditTest extends TestCase
{
    use RefreshDatabase;

    private PettyCashService $service;
    private PettyCashFund $fund;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PettyCashService::class);

        $nurul = User::factory()->create(['role' => 'admin', 'name' => 'Nurul Asyikin']);
        $this->actingAs($nurul);

        // COA yang dipakai kas kecil & kategori uji.
        foreach ([
            ['1102', 'Kas Kecil'],
            ['6202', 'Beban Konsumsi'],
            ['6201', 'Beban Transport'],
        ] as [$kode, $nama]) {
            Account::create([
                'code' => $kode, 'name' => $nama,
                'type' => $kode === '1102' ? 'kas_bank' : 'beban_operasional',
            ]);
        }

        $this->fund = PettyCashFund::create([
            'name' => 'Kas Kecil Operasional',
            'plafon' => 1000000,
            'current_balance' => 1000000,
            'max_transaction' => 750000,
            'is_active' => true,
            'holder_user_id' => $nurul->id,
            'approver_user_id' => User::factory()->create(['role' => 'admin', 'name' => 'Eka'])->id,
        ]);
    }

    private function buatTransaksi(float $jumlah = 45000, string $kategori = 'konsumsi'): PettyCashTransaction
    {
        return $this->service->createTransaction($this->fund, [
            'amount' => $jumlah,
            'category' => $kategori,
            'description' => 'GALON',
            'proof_file' => 'bukti/galon.jpg',
            'transaction_date' => now()->toDateString(),
        ]);
    }

    /** Total debit − kredit pada satu akun, lintas SEMUA jurnal. */
    private function saldoAkun(string $kode): float
    {
        $akun = Account::where('code', $kode)->first();

        return (float) JournalItem::where('account_id', $akun->id)->sum('debit')
             - (float) JournalItem::where('account_id', $akun->id)->sum('credit');
    }

    // ── Perubahan tanpa dampak pembukuan ───────────────────────────────

    public function test_ubah_keterangan_tidak_menyentuh_saldo_maupun_jurnal(): void
    {
        $t = $this->buatTransaksi();
        $saldoSebelum = $this->fund->fresh()->current_balance;
        $jumlahJurnal = \App\Models\Journal::count();

        $this->service->updateTransaction($t, ['description' => 'GALON AIR MINUM']);

        $this->assertSame('GALON AIR MINUM', $t->fresh()->description);
        $this->assertEquals($saldoSebelum, $this->fund->fresh()->current_balance);
        $this->assertSame($jumlahJurnal, \App\Models\Journal::count(), 'ganti keterangan tidak boleh bikin jurnal baru');
    }

    // ── Perubahan jumlah ───────────────────────────────────────────────

    public function test_menaikkan_jumlah_memotong_saldo_sebesar_selisihnya(): void
    {
        $t = $this->buatTransaksi(45000);
        $this->assertEquals(955000, $this->fund->fresh()->current_balance);

        $this->service->updateTransaction($t, ['amount' => 50000], 'salah ketik');

        // Hanya selisih 5.000 yang dipotong lagi.
        $this->assertEquals(950000, $this->fund->fresh()->current_balance);
    }

    public function test_menurunkan_jumlah_mengembalikan_selisih_ke_saldo(): void
    {
        $t = $this->buatTransaksi(100000);
        $this->assertEquals(900000, $this->fund->fresh()->current_balance);

        $this->service->updateTransaction($t, ['amount' => 40000], 'koreksi');

        $this->assertEquals(960000, $this->fund->fresh()->current_balance);
    }

    public function test_buku_besar_mencerminkan_nilai_akhir_bukan_dobel(): void
    {
        // Inti keamanannya: jurnal lama dibalik, jurnal baru dibuat, sehingga
        // beban bersihnya sama dengan nilai terakhir — bukan 45rb + 50rb.
        $t = $this->buatTransaksi(45000, 'konsumsi');

        $this->service->updateTransaction($t, ['amount' => 50000], 'salah ketik');

        $this->assertEquals(50000, $this->saldoAkun('6202'), 'beban konsumsi harus bersih 50.000');
        $this->assertEquals(-50000, $this->saldoAkun('1102'), 'kas kecil harus berkurang bersih 50.000');
    }

    public function test_ganti_kategori_memindahkan_beban_ke_akun_yang_benar(): void
    {
        $t = $this->buatTransaksi(45000, 'konsumsi');

        $this->service->updateTransaction($t, ['category' => 'bensin'], 'salah kategori');

        $this->assertEquals(0, $this->saldoAkun('6202'), 'beban konsumsi harus kembali nol');
        $this->assertEquals(45000, $this->saldoAkun('6201'), 'beban transport menerima pindahannya');
    }

    // ── Pagar pengaman ─────────────────────────────────────────────────

    public function test_tidak_boleh_melebihi_batas_per_transaksi(): void
    {
        $t = $this->buatTransaksi(45000);

        $this->expectExceptionMessage('melebihi batas per transaksi');
        $this->service->updateTransaction($t, ['amount' => 800000]);
    }

    public function test_tidak_boleh_menaikkan_melebihi_saldo_tersedia(): void
    {
        $t = $this->buatTransaksi(45000);
        $this->fund->update(['current_balance' => 10000]);

        $this->expectExceptionMessage('Saldo kas kecil tidak cukup');
        $this->service->updateTransaction($t->fresh(), ['amount' => 100000]);
    }

    public function test_jumlah_nol_ditolak(): void
    {
        $t = $this->buatTransaksi();

        $this->expectExceptionMessage('Jumlah harus lebih dari nol');
        $this->service->updateTransaction($t, ['amount' => 0]);
    }

    // ── Pembatalan ─────────────────────────────────────────────────────

    public function test_pembatalan_mengembalikan_saldo_dan_menihilkan_buku_besar(): void
    {
        $t = $this->buatTransaksi(45000);

        $this->service->cancelTransaction($t, 'dobel input');

        $this->assertEquals(1000000, $this->fund->fresh()->current_balance, 'saldo kembali utuh');
        $this->assertEquals(0, $this->saldoAkun('6202'), 'beban harus nihil');
        $this->assertEquals(0, $this->saldoAkun('1102'), 'kas kecil harus nihil');
    }

    public function test_transaksi_dibatalkan_tetap_ada_barisnya(): void
    {
        // Sengaja tidak dihapus: nomor transaksi yang lompat tanpa penjelasan
        // menyulitkan penelusuran saat audit.
        $t = $this->buatTransaksi();

        $this->service->cancelTransaction($t, 'dobel input');

        $this->assertDatabaseHas('petty_cash_transactions', [
            'id' => $t->id,
            'status' => 'cancelled',
            'reject_reason' => 'dobel input',
        ]);
        $this->assertNotNull($t->fresh()->cancelled_at);
    }

    public function test_alasan_pembatalan_wajib(): void
    {
        $t = $this->buatTransaksi();

        $this->expectExceptionMessage('Alasan pembatalan wajib diisi');
        $this->service->cancelTransaction($t, '   ');
    }

    public function test_yang_sudah_dibatalkan_tidak_bisa_diubah_atau_dibatalkan_lagi(): void
    {
        $t = $this->buatTransaksi();
        $this->service->cancelTransaction($t, 'dobel input');

        $this->expectExceptionMessage('sudah dibatalkan');
        $this->service->updateTransaction($t->fresh(), ['amount' => 10000]);
    }

    // ── Jejak audit ────────────────────────────────────────────────────

    public function test_perubahan_meninggalkan_jejak_lengkap(): void
    {
        $t = $this->buatTransaksi(45000);

        $this->service->updateTransaction($t, ['amount' => 50000, 'description' => 'GALON 2'], 'salah ketik nominal');

        $log = PettyCashTransactionLog::where('petty_cash_transaction_id', $t->id)->first();

        $this->assertSame(PettyCashTransactionLog::ACTION_UPDATED, $log->action);
        $this->assertSame('salah ketik nominal', $log->reason);
        $this->assertSame('Nurul Asyikin', $log->changed_by_name);
        $this->assertEquals(45000, $log->changes['amount']['dari']);
        $this->assertEquals(50000, $log->changes['amount']['ke']);
        $this->assertSame('GALON', $log->changes['description']['dari']);
    }

    public function test_menyimpan_tanpa_perubahan_tidak_membuat_jejak_palsu(): void
    {
        $t = $this->buatTransaksi(45000);

        $this->service->updateTransaction($t, ['amount' => 45000, 'description' => 'GALON']);

        $this->assertSame(0, PettyCashTransactionLog::count(), 'tidak ada yang berubah = tidak ada jejak');
    }
}
