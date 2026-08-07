<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lebarkan kolom berat & volume shipment.
 *
 * Sebelumnya decimal(8,2) → batas 999.999,99. Terlihat longgar, padahal data
 * nyata sudah menyentuh 525.360 kg (20x40ft bitumen) — hanya 2x dari plafon.
 * Sekali M2B menangani kiriman yang lebih besar, penyimpanan GAGAL dengan
 * error SQL mentah di layar staf, bukan pesan yang bisa dimengerti.
 *
 * decimal(12,2) memberi ruang jauh di atas kebutuhan, sehingga yang menyaring
 * angka tidak masuk akal adalah VALIDASI (dengan pesan yang jelas), bukan
 * database yang meledak belakangan.
 *
 * Melebarkan kolom desimal tidak mengubah/memotong data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE shipments MODIFY weight DECIMAL(12,2) NULL');
            DB::statement('ALTER TABLE shipments MODIFY volume DECIMAL(12,2) NULL');

            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('weight', 12, 2)->nullable()->change();
            $table->decimal('volume', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        // Sengaja TIDAK menyempitkan kembali: data yang sudah melebihi
        // decimal(8,2) akan terpotong diam-diam.
    }
};
