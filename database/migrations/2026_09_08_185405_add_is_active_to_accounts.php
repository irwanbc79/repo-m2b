<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Akun yang tidak dipakai lagi dinonaktifkan, BUKAN dihapus — riwayat jurnal,
 * laporan komparatif, dan kewajiban simpan pembukuan 10 tahun tetap utuh.
 *
 * Akun nonaktif hilang dari daftar pilihan saat input, tapi TETAP muncul di
 * seluruh laporan (buku besar, neraca saldo, neraca).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('type');
                $table->index('is_active');
            }
        });

        // 110-00 "Kas": nol baris jurnal, nol saldo, tidak pernah dipakai sejak
        // dibuat. Dinonaktifkan supaya tidak ada lagi yang salah pilih.
        DB::table('accounts')
            ->where('code', '110-00')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('journal_items')
                    ->whereColumn('journal_items.account_id', 'accounts.id');
            })
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'is_active')) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            }
        });
    }
};
