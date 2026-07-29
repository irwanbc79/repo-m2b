<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penampung mentah peristiwa pengiriman dari Kirim Email.
 *
 * Fase 00: bentuk payload webhook mereka BELUM diketahui (tidak ada di
 * dokumentasi maupun di SDK), jadi seluruh badan permintaan disimpan apa adanya
 * di kolom `payload`. Kolom di atasnya hanya hasil ekstraksi best-effort bila
 * kuncinya kebetulan ada — semuanya nullable, tidak ada yang diandalkan.
 *
 * Tabel ini tetap terpakai setelah fase 00: peristiwa yang tidak cocok dengan
 * catatan pengiriman mana pun disimpan di sini supaya bisa ditelusuri, bukan
 * dibuang diam-diam.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_webhook_events')) {
            return;
        }

        Schema::create('email_webhook_events', function (Blueprint $table) {
            $table->id();

            // Ekstraksi best-effort — jangan diandalkan sebelum fase 00 selesai.
            $table->string('event_type', 50)->nullable()->index();
            $table->string('message_guid')->nullable()->index();
            $table->string('recipient')->nullable()->index();
            $table->string('subject')->nullable();

            // Sumber kebenaran fase 00.
            $table->longText('payload');

            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('process_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_webhook_events');
    }
};
