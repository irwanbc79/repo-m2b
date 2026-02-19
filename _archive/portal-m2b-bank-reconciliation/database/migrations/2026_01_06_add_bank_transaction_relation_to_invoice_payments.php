<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migration ini menambahkan relasi bankTransaction ke model InvoicePayment
     * Ini diperlukan untuk fitur rekonsiliasi bank
     */
    public function up(): void
    {
        // Tidak perlu menambah kolom ke invoice_payments karena
        // relasi sudah ada di bank_transactions (invoice_payment_id)
        // 
        // InvoicePayment memiliki relasi hasOne ke BankTransaction
        // melalui kolom invoice_payment_id di tabel bank_transactions
        
        // Migration ini hanya sebagai dokumentasi bahwa:
        // 1. Tabel bank_transactions sudah dibuat sebelumnya
        // 2. Kolom invoice_payment_id sudah ada di bank_transactions
        // 3. Relasi inverse perlu ditambahkan di model InvoicePayment
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse
    }
};
