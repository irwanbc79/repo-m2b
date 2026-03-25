<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master seeder untuk import data legacy dari Accurate Accounting.
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=OldDataImportSeeder
 *
 * AMAN: semua sub-seeder menggunakan duplicate detection.
 * Tidak akan mengubah data yang sudah ada di portal.
 */
class OldDataImportSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== Import Data Legacy dari Accurate Accounting ===');
        $this->command->info('');

        $this->command->info('[1/3] Importing customers...');
        $this->call(OldDataCustomerSeeder::class);

        $this->command->info('[2/3] Importing vendors...');
        $this->call(OldDataVendorSeeder::class);

        $this->command->info('[3/3] Importing Chart of Accounts...');
        $this->call(OldDataCoaSeeder::class);

        $this->command->info('[4/4] Importing Historical Financial Snapshots (2021-2025)...');
        $this->call(OldDataHistoricalSeeder::class);

        $this->command->info('');
        $this->command->info('=== Import selesai. Data existing tidak berubah. ===');
    }
}
