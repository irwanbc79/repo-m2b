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
        Schema::table('customers', function (Blueprint $table) {
            // Defensif: kolom `phone` dipakai kode lama tapi tidak pernah dibuat
            // lewat migration (drift di production). Tambahkan bila belum ada.
            if (! Schema::hasColumn('customers', 'phone')) {
                $table->string('phone', 30)->nullable()->after('company_name');
            }
            // Jabatan / kapasitas pendaftar sebagai perwakilan perusahaan
            $table->string('position', 100)->nullable()->after('company_name');
            // Rencana layanan: import | export | both | domestic
            $table->string('trade_type', 20)->nullable()->after('business_type');
            // Deskripsi rencana / komoditas pengiriman
            $table->text('trade_plan')->nullable()->after('trade_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['position', 'trade_type', 'trade_plan']);
        });
    }
};
