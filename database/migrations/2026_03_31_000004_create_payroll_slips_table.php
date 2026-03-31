<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->unsignedBigInteger('period_id');
            $table->foreign('period_id')->references('id')->on('payroll_periods')->cascadeOnDelete();
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan_transport', 15, 2)->default(0);
            $table->decimal('tunjangan_jabatan', 15, 2)->default(0);
            $table->decimal('tunjangan_lainnya', 15, 2)->default(0);
            $table->decimal('lembur_jam', 8, 2)->default(0);
            $table->decimal('lembur_nominal', 15, 2)->default(0);
            $table->decimal('potongan_bpjs_kes', 15, 2)->default(0);
            $table->decimal('potongan_bpjs_tk', 15, 2)->default(0);
            $table->decimal('potongan_lainnya', 15, 2)->default(0);
            $table->decimal('total_gaji', 15, 2)->default(0)->comment('Computed: stored for performance');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_id'], 'unique_slip_per_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_slips');
    }
};
