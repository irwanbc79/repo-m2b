<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 50); // 'mandiri', 'bca'
            $table->string('account_number', 50);
            $table->date('transaction_date');
            $table->text('description');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2);
            $table->string('reference_number')->nullable();
            
            // Reconciliation fields
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('invoice_payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('matched_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('matched_at')->nullable();
            $table->text('matching_notes')->nullable();
            
            // Import tracking
            $table->string('import_batch')->nullable();
            $table->timestamp('imported_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('bank_name');
            $table->index('transaction_date');
            $table->index('is_reconciled');
            $table->index('invoice_payment_id');
            $table->index(['bank_name', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
