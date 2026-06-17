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
        if (\DB::getDriverName() === 'mysql') {
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
        } else {
            // SQLite fallback using PHP's sha1()
            $rows = DB::table('bank_transactions')->whereNull('transaction_hash')->get();
            foreach ($rows as $row) {
                $hashString = implode('|', [
                    $row->bank_name ?? '',
                    $row->transaction_date ? date('Y-m-d', strtotime($row->transaction_date)) : '',
                    $row->credit_amount ?? 0,
                    $row->debit_amount ?? 0,
                    $row->description ?? '',
                ]);
                DB::table('bank_transactions')
                    ->where('id', $row->id)
                    ->update(['transaction_hash' => sha1($hashString)]);
            }
        }

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
