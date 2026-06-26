<?php

namespace App\Mail;

use App\Models\MoraLeadNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadFollowupMail extends Mailable
{
    use Queueable, SerializesModels;

    public MoraLeadNotification $lead;
    public string $loginUrl;

    public function __construct(MoraLeadNotification $lead)
    {
        $this->lead = $lead;
        $this->loginUrl = url('/login');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no_reply@m2b.co.id', 'Portal M2B'),
            replyTo: [new Address('sales@m2b.co.id', 'Sales M2B Logistics')],
            subject: 'Terima kasih sudah mendaftar di M2B Logistics — boleh kami bantu?',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-followup',
            with: [
                'lead' => $this->lead,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
