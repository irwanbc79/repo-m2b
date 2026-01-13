<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 10);
            $table->string('currency_name', 100);
            $table->decimal('rate', 15, 4);
            $table->date('valid_from');
            $table->date('valid_until');
            $table->timestamps();

            $table->unique(['currency_code', 'valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_exchange_rates');
    }
};
