<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('transaction_hash', 64)->nullable()->after('import_batch');
        });

        // Backfill hash untuk semua record yang ada
        DB::statement("
            UPDATE bank_transactions
            SET transaction_hash = SHA1(CONCAT_WS('|',
                COALESCE(bank_name, ''),
                COALESCE(DATE(transaction_date), ''),
                COALESCE(credit_amount, 0),
                COALESCE(debit_amount, 0),
                COALESCE(description, '')
            ))
            WHERE transaction_hash IS NULL
        ");

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('transaction_hash', 64)->nullable(false)->change();
            $table->unique('transaction_hash');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropUnique(['transaction_hash']);
            $table->dropColumn('transaction_hash');
        });
    }
};
