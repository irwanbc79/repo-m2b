<?php

namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDocumentUploadedNotification implements ShouldQueue
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
    public function handle(DocumentUploaded $event): void
    {
        $this->notificationService->notifyDocumentUploaded(
            $event->shipment,
            $event->document->filename
        );
    }
}
