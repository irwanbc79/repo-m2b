<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Penerima webhook peristiwa pengiriman dari Kirim Email.
 *
 * FASE 00 — tugasnya hanya MENANGKAP, belum memproses.
 *
 * Bentuk payload mereka belum terdokumentasi di mana pun, jadi controller ini
 * sengaja TIDAK memvalidasi struktur: apa pun yang masuk disimpan utuh supaya
 * bisa dibaca, lalu pemrosesan sungguhan menyusul di fase 02 setelah bentuknya
 * diketahui.
 *
 * Rahasia dititipkan di path URL, bukan header, karena API Kirim Email terbukti
 * tidak bisa menangani custom header (validasinya menolak array maupun string),
 * sehingga besar kemungkinan konfigurasi webhook mereka hanya menerima URL.
 */
class KirimEmailWebhookController extends Controller
{
    /**
     * Kunci yang dicoba berurutan saat menebak isi payload.
     * Semuanya opsional — tidak ada yang diandalkan.
     */
    private const GUESS = [
        'event_type'   => ['event_type', 'event', 'type', 'status'],
        'message_guid' => ['message_guid', 'messageGuid', 'guid', 'message_id'],
        'recipient'    => ['recipient', 'to', 'email', 'recipient_email'],
        'subject'      => ['subject'],
    ];

    public function store(Request $request, string $token): JsonResponse
    {
        $expected = config('services.kirimemail.webhook_token');

        if (! $expected || ! hash_equals($expected, $token)) {
            Log::warning('[kirimemail-webhook] token tidak cocok', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $body = $request->json()->all();
        if (empty($body)) {
            $body = $request->all();
        }

        // Payload bisa berupa satu peristiwa atau kumpulan — keduanya diterima.
        $events = $this->isList($body) ? $body : [$body];

        foreach ($events as $event) {
            if (! is_array($event)) {
                $event = ['_raw' => $event];
            }

            EmailWebhookEvent::create([
                'event_type'   => $this->pick($event, self::GUESS['event_type']),
                'message_guid' => $this->pick($event, self::GUESS['message_guid']),
                'recipient'    => $this->pick($event, self::GUESS['recipient']),
                'subject'      => $this->pick($event, self::GUESS['subject']),
                'payload'      => $event,
                'received_at'  => now(),
            ]);
        }

        Log::info('[kirimemail-webhook] ' . count($events) . ' peristiwa diterima', [
            'keys' => array_keys($events[0] ?? []),
        ]);

        // Selalu 200 supaya provider tidak mengulang kiriman; pemeriksaan
        // dilakukan belakangan terhadap baris yang sudah tersimpan.
        return response()->json(['success' => true, 'received' => count($events)]);
    }

    /**
     * Ambil nilai pertama yang ada di antara beberapa kemungkinan nama kunci.
     */
    private function pick(array $event, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $event[$key] ?? null;
            if (is_scalar($value) && $value !== '') {
                return mb_substr((string) $value, 0, 255);
            }
        }

        return null;
    }

    /**
     * Benar bila array bergaya daftar (bukan objek tunggal).
     */
    private function isList(array $value): bool
    {
        return $value !== [] && array_keys($value) === range(0, count($value) - 1);
    }
}
