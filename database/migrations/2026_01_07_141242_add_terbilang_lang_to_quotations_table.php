<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('terbilang_lang', 10)->default('id')->after('notes');
            // Pilihan: 'id' = Indonesia, 'en' = English, 'both' = Bilingual
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('terbilang_lang');
        });
    }
};
