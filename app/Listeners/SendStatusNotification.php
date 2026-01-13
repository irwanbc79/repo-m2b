<?php

namespace App\Listeners;

use App\Events\ShipmentStatusUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendStatusNotification implements ShouldQueue
{
    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ShipmentStatusUpdated $event): void
    {
        $this->notificationService->notifyShipmentStatusUpdate($event->shipment);
    }
}
