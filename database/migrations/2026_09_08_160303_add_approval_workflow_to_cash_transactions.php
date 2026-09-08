<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 — maker/checker kasir.
 *
 * Transaksi kasir tidak lagi langsung masuk buku. Kasir menyimpan → status
 * 'pending'; jurnal baru dibentuk saat accounting menyetujui.
 *
 * Seluruh transaksi LAMA di-backfill jadi 'approved' supaya riwayat yang sudah
 * terlanjur ter-posting tidak tiba-tiba muncul di antrian verifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            // after() dipasang hanya kalau kolom acuannya benar-benar ada —
            // schema produksi punya riwayat drift, dan after() ke kolom yang
            // tidak ada bikin migrasi gagal di tengah jalan.
            $after = fn (string $column) => Schema::hasColumn('cash_transactions', $column) ? $column : null;

            if (! Schema::hasColumn('cash_transactions', 'approval_status')) {
                $column = $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
                if ($ref = $after('is_posted')) {
                    $column->after($ref);
                }
                $table->index('approval_status');
            }
            if (! Schema::hasColumn('cash_transactions', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('cash_transactions', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('cash_transactions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('cash_transactions', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });

        // Backfill: semua transaksi yang sudah ada dianggap sudah diverifikasi.
        // posted_at dipakai sebagai waktu persetujuan bila ada, kalau tidak
        // pakai created_at supaya kolomnya tidak kosong.
        DB::table('cash_transactions')->update([
            'approval_status' => 'approved',
            'submitted_at'    => DB::raw('created_at'),
            'approved_at'     => DB::raw('COALESCE(posted_at, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_transactions', 'approval_status')) {
                $table->dropIndex(['approval_status']);
                $table->dropColumn('approval_status');
            }
            foreach (['submitted_at', 'approved_by', 'approved_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('cash_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
