<?php

namespace App\Services;

use App\Models\MoraLeadNotification;
use App\Models\WaDevice;
use App\Models\WaMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppCloudService
{
    public function isConfigured(): bool
    {
        if (!config('whatsapp.enabled') || !filled(config('whatsapp.access_token'))) {
            return false;
        }

        if (filled(config('whatsapp.phone_number_id'))) {
            return true;
        }

        try {
            return Schema::hasTable('wa_devices') && WaDevice::where('is_active', true)->exists();
        } catch (\Throwable $e) {
            return false;
        }
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
     * Resolve Phone Number ID & WaDevice yang dipakai untuk kirim pesan keluar.
     */
    protected function resolveOutboundDevice(?MoraLeadNotification $lead = null, ?WaDevice $explicitDevice = null): array
    {
        $device = $explicitDevice;

        if (!$device && $lead?->wa_device_id) {
            $device = WaDevice::where('id', $lead->wa_device_id)->where('is_active', true)->first();
        }
        if (!$device && Schema::hasTable('wa_devices')) {
            $device = WaDevice::where('is_primary', true)->where('is_active', true)->first()
                ?? WaDevice::where('is_active', true)->first();
        }

        $phoneNumberId = $device?->phone_number_id ?: (string) config('whatsapp.phone_number_id', '');

        return [$device, $phoneNumberId];
    }

    /**
     * Kirim pesan teks keluar via Meta WhatsApp Cloud API (mendukung multi-number wa_devices).
     */
    public function sendText(
        string $toPhone,
        string $body,
        ?MoraLeadNotification $lead = null,
        ?int $sentByUserId = null,
        ?WaDevice $explicitDevice = null
    ): WaMessage {
        $normalized = $this->normalizePhone($toPhone);
        if ($normalized === '') {
            throw new \InvalidArgumentException('Nomor WhatsApp tujuan tidak valid.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Isi pesan tidak boleh kosong.');
        }

        if (!$this->isConfigured()) {
            throw new \RuntimeException('Meta WhatsApp Cloud API belum diaktifkan di .env.');
        }

        [$device, $phoneId] = $this->resolveOutboundDevice($lead, $explicitDevice);
        if ($phoneId === '') {
            throw new \RuntimeException('Phone Number ID WhatsApp belum dikonfigurasi.');
        }

        $version = config('whatsapp.api_version', 'v21.0');
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
        $waMessageId = null;
        $status = 'sent';
        $errorMessage = null;

        if ($response->successful()) {
            $waMessageId = data_get($rawResponse, 'messages.0.id');
            $status = 'sent';
            if ($device) {
                $device->update(['last_synced_at' => now()]);
            }
        } else {
            $status = 'failed';
            $errorMessage = data_get($rawResponse, 'error.message')
                ?? ('HTTP ' . $response->status() . ' gagal mengirim pesan ke Meta Cloud API.');
            Log::warning('WhatsAppCloudService::sendText failed', [
                'to' => $normalized,
                'phone_number_id' => $phoneId,
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);
        }

        $msg = WaMessage::create([
            'mora_lead_id'  => $lead?->id,
            'wa_device_id'  => $device?->id,
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
            if (!$lead->wa_device_id && $device) {
                $updates['wa_device_id'] = $device->id;
            }
            $lead->update($updates);
        }

        return $msg;
    }

    /**
     * Proses payload webhook resmi dari Meta Cloud API & Multi-Number Coexistence (1 Kantor + 7 Marketing).
     * Menangani:
     * 1. Mapping perangkat lewat metadata.phone_number_id -> update last_synced_at (aturan aktif 13 hari)
     * 2. messages (pesan masuk dari prospek/customer)
     * 3. smb_message_echoes (pesan keluar, edit, & revoke dari HP/Web WA Business staf)
     * 4. statuses (sent / delivered / read / failed)
     */
    public function ingestWebhookPayload(array $payload): int
    {
        $processed = 0;

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                // Resolve perangkat WA berdasarkan metadata.phone_number_id (Multi-Number Coexistence)
                $phoneNumberId = data_get($value, 'metadata.phone_number_id');
                $device = null;
                if ($phoneNumberId && Schema::hasTable('wa_devices')) {
                    $device = WaDevice::where('phone_number_id', $phoneNumberId)->first();
                    if ($device) {
                        $device->update(['last_synced_at' => now()]);
                    }
                }

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
                        if ($this->storeIncomingOrEchoMessage($msgData, $contactNames, WaMessage::DIRECTION_INBOUND, WaMessage::ORIGIN_CLOUD_API, $device)) {
                            $processed++;
                        }
                    }
                }

                // 3) Coexistence Echoes: pesan keluar / edit / revoke yang dilakukan staf di HP/WA Web
                if ($field === 'smb_message_echoes' || !empty($value['message_echoes'])) {
                    $echoes = $value['message_echoes'] ?? $value['messages'] ?? [];
                    foreach ($echoes as $echoData) {
                        if ($this->handleEchoRevokeOrEdit($echoData)) {
                            $processed++;
                            continue;
                        }
                        if ($this->storeIncomingOrEchoMessage($echoData, $contactNames, WaMessage::DIRECTION_OUTBOUND, WaMessage::ORIGIN_COEXISTENCE_ECHO, $device)) {
                            $processed++;
                        }
                    }
                }
            }
        }

        return $processed;
    }

    /**
     * Tangani echo revoke (hapus pesan) atau edit pesan dari HP WA Business staf.
     */
    protected function handleEchoRevokeOrEdit(array $echoData): bool
    {
        $type = $echoData['type'] ?? '';

        if ($type === 'revoke') {
            $targetWamid = data_get($echoData, 'revoke.original_message_id')
                ?? data_get($echoData, 'context.id');
            if ($targetWamid) {
                WaMessage::where('wa_message_id', $targetWamid)->update([
                    'status' => 'revoked',
                ]);
                return true;
            }
        }

        if ($type === 'edit') {
            $targetWamid = data_get($echoData, 'edit.original_message_id')
                ?? data_get($echoData, 'context.id');
            $newText = data_get($echoData, 'edit.message.text.body')
                ?? data_get($echoData, 'text.body');
            if ($targetWamid && filled($newText)) {
                WaMessage::where('wa_message_id', $targetWamid)->update([
                    'body' => trim((string) $newText) . ' (diedit)',
                ]);
                return true;
            }
        }

        return false;
    }

    protected function storeIncomingOrEchoMessage(
        array $msgData,
        array $contactNames,
        string $direction,
        string $origin,
        ?WaDevice $device = null
    ): bool {
        $wamid = $msgData['id'] ?? null;
        if ($wamid && WaMessage::where('wa_message_id', $wamid)->exists()) {
            return false; // Idempotent: abaikan duplikat webhook Meta
        }

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

        $lead = $this->findOrCreateLeadByPhone($phone, $contactName, $body, $direction, $device);

        $outboundLabel = $device
            ? ($device->user?->name ? $device->user->name . ' (' . $device->label . ')' : $device->label)
            : 'WA Business HP M2B';

        WaMessage::create([
            'mora_lead_id'  => $lead?->id,
            'wa_device_id'  => $device?->id,
            'wa_message_id' => $wamid,
            'wa_phone'      => $phone,
            'sender_name'   => $direction === WaMessage::DIRECTION_INBOUND
                ? ($contactName ?: $phone)
                : $outboundLabel,
            'direction'     => $direction,
            'origin'        => $origin,
            'message_type'  => $type,
            'body'          => $body,
            'status'        => $direction === WaMessage::DIRECTION_INBOUND ? 'received' : 'sent',
            'sent_by'       => $direction === WaMessage::DIRECTION_OUTBOUND ? $device?->user_id : null,
            'wa_timestamp'  => $timestamp,
            'raw_payload'   => $msgData,
        ]);

        if ($lead) {
            $updates = ['last_wa_message_at' => $timestamp];
            if ($device && !$lead->wa_device_id) {
                $updates['wa_device_id'] = $device->id;
            }
            if ($device?->user_id && !$lead->assigned_to) {
                $updates['assigned_to'] = $device->user_id;
            }

            if ($direction === WaMessage::DIRECTION_INBOUND) {
                $updates['read_at'] = null;
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
        string $direction,
        ?WaDevice $device = null
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
            'assigned_to'        => $device?->user_id,
            'wa_device_id'       => $device?->id,
            'last_wa_message_at' => now(),
        ]);
    }
}
