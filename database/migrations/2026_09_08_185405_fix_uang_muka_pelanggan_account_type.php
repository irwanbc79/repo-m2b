<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;

/**
 * Koreksi klasifikasi akun 1211 "Uang Muka Pelanggan".
 *
 * Akun ini terdaftar bertipe `piutang` (saldo normal DEBIT), padahal uang muka
 * yang diterima dari pelanggan adalah KEWAJIBAN — kita berhutang jasa kepada
 * mereka sampai pekerjaannya selesai.
 *
 * Akibat salah tipe, saldo yang sebenarnya normal (kredit) tampil sebagai minus
 * besar dan akunnya nongol di sisi aset pada Neraca.
 *
 * Ini murni koreksi KLASIFIKASI: tidak ada satu pun jurnal yang dibuat, diubah,
 * atau dihapus. Nilai transaksinya sama persis, hanya letak dan tandanya yang
 * dibetulkan.
 */
return new class extends Migration
{
    private const KODE = '1211';

    public function up(): void
    {
        $akun = Account::where('code', self::KODE)->first();

        if (! $akun || $akun->type !== 'piutang') {
            return;
        }

        $akun->update(['type' => 'hutang_lancar']);

        // Saldo tersimpan ikut dihitung ulang: tandanya berbalik karena saldo
        // normalnya sekarang kredit.
        $akun->refresh()->recalculateBalance();
    }

    public function down(): void
    {
        $akun = Account::where('code', self::KODE)->first();

        if (! $akun || $akun->type !== 'hutang_lancar') {
            return;
        }

        $akun->update(['type' => 'piutang']);
        $akun->refresh()->recalculateBalance();
    }
};
