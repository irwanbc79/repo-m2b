<?php

namespace App\Listeners;

use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

/**
 * Mencatat SETIAP email keluar dari portal ke buku besar `email_deliveries`.
 *
 * Kenapa lewat event, bukan menyunting satu per satu tempat pengiriman:
 * portal mengirim email dari sekitar 20 titik berbeda (Livewire component,
 * controller, job, command). Satu listener global menangkap semuanya —
 * termasuk kode pengirim yang belum ditulis hari ini.
 *
 * Kenapa `MessageSending` dan bukan `MessageSent`: bila pengiriman gagal di
 * tengah jalan, `MessageSent` tidak pernah menyala dan kegagalannya jadi tak
 * terlihat. Dengan mencatat lebih dulu, barisnya tetap ada dan mangkrak di
 * status `queued` — justru itulah sinyal bahwa ada yang tidak beres.
 *
 * Listener ini WAJIB tidak pernah menggagalkan pengiriman email. Apa pun yang
 * meledak di sini ditelan dan dicatat ke log, karena mencatat metadata jauh
 * lebih tidak penting daripada emailnya sendiri sampai ke tujuan.
 */
class RecordEmailDelivery
{
    /**
     * Model yang dikenali sebagai "pemilik" sebuah email, berurutan dari yang
     * paling spesifik. Invoice didahulukan atas Shipment karena email tagihan
     * biasanya membawa keduanya, dan yang lebih berguna bagi staf adalah
     * tautan ke tagihannya.
     */
    private const RELATABLE = [
        Invoice::class,
        Quotation::class,
        Shipment::class,
        Customer::class,
    ];

    public function handle(MessageSending $event): void
    {
        try {
            $this->record($event);
        } catch (\Throwable $e) {
            // Sengaja ditelan — lihat catatan kelas di atas.
            Log::warning('[email-delivery] gagal mencatat email keluar: ' . $e->getMessage());
        }
    }

    private function record(MessageSending $event): void
    {
        $recipients = $event->message->getTo() ?? [];

        if (empty($recipients)) {
            return;
        }

        $subject  = (string) $event->message->getSubject();
        $data     = is_array($event->data) ? $event->data : [];
        $relation = $this->resolveRelation($data);

        // Satu baris per penerima: peristiwa dari Kirim Email juga datang
        // per penerima, jadi pencocokannya nanti jadi lurus.
        foreach ($recipients as $recipient) {
            EmailDelivery::create(array_merge([
                'recipient_email' => $recipient->getAddress(),
                'subject'         => mb_substr($subject, 0, 500),
                'sent_at'         => now(),
                'mailable_class'  => $data['__laravel_mailable'] ?? null,
                'mailer'          => config('mail.default'),
                'status'          => EmailDelivery::STATUS_QUEUED,
            ], $relation));
        }
    }

    /**
     * Menautkan email ke entitas bisnisnya.
     *
     * Laravel menyalin seluruh properti PUBLIK sebuah Mailable ke dalam data
     * event (lewat `Mailable::buildViewData()`), jadi objek modelnya ada di
     * sana tanpa perlu menyentuh kode pengirim. `InvoiceMail` misalnya punya
     * `public $invoice`.
     */
    private function resolveRelation(array $data): array
    {
        foreach (self::RELATABLE as $class) {
            foreach ($data as $value) {
                if ($value instanceof $class && $value instanceof Model && $value->exists) {
                    return [
                        'related_type' => $class,
                        'related_id'   => $value->getKey(),
                    ];
                }
            }
        }

        return [];
    }
}
