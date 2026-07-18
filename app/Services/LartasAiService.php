<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\LartasAnalysis;
use App\Models\Shipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AI Lartas (F4) — HYBRID multi-provider (Anthropic / OpenAI / Gemini / DeepSeek).
 *
 * Prinsip (disepakati bersama):
 *  - AI HANYA merekomendasikan izin/lartas berdasarkan HS code yang SUDAH
 *    diputuskan customer. Keputusan akhir tetap di manusia.
 *  - TIDAK terhubung live ke insw.go.id (peraturan dinamis). Hasil di-grounding
 *    ke katalog dokumen lartas M2B agar bisa langsung dipetakan ke checklist.
 *  - Wajib disclaimer + arahan verifikasi ke INSW di sisi UI.
 *  - Degrade aman: bila tak ada key sama sekali, fitur nonaktif tanpa crash.
 *  - Isi key provider mana pun di .env; sistem auto-pilih yang tersedia + fallback.
 */
class LartasAiService
{
    /** Provider yang punya key, sesuai urutan prioritas config. */
    public function availableProviders(): array
    {
        $order = config('services.ai_lartas.order', ['anthropic', 'openai', 'gemini', 'deepseek']);
        return array_values(array_filter($order, fn ($p) => ! empty(config("services.$p.key"))));
    }

    public function isConfigured(): bool
    {
        return count($this->availableProviders()) > 0;
    }

    /** Daftar provider yang akan dicoba (hormati AI_LARTAS_PROVIDER + fallback). */
    protected function candidateProviders(): array
    {
        $available = $this->availableProviders();
        if (empty($available)) {
            return [];
        }

        $preferred = config('services.ai_lartas.provider', 'auto');
        if ($preferred !== 'auto' && in_array($preferred, $available, true)) {
            $rest = array_values(array_filter($available, fn ($p) => $p !== $preferred));
            $candidates = array_merge([$preferred], $rest);
        } else {
            $candidates = $available;
        }

        return config('services.ai_lartas.fallback', true) ? $candidates : array_slice($candidates, 0, 1);
    }

    /**
     * Analisa lartas untuk shipment → simpan & kembalikan LartasAnalysis.
     * @throws RuntimeException pesan ramah bila gagal.
     */
    public function analyze(Shipment $shipment, ?int $userId = null): LartasAnalysis
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Fitur AI Lartas belum aktif. Admin perlu mengisi salah satu API key AI (ANTHROPIC/OPENAI/GEMINI/DEEPSEEK) di server.');
        }

        $hs = trim((string) $shipment->hs_code);
        if ($hs === '') {
            throw new RuntimeException('HS code belum diisi pada shipment ini. Isi HS code dulu sebelum analisa lartas.');
        }

        $service = strtolower($shipment->service_type ?: 'import');
        $commodity = trim((string) ($shipment->commodity ?: ''));

        [$system, $user] = $this->buildPrompt($hs, $service, $commodity);

        $result = null;
        $usedProvider = null;
        $lastError = null;

        foreach ($this->candidateProviders() as $provider) {
            try {
                $raw = $this->callProvider($provider, $system, $user);
                $parsed = $this->parseJson($raw);
                if (is_array($parsed) && array_key_exists('recommendations', $parsed)) {
                    $result = $this->normalize($parsed);
                    $usedProvider = $provider;
                    break;
                }
                $lastError = "Jawaban {$provider} tidak dapat dibaca.";
                Log::warning("LartasAiService parse gagal ($provider). Raw: " . mb_substr((string) $raw, 0, 500));
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("LartasAiService gagal ($provider): " . $e->getMessage());
            }
        }

        if ($result === null) {
            throw new RuntimeException($lastError ?: 'Semua layanan AI gagal merespons. Coba lagi nanti.');
        }

        return LartasAnalysis::updateOrCreate(
            ['shipment_id' => $shipment->id],
            [
                'hs_code'         => $hs,
                'service_type'    => $service,
                'commodity'       => $commodity,
                'recommendations' => $result['recommendations'] ?? [],
                'summary'         => trim(($result['summary'] ?? '') . ($result['hs_note'] ?? '' ? "\n\nCatatan HS: " . $result['hs_note'] : '')),
                'model'           => $usedProvider . ':' . config("services.$usedProvider.model"),
                'generated_by'    => $userId,
                'generated_at'    => now(),
            ]
        );
    }

    // ================= Prompt =================

    protected function buildPrompt(string $hs, string $service, string $commodity): array
    {
        $catalog = DocumentType::active()
            ->where('category', 'lartas')
            ->forService($service)
            ->orderBy('sort_order')
            ->get();

        $catalogText = $catalog
            ->map(fn ($t) => '- ' . $t->doc_type . (empty($t->aliases) ? '' : ' (alias: ' . implode(', ', $t->aliases) . ')'))
            ->implode("\n");

        $arah = $service === 'export' ? 'EKSPOR' : ($service === 'import' ? 'IMPOR' : strtoupper($service));

        $system = <<<SYS
Anda asisten kepatuhan kepabeanan Indonesia (Bea Cukai / INSW / Lartas) untuk sebuah PPJK/freight forwarder.
Tugas Anda: dari HS code yang SUDAH ditetapkan, perkirakan dokumen izin/lartas apa yang KEMUNGKINAN diperlukan untuk proses {$arah}.

ATURAN KETAT:
- Anda HANYA memberi rekomendasi awal untuk mempercepat penyiapan dokumen. Keputusan akhir ada di manusia. Sumber otoritatif adalah INSW (insw.go.id) — Anda BUKAN pengganti INSW.
- UTAMAKAN PRESISI, BUKAN KELENGKAPAN. Lebih baik sedikit rekomendasi yang tepat daripada banyak tapi keliru. JANGAN melebih-lebihkan.
- Kenyataannya, SEBAGIAN BESAR barang hanya butuh SEDIKIT izin lartas (bahkan sering hanya SATU jenis, mis. hanya Karantina), atau tidak ada sama sekali. Jangan menambahkan izin hanya karena "mungkin".
- Hanya rekomendasikan sebuah izin bila ada DASAR REGULASI yang cukup spesifik untuk HS ini yang Anda yakini. Bila ragu, JANGAN masukkan sebagai rekomendasi — cukup singgung di "hs_note" bahwa perlu dicek di INSW.
- Contoh kalibrasi: produk pertanian/pangan segar/olahan sederhana (mis. kopi, rempah, buah) untuk impor UMUMNYA hanya wajib KARANTINA (Sertifikat/Izin Karantina). Izin lain (BPOM/Halal/SNI/PI) HANYA berlaku pada kondisi khusus (mis. sudah kemasan ritel siap edar, komoditas yang diatur tata niaganya) — jangan asumsikan berlaku kecuali deskripsi barang jelas mengarah ke sana.
- Peraturan Lartas SANGAT DINAMIS. JANGAN mengklaim kepastian. Nyatakan tingkat kemungkinan secara jujur (banyak kasus hanya "sedang"/"rendah").
- Jangan mengarang nomor peraturan spesifik. Boleh menyebut instansi/kementerian penerbit secara umum.
- Petakan rekomendasi ke KATALOG DOKUMEN M2B berikut jika sesuai (pakai nama persis; set in_catalog=true). Jika ada izin relevan di luar katalog, boleh usulkan dengan in_catalog=false.
- WAJIB sesuaikan rekomendasi & alasan SPESIFIK dengan ARAH ({$arah}). Untuk EKSPOR fokus pada dokumen ekspor (mis. Phytosanitary/Karantina ekspor, LS ekspor, COO/SKA, PE, Fumigasi) — JANGAN pakai logika impor. Untuk IMPOR fokus pada izin impor. Jika sebuah dokumen relevan hanya pada arah tertentu, jangan bawa ke arah yang lain.
- Bila HS code KURANG DARI 6 DIGIT atau deskripsi komoditas KOSONG: turunkan tingkat keyakinan, JANGAN memaksakan rekomendasi spesifik, dan TEGASKAN di "hs_note" bahwa HS perlu dilengkapi ke 6-8 digit / komoditas perlu diisi agar akurat (persyaratan lartas sering berbeda antar subpos).
- Fokus HANYA pada izin/lartas (bukan invoice/packing list/BL biasa).
- Jawab dalam Bahasa Indonesia yang ringkas dan jelas.

KATALOG DOKUMEN LARTAS M2B:
{$catalogText}

FORMAT OUTPUT: kembalikan HANYA JSON valid (tanpa teks lain, tanpa markdown) dengan struktur:
{
  "summary": "ringkasan 1-2 kalimat kondisi lartas untuk HS ini",
  "hs_note": "catatan bila HS tampak janggal/terlalu umum, atau string kosong",
  "recommendations": [
    {
      "doc_type": "nama dokumen",
      "in_catalog": true,
      "likelihood": "tinggi|sedang|rendah",
      "instansi": "kementerian/lembaga penerbit (umum)",
      "reason": "alasan singkat mengapa mungkin diperlukan"
    }
  ]
}
Jika kemungkinan besar barang BEBAS lartas, kembalikan recommendations: [] dan jelaskan di summary.
SYS;

        $user = "HS Code: {$hs}\nArah: {$arah}\nDeskripsi komoditas: " . ($commodity !== '' ? $commodity : '(tidak diisi)') . "\n\nBerikan analisa lartas dalam format JSON yang diminta.";

        return [$system, $user];
    }

    // ================= Provider dispatch =================

    protected function callProvider(string $provider, string $system, string $user): string
    {
        return match ($provider) {
            'anthropic' => $this->callAnthropic($system, $user),
            'openai'    => $this->callOpenAiCompatible('https://api.openai.com/v1/chat/completions', 'openai', $system, $user),
            'deepseek'  => $this->callOpenAiCompatible('https://api.deepseek.com/chat/completions', 'deepseek', $system, $user),
            'gemini'    => $this->callGemini($system, $user),
            default     => throw new RuntimeException("Provider AI tidak dikenal: {$provider}"),
        };
    }

    protected function callAnthropic(string $system, string $user): string
    {
        $resp = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 1500,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $user]],
        ]);

        $this->guard($resp, 'Anthropic');
        return (string) data_get($resp->json(), 'content.0.text', '');
    }

    /** OpenAI & DeepSeek berbagi format chat/completions. */
    protected function callOpenAiCompatible(string $endpoint, string $provider, string $system, string $user): string
    {
        $resp = Http::withToken(config("services.$provider.key"))
            ->timeout(45)
            ->post($endpoint, [
                'model'    => config("services.$provider.model"),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.2,
            ]);

        $this->guard($resp, ucfirst($provider));
        return (string) data_get($resp->json(), 'choices.0.message.content', '');
    }

    protected function callGemini(string $system, string $user): string
    {
        $model = config('services.gemini.model');
        $key = config('services.gemini.key');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $resp = Http::withHeaders(['content-type' => 'application/json'])
            ->timeout(45)
            ->post($endpoint . '?key=' . urlencode($key), [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents'           => [['parts' => [['text' => $user]]]],
                'generationConfig'   => ['responseMimeType' => 'application/json', 'temperature' => 0.2],
            ]);

        $this->guard($resp, 'Gemini');
        return (string) data_get($resp->json(), 'candidates.0.content.parts.0.text', '');
    }

    protected function guard(\Illuminate\Http\Client\Response $resp, string $label): void
    {
        if ($resp->successful()) {
            return;
        }
        Log::warning("LartasAiService {$label} non-200: " . $resp->status() . ' ' . mb_substr($resp->body(), 0, 300));
        $msg = in_array($resp->status(), [401, 403], true)
            ? "API key {$label} tidak valid / ditolak."
            : "Layanan {$label} bermasalah (kode {$resp->status()}).";
        throw new RuntimeException($msg);
    }

    // ================= Parsing =================

    protected function normalize(array $parsed): array
    {
        $parsed['recommendations'] = collect($parsed['recommendations'] ?? [])
            ->filter(fn ($r) => is_array($r) && ! empty($r['doc_type']))
            ->map(fn ($r) => [
                'doc_type'   => (string) $r['doc_type'],
                'in_catalog' => (bool) ($r['in_catalog'] ?? false),
                'likelihood' => in_array($r['likelihood'] ?? '', ['tinggi', 'sedang', 'rendah'], true) ? $r['likelihood'] : 'sedang',
                'instansi'   => (string) ($r['instansi'] ?? ''),
                'reason'     => (string) ($r['reason'] ?? ''),
            ])
            ->values()
            ->all();

        return $parsed;
    }

    /** Ekstrak JSON dari teks (buang code fence / teks pembungkus bila ada). */
    protected function parseJson(string $text): mixed
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $decoded = json_decode(trim($text), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return null;
    }
}
