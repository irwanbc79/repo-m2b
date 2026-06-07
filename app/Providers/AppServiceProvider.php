<?php

namespace App\Providers;

use App\Models\Shipment;
use App\Observers\ShipmentObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        Shipment::observe(ShipmentObserver::class);

        // Override Livewire update route to /lw-update after all providers boot
        // This must be done after LivewireServiceProvider has booted
        $this->app->booted(function () {
            try {
                $handleRequests = app(\Livewire\Mechanisms\HandleRequests\HandleRequests::class);
                $handleRequests->setUpdateRoute(function ($handle) {
                    return \Illuminate\Support\Facades\Route::post('/lw-update', $handle)->middleware('web');
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Could not override Livewire update route: ' . $e->getMessage());
            }
        });
    }
}