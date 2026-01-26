<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?User $user;
    public string $resetUrl;

    public function __construct(?User $user, string $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address('no_reply@m2b.co.id', 'Portal M2B'),
            replyTo: [new \Illuminate\Mail\Mailables\Address('sales@m2b.co.id', 'Customer Service M2B')],
            subject: 'Reset Password - Portal M2B',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.reset-password',
            with: [
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
