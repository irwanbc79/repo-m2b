<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public Shipment $shipment;
    public string $lang;

    /**
     * Create a new message instance.
     */
    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment->load(['customer']);
        // Get language preference from customer, default 'id'
        $this->lang = $this->shipment->customer->preferred_language ?? 'id';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $latestStatus = $this->shipment->latestStatus()->first();
        
        // Dynamic subject based on language
        $subject = $this->lang === 'en' 
            ? 'Shipment Status Update - ' . $this->shipment->awb_number
            : 'Pembaruan Status Pengiriman - ' . $this->shipment->awb_number;

        return $this->subject($subject)
            ->view('emails.shipment-status-update')
            ->with([
                'customerName' => $this->shipment->customer->company_name ?? 'Valued Customer',
                'awbNumber' => $this->shipment->awb_number,
                'status' => $latestStatus->status ?? $this->shipment->status ?? 'Updated',
                'location' => $latestStatus->location ?? '-',
                'notes' => $latestStatus->notes ?? '',
                'trackingUrl' => route('customer.shipment.show', $this->shipment->id),
                'origin' => $this->shipment->origin ?? '-',
                'destination' => $this->shipment->destination ?? '-',
                'lang' => $this->lang,
            ]);
    }
}
