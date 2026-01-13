<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use NumberFormatter;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quotation;

    public function __construct(Quotation $quotation)
    {
        $this->quotation = $quotation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            // PENGIRIM KHUSUS: Email tetap 'no_reply' (Wajib), tapi Nama jadi 'M2B Sales Dept'
            from: new Address('no_reply@m2b.co.id', 'M2B Sales Dept'),
            
            // Balasan tetap masuk ke Sales
            replyTo: [
                new Address('sales@m2b.co.id', 'Sales Dept')
            ],
            
            subject: 'Penawaran Harga / Quotation No: ' . $this->quotation->quotation_number,
        );
    }

    public function content(): Content
    {
        // Siapkan variabel untuk view
        $customerName = $this->quotation->customer 
            ? $this->quotation->customer->user->name 
            : $this->quotation->manual_pic;
            
        if (!$customerName) {
            $customerName = $this->quotation->customer 
                ? $this->quotation->customer->company_name 
                : $this->quotation->manual_company;
        }

        $serviceName = ucfirst($this->quotation->service_type);

        return new Content(
            view: 'emails.quotation-body',
            with: [
                'customer_name' => $customerName,
                'service_name'  => $serviceName,
                'company_name'  => $this->quotation->customer ? $this->quotation->customer->company_name : $this->quotation->manual_company,
            ],
        );
    }

    public function attachments(): array
    {
        $f = new NumberFormatter("id", NumberFormatter::SPELLOUT);
        $terbilangText = ucwords($f->format($this->quotation->grand_total)) . " Rupiah";

        $pdf = Pdf::loadView('admin.quotation-print', [ 
            'quotation' => $this->quotation,
            'terbilangText' => $terbilangText
        ]);
        
        $pdf->setPaper('a4', 'portrait');

        $cleanNumber = str_replace(['.', '/'], '-', $this->quotation->quotation_number);
        $filename = 'Quotation_' . $cleanNumber . '.pdf';

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}