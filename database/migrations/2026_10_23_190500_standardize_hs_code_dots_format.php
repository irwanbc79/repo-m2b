<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\HsCode;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Standardize hs_code and parent_code to:
        // 4-digit: xxxx (tanpa titik, cth: 8501)
        // 6-digit: xxxx.xx (cth: 8501.10)
        // 8-digit: xxxx.xx.xx (cth: 8501.10.21)
        $codes = DB::table('hs_codes')->select('id', 'hs_code', 'parent_code', 'hs_level')->get();

        foreach ($codes as $item) {
            $newHs = HsCode::formatDisplay($item->hs_code);
            $newParent = !empty($item->parent_code) ? HsCode::formatDisplay($item->parent_code) : $item->parent_code;

            if ($newHs !== $item->hs_code || $newParent !== $item->parent_code) {
                DB::table('hs_codes')->where('id', $item->id)->update([
                    'hs_code' => $newHs,
                    'parent_code' => $newParent,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible
    }
};
