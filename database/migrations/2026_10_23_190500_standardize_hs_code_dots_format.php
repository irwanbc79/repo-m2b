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
        // 1. Prune or standardize any legacy 4-digit headings that have dots (e.g., '01.01' -> '0101')
        $dottedHeadings = DB::table('hs_codes')
            ->where('hs_level', 'heading')
            ->where('hs_code', 'LIKE', '%.%')
            ->get();

        foreach ($dottedHeadings as $item) {
            $clean = str_replace('.', '', $item->hs_code);
            $alreadyExists = DB::table('hs_codes')->where('hs_code', $clean)->where('id', '!=', $item->id)->exists();
            if ($alreadyExists) {
                DB::table('hs_codes')->where('id', $item->id)->delete();
            } else {
                DB::table('hs_codes')->where('id', $item->id)->update(['hs_code' => $clean]);
            }
        }

        // 2. Standardize parent_code references for 4-digit headings (e.g. '85.01' -> '8501')
        $dottedParents = DB::table('hs_codes')
            ->whereNotNull('parent_code')
            ->where('parent_code', 'LIKE', '%.%')
            ->get();

        foreach ($dottedParents as $item) {
            $digitsOnly = preg_replace('/[^0-9]/', '', (string)$item->parent_code);
            if (strlen($digitsOnly) === 4) {
                DB::table('hs_codes')->where('id', $item->id)->update([
                    'parent_code' => $digitsOnly,
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
