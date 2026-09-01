<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan kolom journal_id ke tabel bank_transactions
        if (Schema::hasTable('bank_transactions') && ! Schema::hasColumn('bank_transactions', 'journal_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->foreignId('journal_id')
                    ->nullable()
                    ->after('invoice_payment_id')
                    ->constrained('journals')
                    ->onDelete('set null');
                
                $table->index('journal_id');
            });
        }

        // 2. Pastikan akun-akun standar COA untuk perbankan & operasional tersedia
        $standardAccounts = [
            [
                'code' => '1101',
                'name' => 'Bank Mandiri',
                'type' => 'kas_bank',
            ],
            [
                'code' => '1103',
                'name' => 'Bank BCA',
                'type' => 'kas_bank',
            ],
            [
                'code' => '5101',
                'name' => 'Beban Gaji & Upah',
                'type' => 'beban_operasional',
            ],
            [
                'code' => '2103',
                'name' => 'Hutang Titipan & Jaminan Pelanggan',
                'type' => 'hutang_lancar',
            ],
            [
                'code' => '2104',
                'name' => 'Kelebihan Pembayaran / Refund',
                'type' => 'hutang_lancar',
            ],
            [
                'code' => '6203',
                'name' => 'Beban Operasional Lapangan & Trucking',
                'type' => 'beban_operasional',
            ],
            [
                'code' => '6207',
                'name' => 'Beban Pajak Bunga Bank',
                'type' => 'beban_lain',
            ],
            [
                'code' => '6208',
                'name' => 'Beban Administrasi Bank',
                'type' => 'beban_lain',
            ],
            [
                'code' => '7101',
                'name' => 'Pendapatan Bunga Bank',
                'type' => 'pendapatan',
            ],
        ];

        foreach ($standardAccounts as $acc) {
            Account::firstOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'opening_balance' => 0,
                    'current_balance' => 0,
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_transactions') && Schema::hasColumn('bank_transactions', 'journal_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('journal_id');
            });
        }
    }
};
