<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "email customer ini mental".
 *
 * Sengaja disimpan sebagai KOLOM, bukan dihitung saat dibutuhkan: halaman
 * Manage Customers memanggil dataQuality() untuk setiap baris, jadi query
 * per pemanggilan akan langsung jadi N+1.
 *
 * Diisi saat peristiwa mental masuk, dan dikosongkan lagi begitu ada email
 * yang berhasil sampai ke alamat yang sama — jadi penandanya sembuh sendiri
 * ketika staf memperbaiki alamatnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'email_bounced_at')) {
                $table->timestamp('email_bounced_at')->nullable()->index();
            }
            if (! Schema::hasColumn('customers', 'email_bounce_reason')) {
                $table->string('email_bounce_reason', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            foreach (['email_bounced_at', 'email_bounce_reason'] as $kolom) {
                if (Schema::hasColumn('customers', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
