<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $verificationUrl;

    public function __construct(User $user, string $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address('no_reply@m2b.co.id', 'Portal M2B'),
            replyTo: [new \Illuminate\Mail\Mailables\Address('sales@m2b.co.id', 'Customer Service M2B')],
            subject: 'Verifikasi Email Anda - Portal M2B',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email',
            with: [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }
}
