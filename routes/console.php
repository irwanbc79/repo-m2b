<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
| Laravel 12 – Semua command & scheduler didefinisikan di sini
*/

// Command default Laravel
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =====================
// SCHEDULER PRODUCTION
// =====================

// 1. Fetch Kurs Pajak – Setiap hari jam 08:00 WIB
Schedule::command('kurs:fetch-pajak')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta');

// 2. Backup ke Backblaze – Setiap hari jam 02:00 WIB
Schedule::command('backup:drive')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta');

// 3. Clear cache – Seminggu sekali (opsional)
Schedule::command('cache:clear')
    ->weekly()
    ->timezone('Asia/Jakarta');

// 4. Cleanup Email Attachment (TTL) – Setiap hari jam 02:30 WIB
Schedule::command('email:cleanup')
    ->dailyAt('02:30')
    ->timezone('Asia/Jakarta');
