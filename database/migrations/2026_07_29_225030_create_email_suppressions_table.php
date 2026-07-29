<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar alamat yang tidak boleh dikirimi lagi.
 *
 * Kenapa perlu: sebelum ini portal tetap mengirim ke alamat yang sudah
 * terbukti mental. Itu bukan sekadar sia-sia — server penerima menilai
 * pengirim yang berulang kali menembak alamat tidak ada sebagai perilaku
 * spam, dan reputasinya menular ke SELURUH email dari domain yang sama.
 * Jadi satu alamat mati yang terus dikirimi bisa menyeret email ke customer
 * lain masuk folder spam.
 *
 * Alamat dicatat saat peristiwa mental masuk, dan dihapus lagi begitu ada
 * email yang berhasil sampai ke alamat itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_suppressions')) {
            return;
        }

        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();

            // Disimpan huruf kecil semua; lookup terjadi di setiap pengiriman
            // jadi wajib lewat indeks unik.
            $table->string('email')->unique();

            // bounce = dari peristiwa pengiriman kita sendiri
            // provider = hasil tarikan daftar suppression Kirim Email
            // manual = ditandai staf
            $table->string('source', 20)->default('bounce')->index();
            $table->string('reason', 500)->nullable();
            $table->timestamp('suppressed_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
