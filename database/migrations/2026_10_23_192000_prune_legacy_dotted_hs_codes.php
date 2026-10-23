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
        // Delete legacy duplicate rows formatted like '01.01' or '01.01.10'
        // where character 3 is a dot.
        DB::table('hs_codes')
            ->where('hs_code', 'LIKE', '%.%')
            ->whereRaw("SUBSTRING(hs_code, 3, 1) = '.'")
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
