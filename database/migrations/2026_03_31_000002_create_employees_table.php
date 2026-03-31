<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('nik', 50)->unique();
            $table->string('nama');
            $table->unsignedBigInteger('jabatan_id');
            $table->foreign('jabatan_id')->references('id')->on('jabatan');
            $table->date('join_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('employment_type', ['permanent', 'contract'])->default('permanent')
                ->comment('permanent=PKWTT (Karyawan Tetap), contract=PKWT (Karyawan Kontrak)');
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
