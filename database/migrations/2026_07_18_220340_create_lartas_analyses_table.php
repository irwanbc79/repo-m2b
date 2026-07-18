<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache hasil analisa AI Lartas (F4) per shipment.
 * AI hanya merekomendasikan; keputusan tetap manusia. Disimpan agar tidak
 * memanggil API berulang tiap render dan bisa ditampilkan (read-only) ke customer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lartas_analyses')) {
            return;
        }

        Schema::create('lartas_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('hs_code', 30)->nullable();
            $table->string('service_type', 20)->nullable();
            $table->string('commodity')->nullable();
            $table->json('recommendations')->nullable(); // array item rekomendasi
            $table->text('summary')->nullable();         // catatan umum AI
            $table->string('model', 60)->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'hs_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lartas_analyses');
    }
};
