<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
                $table->string('invoice_number', 50)->unique();
                $table->string('type', 20)->default('commercial');
                $table->decimal('dp_percentage', 5, 2)->default(0);
                $table->date('invoice_date');
                $table->date('due_date');
                $table->string('status', 20)->default('unpaid');
                $table->string('payment_proof', 255)->nullable();
                $table->date('payment_date')->nullable();
                
                // Financial columns
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('service_total', 15, 2)->default(0);
                $table->decimal('reimbursement_total', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('pph_amount', 15, 2)->default(0);
                $table->decimal('down_payment', 15, 2)->default(0);
                $table->decimal('grand_total', 15, 2)->default(0);
                $table->text('notes')->nullable();

                // Payment Claim columns
                $table->boolean('payment_claimed')->default(false);
                $table->string('claim_proof_path', 255)->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->text('claim_notes')->nullable();
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
