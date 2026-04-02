<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 🔑 AUTO SYNC EMAIL IMAP (SETIAP 1 MENIT)
        $schedule->command('email:sync')->everyMinute();

        // 🔒 AUTO DEACTIVATE CUSTOMER INACTIVE (SETIAP HARI JAM 00:00)
        $schedule->command('customers:deactivate-inactive')->daily();

        // 💌 FOLLOW-UP EMAIL 3 HARI SETELAH PAID (SETIAP HARI JAM 09:00)
        $schedule->command('emails:send-followup')->dailyAt('09:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
