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
        Schema::create('shipment_eta_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->dateTime('previous_eta')->nullable();
            $table->dateTime('revised_eta');
            $table->integer('change_days')->default(0);
            $table->string('reason_code', 50);
            $table->text('reason_notes')->nullable();
            $table->string('source_party', 150)->nullable();
            $table->dateTime('information_received_at');
            $table->foreignId('source_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->boolean('customer_visible')->default(false);
            $table->dateTime('customer_notified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shipment_id', 'created_at']);
            $table->index(['reason_code', 'revised_eta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_eta_revisions');
    }
};
