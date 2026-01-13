<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment; // Penting
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf; // Library PDF
use NumberFormatter;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function envelope(): Envelope
{
    return new Envelope(
        // 1. PENGIRIM: Harus 'no_reply' (sesuai login SMTP), tapi Namanya 'M2B Finance'
        from: new \Illuminate\Mail\Mailables\Address('no_reply@m2b.co.id', 'M2B Finance Dept'),

        // 2. BALAS KE: Arahkan ke email finance asli
        replyTo: [
            new \Illuminate\Mail\Mailables\Address('finance@m2b.co.id', 'Finance Dept')
        ],

        subject: 'Faktur Tagihan / Invoice No: ' . $this->invoice->invoice_number,
    );
}

    public function content(): Content
    {
        // Kita pakai view khusus untuk body email
        return new Content(
            view: 'emails.invoice-body',
        );
    }

    public function attachments(): array
{
    $pdf = Pdf::loadView('admin.invoice-pdf', [
        'invoice' => $this->invoice,
        'isPdf'   => true, // 🔑 PENTING
    ])->setPaper('A4');

    return [
        Attachment::fromData(
            fn () => $pdf->output(),
            'Invoice_' . $this->invoice->invoice_number . '.pdf'
        )->withMime('application/pdf'),
    ];
}

}