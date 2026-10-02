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
        Schema::create('shipment_compliance_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('audited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 32)->default('gemini');
            $table->string('model', 64)->default('gemini-2.5-flash');
            $table->string('overall_status', 32)->default('COMPLIANT'); // COMPLIANT, ATTENTION, CRITICAL
            $table->unsignedTinyInteger('compliance_score')->default(100);
            $table->text('summary')->nullable();
            $table->json('findings')->nullable(); // array of findings with resolution notes
            $table->json('document_snapshots')->nullable(); // list of documents analyzed (id, name, type)
            $table->json('token_usage')->nullable(); // prompt_tokens, candidate_tokens, est_idr
            $table->text('disclaimer')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_compliance_audits');
    }
};
