<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lampiran gambar/PDF pada pesan chat internal.
 *
 * File disimpan di disk `local` (BUKAN `public`) supaya tidak bisa dijangkau
 * lewat URL tanpa login — lampiran chat internal bisa memuat hal yang tidak
 * boleh terbaca customer.
 *
 * `attachment_path` ikut terhapus saat pesan dibersihkan pada usia 90 hari.
 * Tanpa itu, file menumpuk selamanya tanpa ada yang bisa melihatnya lagi —
 * kebocoran penyimpanan yang tidak muncul di layar mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('internal_messages')) {
            return;
        }

        Schema::table('internal_messages', function (Blueprint $table) {
            foreach ([
                'attachment_path' => fn () => $table->string('attachment_path')->nullable(),
                'attachment_name' => fn () => $table->string('attachment_name')->nullable(),
                'attachment_mime' => fn () => $table->string('attachment_mime', 100)->nullable(),
                'attachment_size' => fn () => $table->unsignedInteger('attachment_size')->nullable(),
            ] as $kolom => $buat) {
                if (! Schema::hasColumn('internal_messages', $kolom)) {
                    $buat();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('internal_messages')) {
            return;
        }

        Schema::table('internal_messages', function (Blueprint $table) {
            foreach (['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size'] as $kolom) {
                if (Schema::hasColumn('internal_messages', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
