<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Shipment $shipment;

    /**
     * Create a new event instance.
     */
    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }
}
