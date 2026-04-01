<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('surat_lamaran')->nullable()->after('bpjs_image')
                ->comment('Surat lamaran kerja (PDF/JPG)');
            $table->string('kartu_keluarga')->nullable()->after('surat_lamaran')
                ->comment('Scan kartu keluarga (PDF/JPG)');
            $table->string('ijazah')->nullable()->after('kartu_keluarga')
                ->comment('Scan ijazah terakhir (PDF/JPG)');
            $table->string('npwp_image')->nullable()->after('ijazah')
                ->comment('Scan NPWP (JPG/PNG)');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['surat_lamaran', 'kartu_keluarga', 'ijazah', 'npwp_image']);
        });
    }
};
