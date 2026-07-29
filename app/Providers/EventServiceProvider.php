<?php

namespace App\Providers;

use App\Events\ShipmentStatusUpdated;
use App\Listeners\SendStatusNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ShipmentStatusUpdated::class => [
            SendStatusNotification::class,
        ],

        // CATATAN: RecordEmailDelivery (MessageSending) TIDAK didaftarkan di
        // sini. Laravel 11/12 sudah otomatis menemukan listener di
        // app/Listeners yang meng-hint tipe event-nya di method handle();
        // mendaftarkannya lagi di sini membuat listener menyala DUA KALI dan
        // setiap email tercatat dobel di buku besar.
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}