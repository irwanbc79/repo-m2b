<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambal drift: `shipments.quotation_id` ADA di production tapi tidak pernah
 * dibuat oleh migrasi mana pun — ditambahkan langsung ke database.
 *
 * Akibatnya `QuotationManager::convertToShipment()` menulis ke kolom yang,
 * menurut migrasi, tidak ada. Di production aman karena kolomnya memang ada;
 * di lingkungan yang dibangun ulang dari migrasi (termasuk test) konversi
 * quotation ke shipment langsung gagal total. Ketahuan saat menulis test
 * pertama untuk konversi itu.
 *
 * Bergaris pengaman `Schema::hasColumn`, jadi di production migrasi ini tidak
 * melakukan apa-apa dan hanya menyamakan catatan migrasi dengan kenyataan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipments') && ! Schema::hasColumn('shipments', 'quotation_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->unsignedBigInteger('quotation_id')->nullable()->after('customer_id');
                $table->index('quotation_id');
            });
        }
    }

    public function down(): void
    {
        // Sengaja TIDAK menghapus kolomnya. Kolom ini sudah dipakai data
        // production sejak sebelum migrasi ini ada; rollback yang menghapusnya
        // akan membuang relasi quotation→shipment yang nyata.
    }
};
