<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi Lartas OTORITATIF — snapshot data INSW/INTR yang direkam staf M2B
 * per HS code. Ini sumber kebenaran; analisa AI hanya perkiraan sekunder.
 * (Idea #2 grounding — menutup gap "tidak bisa konek live ke insw.go.id".)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lartas_references')) {
            return;
        }

        Schema::create('lartas_references', function (Blueprint $table) {
            $table->id();
            $table->string('hs_code', 30);
            $table->string('trade_flow', 20)->default('import'); // import (PIB) / export (PEB)
            $table->boolean('is_free')->default(false);          // barang bebas lartas
            $table->string('izin_names')->nullable();            // "KT.2, KT.9, SP-5 atau KT-13"
            $table->string('izin_code', 60)->nullable();         // kode izin kepabeanan, mis. 940
            $table->string('komoditi_group')->nullable();        // "Tumbuhan"
            $table->string('regulation')->nullable();            // "PP 14 Tahun 2002 ..."
            $table->text('description')->nullable();             // deskripsi INTR
            $table->text('keterangan')->nullable();
            $table->json('doc_types')->nullable();               // mapping ke katalog M2B
            $table->string('source', 60)->default('INSW/INTR');
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['hs_code', 'trade_flow']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lartas_references');
    }
};
