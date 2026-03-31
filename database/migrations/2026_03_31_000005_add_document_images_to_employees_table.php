<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('alamat')
                ->comment('Pas foto karyawan untuk ID Card (resized JPEG, portrait)');
            $table->string('ktp_image')->nullable()->after('photo')
                ->comment('Scan/foto KTP (resized JPEG)');
            $table->string('bpjs_image')->nullable()->after('ktp_image')
                ->comment('Scan/foto kartu BPJS (resized JPEG)');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['photo', 'ktp_image', 'bpjs_image']);
        });
    }
};
