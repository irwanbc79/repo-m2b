<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku besar SEMUA email keluar dari portal.
 *
 * Kenapa tabel sendiri, bukan menumpang `sent_emails`: tabel itu punya
 * `user_id` berelasi WAJIB karena memang dirancang untuk email yang diketik
 * staf dari inbox. Email sistem lahir dari cron dan tidak punya user, jadi
 * akan langsung gagal. Keduanya beda tugas dan sebaiknya tetap terpisah.
 *
 * Baris dibuat saat event `MessageSending` (SEBELUM email benar-benar keluar),
 * supaya pengiriman yang gagal di tengah jalan tetap meninggalkan jejak
 * mangkrak di status `queued` — bukan hilang tanpa kabar.
 *
 * Kolom pencocokan (recipient_email + subject + sent_at) dipakai menautkan
 * peristiwa dari Kirim Email ke baris ini, karena API mereka tidak mau
 * menerima nomor referensi titipan kita.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_deliveries')) {
            return;
        }

        Schema::create('email_deliveries', function (Blueprint $table) {
            $table->id();

            // ── Kunci pencocokan ────────────────────────────────────────
            $table->string('recipient_email')->index();
            $table->string('subject', 500)->nullable();
            $table->timestamp('sent_at')->index();

            // Diisi saat peristiwa pertama berhasil dicocokkan; sesudah itu
            // pencocokan berikutnya cukup lewat kolom ini.
            $table->string('provider_message_guid')->nullable()->index();

            // ── Tautan ke entitas bisnis ────────────────────────────────
            $table->nullableMorphs('related');
            $table->string('mailable_class')->nullable()->index();
            $table->string('mailer', 50)->nullable();

            // ── Perjalanan status ───────────────────────────────────────
            // Bergerak satu arah saja; lihat EmailDelivery::statusOrder().
            $table->string('status', 30)->default('queued')->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
    }
};
