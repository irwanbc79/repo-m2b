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
        Schema::table('vendors', function (Blueprint $table) {
            // FIX 1: Tambahkan kolom npwp jika belum ada
            if (!Schema::hasColumn('vendors', 'npwp')) {
                $table->string('npwp', 20)->nullable()->after('bank_details');
            }
            
            // FIX 2: Tambahkan kolom website jika belum ada
            if (!Schema::hasColumn('vendors', 'website')) {
                $table->string('website')->nullable()->after('npwp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'website')) {
                $table->dropColumn('website');
            }
            if (Schema::hasColumn('vendors', 'npwp')) {
                $table->dropColumn('npwp');
            }
        });
    }
};