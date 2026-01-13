<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Vendor; // Pastikan model Vendor sudah di-import

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. TAMBAHKAN TABEL BARU (VendorContacts)
        if (!Schema::hasTable('vendor_contacts')) {
            Schema::create('vendor_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
                $table->string('pic_name', 100);
                $table->string('phone', 30)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('role', 50)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        // 2. PINDAHKAN DATA DARI KOLOM LAMA KE TABEL BARU
        // Karena Bapak hanya punya 4 vendor, kita pindahkan data kontak PIC utamanya.
        $vendors = DB::table('vendors')
                     ->whereNotNull('pic_name')
                     ->get();

        foreach ($vendors as $vendor) {
            // Cek apakah data ini sudah dimigrasikan sebelumnya (jika ada)
            $existing = DB::table('vendor_contacts')
                          ->where('vendor_id', $vendor->id)
                          ->where('is_primary', true)
                          ->first();

            if (!$existing) {
                DB::table('vendor_contacts')->insert([
                    'vendor_id' => $vendor->id,
                    'pic_name' => $vendor->pic_name,
                    'phone' => $vendor->phone,
                    'email' => $vendor->email,
                    'role' => 'PIC Utama (Migrasi)',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. HAPUS KOLOM LAMA DI TABEL VENDORS
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['pic_name', 'phone', 'email']);
        });
    }

    /**
     * Reverse the migrations (Opsional, untuk rollback).
     */
    public function down(): void
    {
        // Mengembalikan kolom pic_name, phone, email ke tabel vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('pic_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
        });

        // Menghapus tabel vendor_contacts
        Schema::dropIfExists('vendor_contacts');
    }
};