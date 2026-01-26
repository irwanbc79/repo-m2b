<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeVerifiedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address('no_reply@m2b.co.id', 'Portal M2B'),
            replyTo: [new \Illuminate\Mail\Mailables\Address('sales@m2b.co.id', 'Customer Service M2B')],
            subject: 'Selamat Datang di Portal M2B!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.welcome-verified',
            with: [
                'user' => $this->user,
            ],
        );
    }
}
