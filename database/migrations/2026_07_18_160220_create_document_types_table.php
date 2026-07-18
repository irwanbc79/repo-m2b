<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master jenis dokumen (katalog) — fondasi Checklist Dokumen & AI Lartas.
 * Aditif: tidak menyentuh tabel lama. Portabel (SQLite lokal & MySQL prod).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 100);                          // nama kanonik (selaras getDocumentTriggers)
            $table->json('aliases')->nullable();                      // nama alternatif utk pencocokan
            $table->string('category', 30);                           // komersial/angkut/kepabeanan/lartas/pengiriman/prinsip/lainlain
            $table->string('service_type', 12)->default('all');       // import/export/domestic/all
            $table->string('mode', 12)->nullable();                   // sea/air/land/all/null
            $table->string('level', 12)->default('shipment');         // shipment/profil
            $table->string('responsibility', 12)->default('customer'); // customer/internal (default, bukan pengunci)
            $table->string('conditional', 14)->default('selalu');     // selalu/kondisional/situasional/opsional
            $table->boolean('is_status_trigger')->default(false);     // memicu auto-status (getDocumentTriggers)
            $table->boolean('is_payment_obligation')->default(false); // dokumen kewajiban bayar (SPTNP/billing/dll)
            $table->boolean('has_expiry')->default(false);            // punya masa berlaku
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['service_type', 'category']);
            $table->index('doc_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
