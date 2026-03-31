<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('bulan')->unsigned()->comment('1-12');
            $table->smallInteger('tahun')->unsigned();
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['bulan', 'tahun'], 'unique_payroll_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
