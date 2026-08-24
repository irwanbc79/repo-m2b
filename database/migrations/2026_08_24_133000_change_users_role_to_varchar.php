<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kolom `users.role` awalnya didefinisikan sebagai ENUM ('admin', 'customer', 'employee')
 * atau ('admin', 'manager', 'field_officer', 'staff').
 *
 * Saat user diberi role baru seperti `konsultan_pajak`, `super_admin`, `director`, dsb.,
 * MySQL strict mode melempar QueryException "1265 Data truncated for column 'role'".
 *
 * Migrasi ini mengubah tipe kolom `users.role` menjadi VARCHAR(50) nullable
 * agar kompatibel dengan seluruh role dinamis yang terdaftar di config/permissions.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(50) NULL DEFAULT 'staff'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE `users` SET `role` = 'staff' WHERE `role` NOT IN ('admin', 'manager', 'field_officer', 'staff', 'customer', 'employee')");
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin', 'manager', 'field_officer', 'staff', 'customer', 'employee') NULL DEFAULT 'staff'");
    }
};
