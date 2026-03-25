<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->comment('Format: YYYY-MM (monthly) atau YYYY (annual)');
            $table->enum('period_type', ['monthly', 'annual']);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('cogs', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->string('source', 50)->default('accurate');
            $table->timestamps();

            $table->unique(['period', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_snapshots');
    }
};
