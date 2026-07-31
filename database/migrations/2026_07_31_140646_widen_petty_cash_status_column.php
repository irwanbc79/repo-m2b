<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lebarkan `petty_cash_transactions.status` dari enum ke VARCHAR.
 *
 * Enum lama hanya memuat pending/approved/rejected, sehingga status
 * `cancelled` (pembatalan transaksi) ditolak database. Ini persis pola yang
 * dulu menggigit `shipments.status`: enum sempit membuat status baru gagal
 * disimpan, dan kegagalannya mudah lolos tanpa disadari.
 *
 * Dibuat sadar-engine: MySQL (production) diubah lewat SQL langsung karena
 * lebih dapat diandalkan untuk enum, SQLite (test) lewat Schema::change().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('petty_cash_transactions')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE petty_cash_transactions MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('petty_cash_transactions')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE petty_cash_transactions MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
