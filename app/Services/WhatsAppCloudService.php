<?php

namespace App\Services;

use App\Models\MoraLeadNotification;
use App\Models\WaMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudService
{
    public function isConfigured(): bool
    {
        return (bool) config('whatsapp.enabled')
            && filled(config('whatsapp.phone_number_id'))
            && filled(config('whatsapp.access_token'));
    }

    /**
     * Normalisasi nomor HP Indonesia menjadi format digit internasional (628...).
     */
    public function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }

    /**
     * Verifikasi signature HMAC-SHA256 dari header X-Hub-Signature-256 Meta.
     * Jika WHATSAPP_APP_SECRET tidak diisi, kembalikan true (untuk dev/staging).
     */
    public function verifySignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = (string) config('whatsapp.app_secret', '');
        if ($secret === '') {
            return true;
        }
        if (empty($signatureHeader) || !str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Kirim pesan teks keluar via Meta WhatsApp Cloud API dan catat ke wa_messages.
     */
    public function sendText(
        string $toPhone,
        string $body,
        ?MoraLeadNotification $lead = null,
        ?int $sentByUserId = null
    ): WaMessage {
        $normalized = $this->normalizePhone($toPhone);
        if ($normalized === '') {
            throw new \InvalidArgumentException('Nomor WhatsApp tujuan tidak valid.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Isi pesan tidak boleh kosong.');
        }

        $waMessageId = null;
        $status = 'sent';
        $errorMessage = null;
        $rawResponse = null;

        if ($this->isConfigured()) {
            $version = config('whatsapp.api_version', 'v21.0');
            $phoneId = config('whatsapp.phone_number_id');
            $token = config('whatsapp.access_token');

            $response = Http::withToken($token)
                ->timeout(15)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $normalized,
                    'type'              => 'text',
                    'text'              => [
                        'preview_url' => false,
                        'body'        => $body,
                    ],
                ]);

            $rawResponse = $response->json();

            if ($response->successful()) {
                $waMessageId = data_get($rawResponse, 'messages.0.id');
                $status = 'sent';
            } else {
                $status = 'failed';
                $errorMessage = data_get($rawResponse, 'error.message')
                    ?? ('HTTP ' . $response->status() . ' gagal mengirim pesan ke Meta Cloud API.');
                Log::warning('WhatsAppCloudService::sendText failed', [
                    'to' => $normalized,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                ]);
            }
        } else {
            throw new \RuntimeException('Meta WhatsApp Cloud API belum diaktifkan di .env.');
        }

        $msg = WaMessage::create([
            'mora_lead_id' => $lead?->id,
            'wa_message_id' => $waMessageId,
            'wa_phone'      => $normalized,
            'sender_name'   => auth()->user()?->name ?? 'Tim M2B',
            'direction'     => WaMessage::DIRECTION_OUTBOUND,
            'origin'        => WaMessage::ORIGIN_CLOUD_API,
            'message_type'  => 'text',
            'body'          => $body,
            'status'        => $status,
            'error_message' => $errorMessage,
            'sent_by'       => $sentByUserId,
            'wa_timestamp'  => now(),
            'raw_payload'   => $rawResponse,
        ]);

        if ($lead && $status !== 'failed') {
            $updates = ['last_wa_message_at' => now()];
            if (!$lead->first_responded_at) {
                $updates['first_responded_at'] = now();
            }
            if ($lead->status === 'new') {
                $updates['status'] = 'contacted';
            }
            if (!$lead->assigned_to && $sentByUserId) {
                $updates['assigned_to'] = $sentByUserId;
            }
            $lead->update($updates);
        }

        return $msg;
    }

    /**
     * Proses payload webhook resmi dari Meta Cloud API & WhatsApp Business App Coexistence.
     * Menangani:
     * 1. messages (pesan masuk dari prospek/customer)
     * 2. smb_message_echoes (pesan keluar yang dibalas langsung dari HP WA Business M2B)
     * 3. statuses (update status sent / delivered / read / failed)
     */
    public function ingestWebhookPayload(array $payload): int
    {
        $processed = 0;

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                // 1) Status delivery/read updates
                foreach (($value['statuses'] ?? []) as $st) {
                    $wamid = $st['id'] ?? null;
                    $newStatus = $st['status'] ?? null;
                    if ($wamid && $newStatus) {
                        $err = data_get($st, 'errors.0.message');
                        WaMessage::where('wa_message_id', $wamid)->update(array_filter([
                            'status' => $newStatus,
                            'error_message' => $err,
                        ], fn ($v) => $v !== null));
                        $processed++;
                    }
                }

                // Map contact names by wa_id
                $contactNames = [];
                foreach (($value['contacts'] ?? []) as $contact) {
                    $waId = $this->normalizePhone($contact['wa_id'] ?? '');
                    if ($waId !== '') {
                        $contactNames[$waId] = data_get($contact, 'profile.name');
                    }
                }

                // 2) Inbound messages from leads/customers
                if ($field === 'messages' || !empty($value['messages'])) {
                    foreach (($value['messages'] ?? []) as $msgData) {
                        if ($this->storeIncomingOrEchoMessage($msgData, $contactNames, WaMessage::DIRECTION_INBOUND, WaMessage::ORIGIN_CLOUD_API)) {
                            $processed++;
                        }
                    }
                }

                // 3) Coexistence Echoes: pesan yang dibalas langsung dari HP WA Business M2B
                if ($field === 'smb_message_echoes' || !empty($value['message_echoes'])) {
                    $echoes = $value['message_echoes'] ?? $value['messages'] ?? [];
                    foreach ($echoes as $echoData) {
                        if ($this->storeIncomingOrEchoMessage($echoData, $contactNames, WaMessage::DIRECTION_OUTBOUND, WaMessage::ORIGIN_COEXISTENCE_ECHO)) {
                            $processed++;
                        }
                    }
                }
            }
        }

        return $processed;
    }

    protected function storeIncomingOrEchoMessage(
        array $msgData,
        array $contactNames,
        string $direction,
        string $origin
    ): bool {
        $wamid = $msgData['id'] ?? null;
        if ($wamid && WaMessage::where('wa_message_id', $wamid)->exists()) {
            return false; // Idempotent: abaikan duplikat webhook Meta
        }

        // Untuk inbound, nomor customer ada di 'from'; untuk echo outbound, ada di 'to'
        $rawPhone = $direction === WaMessage::DIRECTION_INBOUND
            ? ($msgData['from'] ?? '')
            : ($msgData['to'] ?? '');
        $phone = $this->normalizePhone($rawPhone);
        if ($phone === '') {
            return false;
        }

        $type = $msgData['type'] ?? 'text';
        $body = $this->extractBodyText($msgData, $type);
        $timestamp = isset($msgData['timestamp'])
            ? Carbon::createFromTimestamp((int) $msgData['timestamp'])
            : now();
        $contactName = $contactNames[$phone] ?? null;

        // Cari lead yang cocok berdasarkan nomor WA, atau buat lead baru bila inbound dari prospek baru
        $lead = $this->findOrCreateLeadByPhone($phone, $contactName, $body, $direction);

        WaMessage::create([
            'mora_lead_id' => $lead?->id,
            'wa_message_id' => $wamid,
            'wa_phone'      => $phone,
            'sender_name'   => $direction === WaMessage::DIRECTION_INBOUND
                ? ($contactName ?: $phone)
                : 'WA Business HP M2B',
            'direction'     => $direction,
            'origin'        => $origin,
            'message_type'  => $type,
            'body'          => $body,
            'status'        => $direction === WaMessage::DIRECTION_INBOUND ? 'received' : 'sent',
            'wa_timestamp'  => $timestamp,
            'raw_payload'   => $msgData,
        ]);

        if ($lead) {
            $updates = ['last_wa_message_at' => $timestamp];
            if ($direction === WaMessage::DIRECTION_INBOUND) {
                $updates['read_at'] = null; // Tandai belum dibaca agar staf & admin langsung notice!
            } else {
                if (!$lead->first_responded_at) {
                    $updates['first_responded_at'] = $timestamp;
                }
                if ($lead->status === 'new') {
                    $updates['status'] = 'contacted';
                }
            }
            $lead->update($updates);
        }

        return true;
    }

    protected function extractBodyText(array $msgData, string $type): string
    {
        return match ($type) {
            'text'        => (string) data_get($msgData, 'text.body', ''),
            'button'      => (string) data_get($msgData, 'button.text', '[Tombol]'),
            'interactive' => (string) (data_get($msgData, 'interactive.button_reply.title')
                ?? data_get($msgData, 'interactive.list_reply.title')
                ?? '[Interaktif]'),
            'image'       => trim('[Gambar] ' . (string) data_get($msgData, 'image.caption', '')),
            'document'    => trim('[Dokumen: ' . (string) data_get($msgData, 'document.filename', 'file') . '] ' . (string) data_get($msgData, 'document.caption', '')),
            'audio'       => '[Pesan Suara / Audio]',
            'video'       => trim('[Video] ' . (string) data_get($msgData, 'video.caption', '')),
            'location'    => '[Lokasi Shared]',
            default       => '[' . strtoupper($type) . ']',
        };
    }

    protected function findOrCreateLeadByPhone(
        string $normalizedPhone,
        ?string $contactName,
        string $firstMessage,
        string $direction
    ): ?MoraLeadNotification {
        $tail = substr($normalizedPhone, -9);

        $existing = MoraLeadNotification::query()
            ->where(function ($q) use ($normalizedPhone, $tail) {
                $q->where('phone', $normalizedPhone)
                  ->orWhere('phone', 'like', '%' . $tail);
            })
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        if ($direction !== WaMessage::DIRECTION_INBOUND) {
            return null;
        }

        return MoraLeadNotification::create([
            'name'               => $contactName ?: ('Prospek WA +' . $normalizedPhone),
            'phone'              => $normalizedPhone,
            'source'             => 'whatsapp_direct',
            'score'              => 'warm',
            'service_interest'   => 'consultation',
            'summary'            => \Illuminate\Support\Str::limit($firstMessage, 250),
            'status'             => 'new',
            'last_wa_message_at' => now(),
        ]);
    }
}
