<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_deduction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_slip_id');
            $table->foreign('payroll_slip_id')->references('id')->on('payroll_slips')->cascadeOnDelete();
            $table->string('nama_potongan');   // e.g. "Kasbon Nov", "Absen 2 hari", "PPh 21"
            $table->decimal('nominal', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_deduction_items');
    }
};
