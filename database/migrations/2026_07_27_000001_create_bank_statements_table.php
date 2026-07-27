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
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name')->default('MANDIRI');
            $table->string('account_number')->index();
            $table->dateTime('transaction_date')->index();
            $table->dateTime('booking_date')->nullable();
            
            // 'CR' = Credit / Uang Masuk, 'DB' = Debit / Uang Keluar
            $table->enum('type', ['CR', 'DB'])->default('CR')->index();
            
            $table->decimal('amount', 15, 2);
            $table->decimal('balance', 15, 2)->nullable();
            $table->text('description')->nullable();
            
            // Deduplikasi menggunakan reference_number unik dari Bank Mandiri
            $table->string('reference_number')->unique();
            
            $table->boolean('is_reconciled')->default(false)->index();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
