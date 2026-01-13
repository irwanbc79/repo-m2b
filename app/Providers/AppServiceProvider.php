<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Paginator Style
        Paginator::useTailwind();

        // 2. REGISTER GOOGLE DRIVE DRIVER MANUAL
        try {
            
        } catch (\Exception $e) {
            // Silent error jika library belum siap
        }
    }
}