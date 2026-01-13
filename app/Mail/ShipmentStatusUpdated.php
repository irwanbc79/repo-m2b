<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Shipment;

class ShipmentStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $shipment;

    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update Status Pengiriman - ' . $this->shipment->awb_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shipment-updated',
        );
    }
}