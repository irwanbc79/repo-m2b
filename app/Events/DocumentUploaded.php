<?php

namespace App\Events;

use App\Models\Document;
use App\Models\Shipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Document $document;
    public Shipment $shipment;

    /**
     * Create a new event instance.
     */
    public function __construct(Document $document, Shipment $shipment)
    {
        $this->document = $document;
        $this->shipment = $shipment;
    }
}
