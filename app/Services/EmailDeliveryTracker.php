<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\EmailDeliveryEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Menautkan peristiwa pengiriman dari Kirim Email ke catatan email keluar.
 *
 * Kenapa serumit ini: API Kirim Email tidak mau menerima nomor referensi
 * titipan kita (respons kirim tidak mengembalikan message_guid, field `tags`
 * diabaikan, dan custom header ditolak). Jadi pencocokan harus dibangun dari
 * apa yang tersedia: alamat penerima + subject + jendela waktu.
 *
 * Sekali sebuah peristiwa berhasil dicocokkan, `message_guid`-nya disimpan di
 * baris pengiriman, dan peristiwa berikutnya untuk email yang sama cukup
 * dicocokkan lewat ID itu — jauh lebih murah dan tidak mungkin salah orang.
 *
 * Dipakai bersama oleh penarik log berkala dan penerima webhook, karena
 * bentuk data keduanya praktis sama.
 */
class EmailDeliveryTracker
{
    /** Sejauh mana ke belakang sebuah peristiwa boleh dicocokkan. */
    private const MATCH_WINDOW_DAYS = 7;

    /**
     * Catat satu peristiwa. Aman dijalankan berulang untuk peristiwa yang
     * sama — baris kedua tidak akan dibuat.
     *
     * @param array{
     *     provider_event_id: string,
     *     event_type: string,
     *     message_guid?: ?string,
     *     recipient?: ?string,
     *     subject?: ?string,
     *     occurred_at?: mixed,
     *     detail?: ?string
     * } $raw
     */
    public function record(array $raw): ?EmailDeliveryEvent
    {
        $eventId = $raw['provider_event_id'] ?? null;
        $type    = $raw['event_type'] ?? null;

        if (! $eventId || ! $type) {
            return null;
        }

        if (EmailDeliveryEvent::where('provider_event_id', $eventId)->exists()) {
            return null;
        }

        $occurredAt = $this->toDate($raw['occurred_at'] ?? null) ?? now();
        $subject    = $this->normalizeSubject($raw['subject'] ?? null);
        $recipient  = $raw['recipient'] ?? null;
        $guid       = $raw['message_guid'] ?? null;

        $delivery = $this->findDelivery($guid, $recipient, $subject, $occurredAt);

        $event = EmailDeliveryEvent::create([
            'email_delivery_id'     => $delivery?->id,
            'provider_event_id'     => $eventId,
            'provider_message_guid' => $guid,
            'event_type'            => $type,
            'recipient'             => $recipient,
            'subject'               => $subject ? mb_substr($subject, 0, 500) : null,
            'occurred_at'           => $occurredAt,
            'detail'                => $raw['detail'] ?? null,
        ]);

        if ($delivery) {
            $this->applyToDelivery($delivery, $guid, $type, $occurredAt, $raw['detail'] ?? null);
        }

        return $event;
    }

    /**
     * Cari catatan pengiriman yang dimaksud peristiwa ini.
     */
    private function findDelivery(?string $guid, ?string $recipient, ?string $subject, Carbon $occurredAt): ?EmailDelivery
    {
        // Jalur cepat: sudah pernah ditautkan sebelumnya.
        if ($guid) {
            $byGuid = EmailDelivery::where('provider_message_guid', $guid)->first();
            if ($byGuid) {
                return $byGuid;
            }
        }

        if (! $recipient) {
            return null;
        }

        $query = EmailDelivery::where('recipient_email', $recipient)
            ->where('sent_at', '>=', $occurredAt->copy()->subDays(self::MATCH_WINDOW_DAYS))
            // Toleransi jam server yang sedikit berbeda dengan provider.
            ->where('sent_at', '<=', $occurredAt->copy()->addMinutes(10));

        if ($subject !== null && $subject !== '') {
            $query->where('subject', $subject);
        }

        // Utamakan baris yang belum terikat ke pesan lain, lalu yang paling
        // dekat waktunya — supaya subject kembar tidak saling rebut.
        return $query->orderByRaw('provider_message_guid IS NULL DESC')
            ->orderByDesc('sent_at')
            ->first();
    }

    /**
     * Perbarui ringkasan di baris pengiriman berdasarkan peristiwa baru.
     */
    private function applyToDelivery(
        EmailDelivery $delivery,
        ?string $guid,
        string $type,
        Carbon $occurredAt,
        ?string $detail
    ): void {
        $changes = [];

        if ($guid && ! $delivery->provider_message_guid) {
            $changes['provider_message_guid'] = $guid;
        }

        // Status hanya boleh maju — peristiwa yang telat datang tidak
        // menurunkan email yang sudah lebih jauh perjalanannya.
        if ($delivery->canAdvanceTo($type)) {
            $changes['status'] = $type;
        }

        switch ($type) {
            case EmailDelivery::STATUS_DELIVERED:
                $changes['delivered_at'] = $delivery->delivered_at ?? $occurredAt;
                // Alamatnya hidup lagi — cabut penanda mental bila ada.
                $this->cabutPenandaMental($delivery->recipient_email);
                break;

            case EmailDelivery::STATUS_OPENED:
                $changes['first_opened_at'] = $delivery->first_opened_at ?? $occurredAt;
                $changes['last_opened_at']  = $occurredAt;
                break;

            case EmailDelivery::STATUS_BOUNCED:
            case EmailDelivery::STATUS_FAILED:
                $changes['failed_at']      = $delivery->failed_at ?? $occurredAt;
                $changes['failure_reason'] = $detail;
                $this->tandaiCustomerEmailMental($delivery->recipient_email, $detail, $occurredAt);
                break;
        }

        // Hitung ulang dari tabel peristiwa, bukan menambah counter — supaya
        // penarikan log yang dijalankan dua kali tidak menggandakan angka.
        $changes['open_count'] = EmailDeliveryEvent::where('email_delivery_id', $delivery->id)
            ->where('event_type', EmailDelivery::STATUS_OPENED)->count();
        $changes['click_count'] = EmailDeliveryEvent::where('email_delivery_id', $delivery->id)
            ->where('event_type', EmailDelivery::STATUS_CLICKED)->count();

        $delivery->update($changes);
    }

    /**
     * Tandai customer yang alamat emailnya mental.
     *
     * Penanda disimpan di baris customer (bukan dihitung saat dibutuhkan)
     * karena Manage Customers memanggil dataQuality() per baris — menghitung
     * di sana akan langsung jadi N+1.
     *
     * Email customer tinggal di tabel `users`, jadi pencocokannya lewat relasi.
     */
    private function tandaiCustomerEmailMental(?string $email, ?string $alasan, Carbon $waktu): void
    {
        if (! $email) {
            return;
        }

        try {
            Customer::whereHas('user', fn ($q) => $q->where('email', $email))
                ->update([
                    'email_bounced_at'    => $waktu,
                    'email_bounce_reason' => $alasan ? mb_substr($alasan, 0, 500) : null,
                ]);
        } catch (\Throwable $e) {
            // Penandaan kualitas data tidak boleh menggagalkan pencatatan
            // peristiwa pengiriman.
            Log::warning('[email-delivery] gagal menandai email mental: ' . $e->getMessage());
        }
    }

    /**
     * Cabut penanda begitu ada email yang berhasil sampai ke alamat itu —
     * supaya penandanya sembuh sendiri setelah staf memperbaiki alamatnya,
     * tanpa perlu dibersihkan manual.
     */
    private function cabutPenandaMental(?string $email): void
    {
        if (! $email) {
            return;
        }

        try {
            // `whereNotNull` didahulukan supaya mayoritas kasus (tidak ada
            // penanda sama sekali) berhenti murah di indeks.
            Customer::whereNotNull('email_bounced_at')
                ->whereHas('user', fn ($q) => $q->where('email', $email))
                ->update([
                    'email_bounced_at'    => null,
                    'email_bounce_reason' => null,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[email-delivery] gagal mencabut penanda mental: ' . $e->getMessage());
        }
    }

    /**
     * Subject di log provider bisa datang dalam bentuk ter-encode MIME
     * (mis. "=?utf-8?Q?=E2=9C=85?= Buku kas aman") untuk emoji dan karakter
     * non-ASCII. Tanpa dikembalikan ke bentuk asli, pencocokan berbasis
     * subject akan gagal diam-diam persis pada email yang paling penting.
     */
    public function normalizeSubject(?string $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        if (str_contains($subject, '=?')) {
            $decoded = @mb_decode_mimeheader($subject);
            if (is_string($decoded) && $decoded !== '') {
                $subject = $decoded;
            }
        }

        // Provider kadang menyisipkan pelipatan baris pada header panjang.
        return trim(preg_replace('/\s+/u', ' ', $subject));
    }

    private function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Timestamp dari provider selalu UTC absolut, tapi `sent_at`
            // tersimpan dalam zona waktu aplikasi (Asia/Jakarta). Tanpa
            // disetarakan, selisih 7 jam membuat email jatuh di luar jendela
            // pencocokan dan tidak pernah cocok.
            return is_numeric($value)
                ? Carbon::createFromTimestamp((int) $value, config('app.timezone'))
                : Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
