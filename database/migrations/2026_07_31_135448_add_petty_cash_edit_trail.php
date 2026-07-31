<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak perubahan transaksi kas kecil + penanda pembatalan.
 *
 * Kas kecil dipegang satu orang dengan approver terpisah. Begitu transaksinya
 * bisa diubah, perubahan tanpa jejak justru melemahkan kontrol yang selama ini
 * ada — jadi jejaknya dibuat bersamaan dengan fitur editnya, bukan menyusul.
 *
 * Tab "Log Perubahan" yang sudah ada hanya mencatat perubahan PENGATURAN
 * (plafon, pemegang kas), bukan transaksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('petty_cash_transaction_logs')) {
            Schema::create('petty_cash_transaction_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('petty_cash_transaction_id')
                    ->constrained('petty_cash_transactions')
                    ->cascadeOnDelete();

                // diubah | dibatalkan
                $table->string('action', 20)->index();

                // Peta perubahan: {"amount": {"dari": 45000, "ke": 50000}, ...}
                $table->json('changes')->nullable();

                $table->string('reason', 500)->nullable();

                // Tanpa foreign key: staf bisa dinonaktifkan belakangan, dan
                // jejak perubahannya tidak boleh ikut hilang.
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->string('changed_by_name')->nullable();

                $table->timestamps();
            });
        }

        if (Schema::hasTable('petty_cash_transactions')) {
            Schema::table('petty_cash_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('petty_cash_transactions', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable();
                }
                if (! Schema::hasColumn('petty_cash_transactions', 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable();
                }
                // Jurnal balik yang meniadakan efek transaksi ini di buku besar.
                if (! Schema::hasColumn('petty_cash_transactions', 'reversal_journal_id')) {
                    $table->unsignedBigInteger('reversal_journal_id')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_transaction_logs');

        if (Schema::hasTable('petty_cash_transactions')) {
            Schema::table('petty_cash_transactions', function (Blueprint $table) {
                foreach (['cancelled_at', 'cancelled_by', 'reversal_journal_id'] as $kolom) {
                    if (Schema::hasColumn('petty_cash_transactions', $kolom)) {
                        $table->dropColumn($kolom);
                    }
                }
            });
        }
    }
};
