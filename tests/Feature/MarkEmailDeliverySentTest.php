<?php

namespace Tests\Feature;

use App\Models\EmailDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MarkEmailDeliverySentTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_smtp_otomatis_ditandai_delivered_saat_event_messagesent(): void
    {
        $delivery = EmailDelivery::create([
            'recipient_email' => 'klien@example.com',
            'subject'         => 'Testing SMTP Delivery Status',
            'sent_at'         => now(),
            'mailer'          => 'smtp',
            'status'          => EmailDelivery::STATUS_QUEUED,
        ]);

        $message = (new Email())
            ->to('klien@example.com')
            ->from('no_reply@m2b.co.id')
            ->subject('Testing SMTP Delivery Status')
            ->text('Isi pesan pengujian');

        $symfonySent = new SymfonySentMessage(
            $message,
            new Envelope(new Address('no_reply@m2b.co.id'), [new Address('klien@example.com')])
        );

        $event = new MessageSent(new SentMessage($symfonySent));

        event($event);

        $delivery->refresh();

        $this->assertSame(EmailDelivery::STATUS_DELIVERED, $delivery->status);
        $this->assertNotNull($delivery->delivered_at);
    }
}
