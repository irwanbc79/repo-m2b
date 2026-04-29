<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Verifies that the konsultan_pajak role is defined in config/permissions.php.
 *
 * This project uses a JSON-based role system (users.roles column), not Spatie.
 * There is no roles table — roles are string keys in the permissions config
 * and stored as JSON arrays on the users record.
 *
 * To create a konsultan_pajak user, run:
 *   php artisan konsultan:create "Nama" "email@example.com" "Password123!"
 */
class KonsultanPajakSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('permissions.roles');

        if (isset($config['konsultan_pajak'])) {
            $this->command->info('Role konsultan_pajak sudah terdefinisi di config/permissions.php.');
            $this->command->info('  Level  : ' . $config['konsultan_pajak']['level']);
            $this->command->info('  Akses  : ' . implode(', ', $config['konsultan_pajak']['permissions']));
        } else {
            $this->command->error(
                'Role konsultan_pajak TIDAK ditemukan di config/permissions.php. ' .
                'Pastikan entry sudah ditambahkan sebelum menjalankan seeder ini.'
            );
            return;
        }

        $this->command->info('KonsultanPajakSeeder selesai. Buat user via: php artisan konsultan:create');
    }
}
