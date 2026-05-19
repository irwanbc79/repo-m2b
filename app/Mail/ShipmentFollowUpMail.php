<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentFollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $testimonialToken,
        public string $forceLang = ''
    ) {
        $this->invoice = $invoice->load(['customer.user', 'shipment']);
    }

    public function build()
    {
        $customer = $this->invoice->customer;
        $shipment = $this->invoice->shipment;
        $lang     = $this->forceLang ?: ($customer->preferred_language ?? 'id');

        $ref = $shipment->awb_number ?? $this->invoice->invoice_number;

        $subject = $lang === 'en'
            ? "Thank You for Your Trust - {$ref}"
            : "Terima Kasih atas Kepercayaan Anda - {$ref}";

        return $this->subject($subject)
            ->view('emails.shipment-followup')
            ->with([
                'customerName'   => $customer->company_name ?? 'Valued Customer',
                'awbNumber'      => $shipment->awb_number ?? '-',
                'origin'         => $shipment->origin ?? '-',
                'destination'    => $shipment->destination ?? '-',
                'paymentDate'    => $this->invoice->payment_date?->format('d F Y') ?? '-',
                'invoiceNumber'  => $this->invoice->invoice_number,
                'surveyUrl'      => route('survey.public'),
                'testimonialUrl' => route('testimonial.form', ['token' => $this->testimonialToken]),
                'lang'           => $lang,
            ]);
    }
}
