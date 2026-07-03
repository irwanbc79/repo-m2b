<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Di production ada index server-local `uniq_cash_invoice` (UNIQUE pada
 * invoice_id, tidak pernah tercatat di migration). Akibatnya satu invoice
 * maksimal punya SATU cash transaction — cicilan ke-2 dst gagal membuat
 * cash transaction secara diam-diam (tertelan try/catch di InvoiceManager).
 *
 * Ganti dengan UNIQUE pada invoice_payment_id: proteksi anti-dobel tepat
 * sasaran per payment, dan nilai NULL boleh berulang sehingga entry manual
 * kasir (tanpa payment record) tidak terganggu.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL menolak drop index yang menopang FK invoice_id — sediakan
        // index biasa sebagai pengganti DULU, baru drop yang unique.
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (!Schema::hasIndex('cash_transactions', 'idx_cash_invoice')) {
                $table->index('invoice_id', 'idx_cash_invoice');
            }
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            if (Schema::hasIndex('cash_transactions', 'uniq_cash_invoice')) {
                $table->dropUnique('uniq_cash_invoice');
            }
            if (!Schema::hasIndex('cash_transactions', 'uniq_cash_invoice_payment')) {
                $table->unique('invoice_payment_id', 'uniq_cash_invoice_payment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (Schema::hasIndex('cash_transactions', 'uniq_cash_invoice_payment')) {
                $table->dropUnique('uniq_cash_invoice_payment');
            }
            // uniq_cash_invoice sengaja TIDAK dikembalikan — index itu sumber
            // bug cicilan dan tidak pernah menjadi bagian skema resmi.
        });
    }
};
