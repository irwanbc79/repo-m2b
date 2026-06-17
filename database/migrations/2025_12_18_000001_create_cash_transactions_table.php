<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('type', 20); // receipt or payment
            $table->decimal('amount', 15, 2);
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('counter_account_id')->nullable()->constrained('accounts');
            $table->text('description')->nullable();
            $table->string('proof_file', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
