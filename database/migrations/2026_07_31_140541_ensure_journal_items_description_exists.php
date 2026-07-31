<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambal drift: `journal_items` di production sudah lama punya kolom
 * `description` dan `note`, tapi migration-nya tidak pernah membuatnya —
 * kolomnya ditambahkan langsung di server.
 *
 * Akibatnya siapa pun yang menjalankan migrasi dari nol (termasuk lingkungan
 * test) mendapat modul akuntansi yang rusak: PettyCashService & AccountingService
 * menulis `description` ke journal_items dan langsung gagal.
 *
 * Ber-guard hasColumn, jadi production yang sudah punya kolomnya tidak
 * tersentuh sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('journal_items')) {
            return;
        }

        Schema::table('journal_items', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_items', 'description')) {
                $table->string('description', 500)->nullable();
            }
            if (! Schema::hasColumn('journal_items', 'note')) {
                $table->string('note', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sengaja tidak menghapus: kolom ini sudah dipakai data production
        // sejak lama, jauh sebelum migration ini ada.
    }
};
