<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'notes_en')) {
                $table->longText('notes_en')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('quotations', 'notes_en_source_hash')) {
                // md5 dari `notes` (ID) saat terakhir diterjemahkan — dipakai utk
                // deteksi "terjemahan basi" kalau catatan ID diedit lagi setelahnya.
                $table->string('notes_en_source_hash', 32)->nullable()->after('notes_en');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'notes_en_source_hash')) {
                $table->dropColumn('notes_en_source_hash');
            }
            if (Schema::hasColumn('quotations', 'notes_en')) {
                $table->dropColumn('notes_en');
            }
        });
    }
};
