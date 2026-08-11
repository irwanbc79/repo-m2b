<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak perubahan komoditi & HS Code pada quotation, untuk audit internal.
 *
 * Klasifikasi barang adalah keputusan yang bisa dipersoalkan belakangan —
 * saat pemeriksaan, saat nilai bea berbeda dari perkiraan, atau saat
 * pelanggan menyanggah. Yang perlu terjawab bukan "kodenya apa" (itu ada di
 * quotation_commodities) melainkan "siapa mengubahnya jadi ini, kapan, dari
 * apa". Karena itu tabel ini menyimpan riwayat, bukan keadaan terakhir.
 *
 * quotation_commodity_id sengaja TIDAK diberi foreign key: barisnya boleh
 * hilang (komoditi dihapus), jejaknya harus tetap ada. Justru penghapusan
 * itulah yang paling perlu terekam.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotation_hs_logs')) {
            Schema::create('quotation_hs_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('quotation_commodity_id')->nullable();

                // 'ditambah' | 'diubah' | 'dihapus'
                $table->string('action', 20);

                // Nama pelaku DISALIN, bukan hanya id: pengguna bisa dihapus
                // atau berganti nama, sedangkan jejak audit harus tetap
                // terbaca bertahun-tahun kemudian.
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 120)->nullable();

                $table->string('commodity_lama', 200)->nullable();
                $table->string('commodity_baru', 200)->nullable();
                $table->string('hs_code_lama', 12)->nullable();
                $table->string('hs_code_baru', 12)->nullable();

                $table->timestamps();

                $table->index(['quotation_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_hs_logs');
    }
};
