<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 — kategori biaya terstruktur + catatan pengecualian bukti.
 *
 * expense_category sengaja string biasa (bukan enum) supaya daftar kategorinya
 * bisa ditambah lewat config/cashier.php tanpa migrasi baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_transactions', 'expense_category')) {
                $table->string('expense_category', 50)->nullable()->after('cost_category');
                $table->index('expense_category');
            }
            if (! Schema::hasColumn('cash_transactions', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_transactions', 'expense_category')) {
                $table->dropIndex(['expense_category']);
                $table->dropColumn('expense_category');
            }
            if (Schema::hasColumn('cash_transactions', 'approval_note')) {
                $table->dropColumn('approval_note');
            }
        });
    }
};
