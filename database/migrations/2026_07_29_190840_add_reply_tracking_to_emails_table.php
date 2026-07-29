<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak balasan pada email masuk.
 *
 * Tanpa ini, dua pertanyaan operasional paling dasar tidak bisa dijawab:
 * berapa lama rata-rata kita membalas customer, dan mana yang menggantung
 * terlalu lama. Di freight forwarding, kecepatan balas quotation itu
 * penentu menang-kalah tender — jadi angkanya layak diukur.
 *
 * Guard hasColumn dipakai karena skema portal ini punya riwayat drift
 * antara migration dan kondisi server.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('emails')) {
            return;
        }

        Schema::table('emails', function (Blueprint $table) {
            if (! Schema::hasColumn('emails', 'replied_at')) {
                $table->timestamp('replied_at')->nullable()->index()->after('is_read');
            }
            if (! Schema::hasColumn('emails', 'replied_by')) {
                // Sengaja tanpa foreign key: staf yang membalas bisa saja
                // dinonaktifkan belakangan, dan jejak balasannya tidak boleh
                // ikut hilang.
                $table->unsignedBigInteger('replied_by')->nullable()->after('replied_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('emails')) {
            return;
        }

        Schema::table('emails', function (Blueprint $table) {
            foreach (['replied_at', 'replied_by'] as $kolom) {
                if (Schema::hasColumn('emails', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
