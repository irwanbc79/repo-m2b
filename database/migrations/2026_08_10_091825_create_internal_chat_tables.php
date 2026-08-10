<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat internal antar tim M2B.
 *
 * `conversation_key` adalah kunci percakapan yang sudah dinormalkan:
 *   - 'all'          → obrolan seluruh peserta
 *   - 'dm:13-43'     → japri, id kecil dulu supaya kedua arah bertemu di
 *                      kunci yang SAMA (tanpa ini, pesan A→B dan B→A akan
 *                      dianggap dua percakapan berbeda)
 *
 * `sender_name` disalin, bukan hanya id, supaya riwayat tetap terbaca kalau
 * akun stafnya dinonaktifkan atau namanya berubah.
 *
 * Pesan dibersihkan otomatis setelah 90 hari (kecuali yang disematkan) —
 * tabel `emails` di portal ini sudah membuktikan apa yang terjadi kalau data
 * pesan dibiarkan tumbuh tanpa pengelola: 60% isi database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('internal_messages')) {
            Schema::create('internal_messages', function (Blueprint $table) {
                $table->id();

                $table->string('conversation_key', 40)->index();
                $table->string('scope', 10)->default('all'); // all | dm

                // Tanpa foreign key: staf bisa dinonaktifkan, jejak percakapan
                // tidak boleh ikut terhapus.
                $table->unsignedBigInteger('sender_id')->index();
                $table->string('sender_name');
                $table->unsignedBigInteger('recipient_id')->nullable()->index();

                $table->text('body');
                $table->boolean('is_pinned')->default(false)->index();

                $table->timestamps();

                // Query utama: ambil pesan satu percakapan, terbaru dulu.
                $table->index(['conversation_key', 'id']);
            });
        }

        if (! Schema::hasTable('internal_message_reads')) {
            Schema::create('internal_message_reads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('conversation_key', 40);
                $table->unsignedBigInteger('last_read_message_id')->default(0);
                $table->timestamps();

                // Satu baris per orang per percakapan.
                $table->unique(['user_id', 'conversation_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_message_reads');
        Schema::dropIfExists('internal_messages');
    }
};
