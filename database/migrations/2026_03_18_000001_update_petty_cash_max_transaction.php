<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing petty cash funds that still use the old 250,000 limit
        DB::table('petty_cash_funds')
            ->where('max_transaction', 250000)
            ->update(['max_transaction' => 750000]);
    }

    public function down(): void
    {
        DB::table('petty_cash_funds')
            ->where('max_transaction', 750000)
            ->update(['max_transaction' => 250000]);
    }
};
