<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambal schema drift: kolom journals.posted_at sudah ada di database
 * production (ditambahkan manual) dan dipakai CashierService::createJournal,
 * tapi tidak pernah tercatat di migration — sehingga environment baru
 * (termasuk test DB) tidak punya kolom ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (!Schema::hasColumn('journals', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
        });
    }
};
