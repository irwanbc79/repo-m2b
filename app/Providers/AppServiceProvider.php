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
        // Override Livewire update route BEFORE LivewireServiceProvider::boot() sets the default.
        // Must run at register() time so the guard (if !$this->updateRoute) is hit first.
        $this->app->resolving(
            \Livewire\Mechanisms\HandleRequests\HandleRequests::class,
            function ($handleRequests) {
                $handleRequests->setUpdateRoute(function ($handle) {
                    return \Illuminate\Support\Facades\Route::post('/lw-update', $handle)->middleware('web');
                });
            }
        );
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        Shipment::observe(ShipmentObserver::class);
    }
}