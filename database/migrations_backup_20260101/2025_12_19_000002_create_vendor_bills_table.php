<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Vendor Bills untuk track tagihan vendor per shipment
     */
    public function up(): void
    {
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            
            // Bill info
            $table->string('bill_number', 100)->unique();
            $table->date('bill_date');
            $table->date('due_date');
            
            // Payment terms
            $table->enum('payment_terms', ['cod', '7_days', '14_days', '30_days', 'custom'])->default('cod');
            $table->integer('custom_days')->nullable()->comment('Untuk payment terms custom');
            
            // Amount
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->decimal('exchange_rate', 12, 4)->nullable();
            $table->decimal('amount_idr', 15, 2)->nullable();
            
            // Payment tracking
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'cancelled'])->default('unpaid');
            $table->date('paid_date')->nullable();
            
            // Cost category for job costing
            $table->enum('cost_category', [
                'freight', 
                'trucking', 
                'customs', 
                'documentation', 
                'handling',
                'insurance',
                'other'
            ])->default('other');
            
            // Description & notes
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            
            // Attachment
            $table->string('attachment_path')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('bill_number');
            $table->index('status');
            $table->index('due_date');
            $table->index(['shipment_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bills');
    }
};
