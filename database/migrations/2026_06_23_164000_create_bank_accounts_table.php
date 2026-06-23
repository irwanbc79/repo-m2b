<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 100);
            $table->string('account_number', 50)->unique();
            $table->string('account_holder', 100);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Seed initial default accounts
        DB::table('bank_accounts')->insert([
            [
                'bank_name' => 'PT BANK MANDIRI (Persero) Tbk',
                'account_number' => '106-00-5598889-6',
                'account_holder' => 'PT. MORA MULTI BERKAH',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bank_name' => 'PT BANK MANDIRI (Persero) Tbk',
                'account_number' => '106-00164.19-775',
                'account_holder' => 'Eka Mayang Sari Harahap',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
