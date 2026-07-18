<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item checklist dokumen per shipment — inti fitur. Aditif; tidak menyentuh
 * tabel documents/shipments. FK shipment_id ke shipments (cascade delete).
 * Kolom "siapa" (requested_by/confirmed_by) sebagai bigint index (bukan FK keras)
 * agar aman lintas engine & tak mengunci penghapusan user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('doc_type', 100);
            $table->string('responsibility', 12)->default('customer'); // customer/internal
            $table->boolean('is_mandatory')->default(false);           // ditetapkan MANUSIA
            $table->string('status', 14)->default('pending');          // pending/requested/fulfilled/waived
            $table->string('source', 12)->default('manual');           // manual/preset/ai
            $table->text('note')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('fulfilled_document_id')->nullable(); // dokumen (documents.id) yg memenuhi
            $table->string('fulfilled_by_role', 12)->nullable();       // customer/admin
            $table->unsignedBigInteger('confirmed_by')->nullable();    // yg menetapkan wajib (audit)
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'status']);
            $table->index('fulfilled_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
