<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Translate teks Catatan/Syarat & Ketentuan quotation (HTML) dari Bahasa Indonesia
 * ke Inggris via AI, HANYA dipanggil saat admin klik tombol "Translate" secara manual
 * (bukan otomatis tiap render/save) — supaya tidak boros token/biaya API.
 *
 * Pakai provider yang sama dgn LartasAiService (services.anthropic/openai/gemini/deepseek),
 * tapi berdiri sendiri (tidak share kode) karena fiturnya independen — jangan sampai
 * perubahan di sini berisiko ke fitur Lartas yang sudah berjalan.
 */
class QuotationTranslationService
{
    protected function order(): array
    {
        return ['anthropic', 'openai', 'gemini', 'deepseek'];
    }

    public function availableProviders(): array
    {
        return array_values(array_filter($this->order(), fn ($p) => !empty(config("services.$p.key"))));
    }

    public function isConfigured(): bool
    {
        return count($this->availableProviders()) > 0;
    }

    /**
     * @throws RuntimeException pesan ramah bila gagal / belum dikonfigurasi.
     */
    public function translateNotesToEnglish(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (!$this->isConfigured()) {
            throw new RuntimeException('Fitur translate AI belum aktif. Admin perlu mengisi salah satu API key AI (ANTHROPIC/OPENAI/GEMINI/DEEPSEEK) di server.');
        }

        $system = <<<SYS
Anda penerjemah profesional Bahasa Indonesia ke Inggris untuk dokumen bisnis logistik/freight forwarding & kepabeanan.

ATURAN KETAT:
- Terjemahkan HANYA teksnya ke Bahasa Inggris formal/bisnis. JANGAN mengubah, menambah, atau menghapus tag HTML apapun.
- Pertahankan struktur HTML persis sama (jumlah & jenis tag, urutan <ol>/<ul>/<li>/<div>/<strong>, dsb).
- Istilah kepabeanan Indonesia yang tidak punya padanan resmi umum (mis. SPPB, NPE, PPh, PPN) boleh dipertahankan aslinya, cukup beri padanan Inggris singkat dalam kurung bila perlu.
- Jangan menambahkan komentar, catatan, atau markdown code fence apapun. Output HANYA HTML hasil terjemahan, tanpa teks lain.
SYS;

        $user = "Terjemahkan HTML berikut ke Bahasa Inggris:\n\n{$html}";

        $lastError = null;
        foreach ($this->availableProviders() as $provider) {
            try {
                $raw = $this->callProvider($provider, $system, $user);
                $clean = $this->cleanOutput($raw);
                if ($clean !== '') {
                    return $clean;
                }
                $lastError = "Jawaban {$provider} kosong.";
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("QuotationTranslationService gagal ($provider): " . $e->getMessage());
            }
        }

        throw new RuntimeException($lastError ?: 'Semua layanan AI gagal merespons. Coba lagi nanti.');
    }

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
            'max_tokens' => 2000,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $user]],
        ]);

        $this->guard($resp, 'Anthropic');
        return (string) data_get($resp->json(), 'content.0.text', '');
    }

    protected function callOpenAiCompatible(string $endpoint, string $provider, string $system, string $user): string
    {
        $resp = Http::withToken(config("services.$provider.key"))
            ->timeout(45)
            ->post($endpoint, [
                'model'       => config("services.$provider.model"),
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.2,
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
                'generationConfig'   => ['temperature' => 0.2],
            ]);

        $this->guard($resp, 'Gemini');
        return (string) data_get($resp->json(), 'candidates.0.content.parts.0.text', '');
    }

    protected function guard(\Illuminate\Http\Client\Response $resp, string $label): void
    {
        if ($resp->successful()) {
            return;
        }
        Log::warning("QuotationTranslationService {$label} non-200: " . $resp->status() . ' ' . mb_substr($resp->body(), 0, 300));
        $msg = in_array($resp->status(), [401, 403], true)
            ? "API key {$label} tidak valid / ditolak."
            : "Layanan {$label} bermasalah (kode {$resp->status()}).";
        throw new RuntimeException($msg);
    }

    protected function cleanOutput(string $text): string
    {
        $text = trim($text);
        // Buang code fence kalau model tetap membungkusnya dgn ```html ... ```
        $text = preg_replace('/^```(?:html)?\s*|\s*```$/m', '', $text);
        return trim($text);
    }
}
