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
        DB::table('bank_accounts')
            ->where('account_number', '106-00-5598809-6')
            ->update([
                'account_number' => '106-00-5598889-6',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('bank_accounts')
            ->where('account_number', '106-00-5598889-6')
            ->update([
                'account_number' => '106-00-5598809-6',
                'updated_at' => now(),
            ]);
    }
};
