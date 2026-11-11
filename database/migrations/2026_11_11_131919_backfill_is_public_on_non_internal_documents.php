<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Semua dokumen yang bukan internal (is_internal = false) adalah dokumen publik
        // untuk customer pemilik shipment tersebut.
        DB::table('documents')
            ->where('is_internal', false)
            ->update(['is_public' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu membalikkan status karena is_public = true konsisten dengan is_internal = false
    }
};
