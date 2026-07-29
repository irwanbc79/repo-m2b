<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat peristiwa per email: terkirim, sampai, dibuka, diklik, mental.
 *
 * Kenapa tabel terpisah dan bukan sekadar kolom penghitung di
 * `email_deliveries`: (1) `provider_event_id` yang unik membuat penarikan
 * log bisa dijalankan berulang tanpa menggandakan hitungan, dan (2) layar
 * riwayat komunikasi memang perlu menampilkan urutan peristiwanya, bukan
 * hanya angkanya.
 *
 * `email_delivery_id` boleh kosong: peristiwa yang belum cocok dengan
 * catatan pengiriman mana pun tetap disimpan supaya tidak hilang, lalu bisa
 * ditautkan belakangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_delivery_events')) {
            return;
        }

        Schema::create('email_delivery_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('email_delivery_id')->nullable()
                ->constrained('email_deliveries')->nullOnDelete();

            // Identitas peristiwa di sisi Kirim Email — kunci anti-dobel.
            $table->string('provider_event_id')->unique();
            $table->string('provider_message_guid')->nullable()->index();

            $table->string('event_type', 30)->index();
            $table->string('recipient')->nullable()->index();
            $table->string('subject', 500)->nullable();
            $table->timestamp('occurred_at')->index();

            // Keterangan mentah dari provider (mis. balasan SMTP, alasan mental).
            $table->text('detail')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_delivery_events');
    }
};
