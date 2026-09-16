<?php

namespace App\Listeners;

use App\Models\EmailDelivery;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

/**
 * Menandai email keluar berstatus DELIVERED saat berhasil dikirim oleh driver SMTP.
 *
 * RecordEmailDelivery mencatat email di awal (MessageSending) dengan status 'queued'.
 * Untuk driver SMTP langsung (seperti mx.kerjamail.co), saat MessageSent menyala berarti
 * proses kirim ke relay SMTP selesai tanpa error. Kita tandai statusnya menjadi 'delivered'
 * agar tidak dianggap mangkrak.
 */
class MarkEmailDeliverySent
{
    public function handle(MessageSent $event): void
    {
        try {
            $recipients = $event->message->getTo() ?? [];

            foreach ($recipients as $recipient) {
                $email = $recipient->getAddress();

                // Cari baris EmailDelivery yang baru saja dibuat berstatus queued untuk mailer smtp
                $delivery = EmailDelivery::where('recipient_email', $email)
                    ->where('status', EmailDelivery::STATUS_QUEUED)
                    ->where('mailer', 'smtp')
                    ->where('sent_at', '>=', now()->subMinutes(15))
                    ->latest('id')
                    ->first();

                if ($delivery) {
                    $delivery->update([
                        'status'       => EmailDelivery::STATUS_DELIVERED,
                        'delivered_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[email-delivery] gagal menandai email terkirim: ' . $e->getMessage());
        }
    }
}
