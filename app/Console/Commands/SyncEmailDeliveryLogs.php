<?php

namespace App\Console\Commands;

use App\Models\EmailDelivery;
use App\Services\EmailDeliveryTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menarik riwayat pengiriman dari Kirim Email dan menautkannya ke buku besar.
 *
 * INI MEKANISME UTAMA, bukan cadangan. Webhook mereka terbukti tidak menyala
 * untuk peristiwa email nyata (diuji 29 Juli 2026: log mencatat delivered dan
 * opened dengan normal, tapi tidak ada satu pun permintaan masuk ke portal,
 * padahal webhook uji mereka sendiri berhasil). Penerima webhook tetap
 * dipertahankan supaya langsung berguna bila mereka memperbaikinya.
 *
 * Aman dijalankan berulang: setiap peristiwa punya ID sendiri di sisi
 * provider dan hanya dicatat sekali.
 */
class SyncEmailDeliveryLogs extends Command
{
    protected $signature = 'email:sync-delivery-logs
        {--hours=24 : Seberapa jauh ke belakang riwayat ditarik}
        {--limit=1000 : Jumlah maksimum baris per penarikan}';

    protected $description = 'Tarik riwayat pengiriman dari Kirim Email, tautkan ke catatan email keluar';

    public function handle(EmailDeliveryTracker $tracker): int
    {
        $domain   = config('kirimemail.domain');
        $username = config('kirimemail.username');
        $token    = config('kirimemail.token');

        if (! $domain || ! $username || ! $token) {
            $this->warn('Kredensial Kirim Email belum diisi — penarikan dilewati.');

            return self::SUCCESS;
        }

        $since = now()->subHours((int) $this->option('hours'));

        try {
            $response = Http::withBasicAuth($username, $token)
                ->acceptJson()
                ->timeout(30)
                ->get(rtrim(config('kirimemail.base_url'), '/') . "/api/domains/{$domain}/log", [
                    'start' => $since->toIso8601String(),
                    'limit' => (int) $this->option('limit'),
                ]);
        } catch (\Throwable $e) {
            $this->error('Gagal menghubungi Kirim Email: ' . $e->getMessage());
            Log::error('[email:sync-delivery-logs] ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('Kirim Email membalas HTTP ' . $response->status());

            return self::FAILURE;
        }

        $rows    = $response->json('data') ?? [];
        $baru    = 0;
        $terikat = 0;

        foreach ($rows as $row) {
            $event = $tracker->record([
                'provider_event_id' => $row['id'] ?? null,
                'message_guid'      => $row['message_guid'] ?? null,
                'event_type'        => $row['event_type'] ?? null,
                'recipient'         => $row['recipient'] ?? null,
                'subject'           => $row['subject'] ?? null,
                'occurred_at'       => $row['created_at'] ?? null,
                'detail'            => $this->detail($row),
            ]);

            if ($event) {
                $baru++;
                if ($event->email_delivery_id) {
                    $terikat++;
                }
            }
        }

        $this->info(sprintf(
            'Ditarik %d baris · %d peristiwa baru · %d tertaut ke catatan pengiriman.',
            count($rows), $baru, $terikat
        ));

        $this->laporkanYangMangkrak();

        return self::SUCCESS;
    }

    /**
     * Keterangan mentah dari provider, untuk dibaca staf saat ada masalah.
     */
    private function detail(array $row): ?string
    {
        foreach (['event_detail', 'event'] as $key) {
            $value = $row[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return mb_substr($value, 0, 1000);
            }
        }

        return null;
    }

    /**
     * Email yang tercatat hendak dikirim tapi tidak pernah dikabarkan
     * nasibnya — pertanda pengiriman gagal tanpa meninggalkan pesan error,
     * pola yang sudah pernah menggigit portal ini di pembukuan kas.
     */
    private function laporkanYangMangkrak(): void
    {
        $mangkrak = EmailDelivery::stuck(60)
            ->where('mailer', 'kirimemail')
            ->count();

        if ($mangkrak > 0) {
            $this->warn("{$mangkrak} email masih mangkrak di status 'queued' lebih dari 1 jam.");
            Log::warning("[email:sync-delivery-logs] {$mangkrak} email mangkrak di 'queued'");
        }
    }
}
