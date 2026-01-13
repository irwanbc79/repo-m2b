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
            // Menambahkan kolom NPWP jika belum ada
            if (!Schema::hasColumn('customers', 'npwp')) {
                $table->string('npwp', 30)->nullable()->after('phone');
            }

            // Menambahkan kolom Alamat Pajak jika belum ada
            if (!Schema::hasColumn('customers', 'tax_address')) {
                $table->text('tax_address')->nullable()->after('npwp');
            }

            // Menambahkan kolom Alamat Gudang jika belum ada
            if (!Schema::hasColumn('customers', 'warehouse_address')) {
                $table->text('warehouse_address')->nullable()->after('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['npwp', 'tax_address', 'warehouse_address']);
        });
    }
};