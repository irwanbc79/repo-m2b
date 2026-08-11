<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Komoditi & rekomendasi HS Code per quotation.
 *
 * Sengaja tabel TERSENDIRI, bukan kolom teks di `quotations`, dan multi-baris
 * sejak awal walau tampilannya mulai dari satu baris — quotation forwarding
 * cepat sekali berkembang menangani banyak jenis barang, dan memecah teks
 * bebas jadi baris terstruktur belakangan berarti membedah data yang sudah
 * dipakai pelanggan.
 *
 * CATATAN: `quotation_items` yang sudah ada adalah baris BIAYA (qty/price/
 * total), bukan komoditi. Dua hal berbeda, sengaja tidak digabung.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotation_commodities')) {
            Schema::create('quotation_commodities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
                $table->unsignedInteger('sort_order')->default(0);

                $table->string('commodity', 200);

                // Bentuk baku BTKI yang bertitik: 1234 / 1234.56 / 1234.56.78.
                // Panjang 12 mengikuti shipments.hs_code supaya nilainya bisa
                // disalin apa adanya saat quotation dikonversi.
                $table->string('hs_code', 12)->nullable();

                // Uraian resmi DISALIN saat disimpan, bukan di-join saat cetak.
                // Data BTKI bisa diperbarui/diimpor ulang; quotation yang sudah
                // dikirim ke pelanggan harus tetap bisa dicetak ulang persis
                // seperti saat disetujui.
                $table->string('hs_description_id', 500)->nullable();
                $table->string('hs_description_en', 500)->nullable();

                // false = kode tidak ditemukan di BTKI saat disimpan. Disimpan
                // sebagai fakta, bukan penghalang: HS Code di sini berlabel
                // rekomendasi, dan data BTKI bisa tertinggal dari tarif baru.
                $table->boolean('found_in_btki')->default(false);

                $table->timestamps();

                $table->index(['quotation_id', 'sort_order']);
                $table->index('hs_code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_commodities');
    }
};
