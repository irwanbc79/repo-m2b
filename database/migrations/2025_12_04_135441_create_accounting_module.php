<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. TABEL AKUN (MASTER)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 1101
            $table->string('name'); // Kas Besar
            $table->enum('type', [
                'kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap', 
                'hutang_lancar', 'hutang_jangka_panjang', 'modal', 
                'pendapatan', 'beban_pokok', 'beban_operasional', 'beban_lain'
            ]);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamps();
        });

        // 2. TABEL JURNAL HEADER (TRANSAKSI)
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number')->unique(); // JR-202512-001
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->string('reference_no')->nullable();
            $table->enum('status', ['draft', 'posted'])->default('posted');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 3. TABEL JURNAL ITEMS (DETAIL DEBIT KREDIT)
        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            // Kunci tamu ke journals (Parent harus ada dulu, makanya urutan di file ini penting)
            $table->foreignId('journal_id')->constrained('journals')->onDelete('cascade');
            
            // Kunci tamu ke accounts
            $table->foreignId('account_id')->constrained('accounts');
            
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        // Urutan hapus harus dibalik (Anak dulu, baru Bapak)
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
    }
};