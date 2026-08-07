<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambal drift: `activity_logs` di production sudah lama punya kolom
 * user_name, role, module, dan target_ref — ditambahkan langsung di server,
 * tidak pernah lewat migration.
 *
 * `ActivityLog::record()` menulis keempatnya, dan record() dipanggil dari
 * hampir semua aksi penting portal (simpan shipment, invoice, dsb). Akibatnya
 * di database hasil migrasi dari nol — termasuk lingkungan test — praktis
 * SEMUA aksi tersebut gagal, sehingga alur penyimpanan tidak bisa diuji.
 *
 * Ber-guard hasColumn, jadi production yang sudah punya kolomnya tidak
 * tersentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_logs', 'user_name')) {
                $table->string('user_name')->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'role')) {
                $table->string('role', 50)->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'module')) {
                $table->string('module', 100)->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'target_ref')) {
                $table->string('target_ref')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sengaja tidak menghapus: kolom ini sudah dipakai data production
        // sejak lama, jauh sebelum migration ini ada.
    }
};
