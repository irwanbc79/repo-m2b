<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Testimonial;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentFollowUpMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public string $forceLang = '';

    public function __construct(Invoice $invoice, string $forceLang = '')
    {
        $this->invoice   = $invoice->load(['customer.user', 'shipment']);
        $this->forceLang = $forceLang;
    }

    public function build()
    {
        $customer = $this->invoice->customer;
        $shipment = $this->invoice->shipment;
        $lang     = $this->forceLang ?: ($customer->preferred_language ?? 'id');

        // Generate token testimoni sekali pakai
        $token = Testimonial::where('invoice_id', $this->invoice->id)->value('token');
        if (!$token) {
            $token = Testimonial::generateToken();
            Testimonial::create([
                'customer_id'  => $customer->id,
                'invoice_id'   => $this->invoice->id,
                'display_name' => $customer->company_name ?? '',
                'company_name' => $customer->company_name ?? '',
                'token'        => $token,
                'rating'       => 5,
                'content'      => '',
                'status'       => 'pending',
                'ip_address'   => request()->ip(),
            ]);
        }

        $subject = $lang === 'en'
            ? 'Thank You for Your Trust - ' . ($shipment->awb_number ?? $this->invoice->invoice_number)
            : 'Terima Kasih atas Kepercayaan Anda - ' . ($shipment->awb_number ?? $this->invoice->invoice_number);

        return $this->subject($subject)
            ->view('emails.shipment-followup')
            ->with([
                'customerName'    => $customer->company_name ?? 'Valued Customer',
                'awbNumber'       => $shipment->awb_number ?? '-',
                'origin'          => $shipment->origin ?? '-',
                'destination'     => $shipment->destination ?? '-',
                'paymentDate'     => $this->invoice->payment_date?->format('d F Y') ?? '-',
                'invoiceNumber'   => $this->invoice->invoice_number,
                'surveyUrl'       => route('survey.public'),
                'testimonialUrl'  => route('testimonial.form', ['token' => $token]),
                'lang'            => $lang,
            ]);
    }
}
