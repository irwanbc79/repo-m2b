<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Panggil seeder idempotent yang sudah kita buat
        $this->call([
            \Database\Seeders\UserSeeder::class,
            // Tambahkan seeder lain di sini jika perlu, contoh:
            // \Database\Seeders\AnotherSeeder::class,
        ]);
    }
}
