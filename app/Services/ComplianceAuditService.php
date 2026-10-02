<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Shipment;
use App\Models\ShipmentComplianceAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ComplianceAuditService
{
    public const DISCLAIMER = 'Laporan Pra-Audit Kepatuhan Berkas ini disusun oleh Tim M2B menggunakan sistem pendukung kecerdasan buatan terenkripsi sebagai langkah kehati-hatian pra-aju (pre-clearance diligence). Analisis ini bertujuan mengidentifikasi potensi ketidaksesuaian formil sejak dini. Rekomendasi bersifat advis internal dan tidak menggantikan keputusan resmi Pejabat Bea dan Cukai yang berwenang.';

    /**
     * Periksa apakah provider AI terkonfigurasi.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.gemini.key')) || ! empty(config('services.deepseek.key'));
    }

    /**
     * Jalankan audit kepatuhan berkas untuk shipment.
     */
    public function auditShipment(Shipment $shipment, ?int $userId = null): ShipmentComplianceAudit
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Layanan AI Audit belum aktif. Pastikan GEMINI_API_KEY atau DEEPSEEK_API_KEY terisi pada konfigurasi server.');
        }

        $documents = $shipment->documents()->get();
        if ($documents->isEmpty()) {
            throw new RuntimeException('Belum ada dokumen pengapalan yang diunggah untuk shipment ini. Unggah minimal B/L, Invoice, atau Packing List terlebih dahulu.');
        }

        // Susun snapshot berkas
        $snapshots = [];
        $docSummaries = [];

        foreach ($documents as $doc) {
            $label = $doc->document_type_label ?: ($doc->document_type ?: 'Lainnya');
            $snapshots[] = [
                'id' => $doc->id,
                'type' => $doc->document_type,
                'label' => $label,
                'filename' => $doc->filename,
                'size' => $doc->file_size_human,
                'uploaded_at' => $doc->uploaded_at?->toDateTimeString(),
            ];

            $docSummaries[] = "- Tipe Dokumen: {$label} | Nama Berkas: {$doc->filename} | Deskripsi: " . ($doc->description ?: '-');
        }

        $shipmentInfo = [
            'bl_number' => $shipment->awb_number ?: ($shipment->bl_number ?: '-'),
            'service_type' => $shipment->service_type ?: 'Import',
            'commodity' => $shipment->commodity ?: '-',
            'hs_code' => $shipment->hs_code ?: '-',
            'origin' => $shipment->origin ?: '-',
            'destination' => $shipment->destination ?: '-',
            'shipper' => $shipment->shipper_name ?: ($shipment->customer?->company_name ?: '-'),
            'vessel' => $shipment->vessel_name ?: ($shipment->transport_name ?: '-'),
            'packages' => $shipment->packages_count ? "{$shipment->packages_count} packages" : '-',
            'gross_weight' => $shipment->gross_weight ? "{$shipment->gross_weight} kg" : '-',
            'net_weight' => $shipment->net_weight ? "{$shipment->net_weight} kg" : '-',
            'cbm' => $shipment->volume ? "{$shipment->volume} CBM" : '-',
        ];

        [$systemPrompt, $userPrompt] = $this->buildPrompts($shipmentInfo, $docSummaries);

        // Kumpulkan file inline data (PDF/Gambar) untuk dokumen pabean utama
        $inlineParts = $this->collectDocumentParts($documents);

        // Prioritas pemanggilan: Gemini lebih dulu (karena multimodal & context besar), fallback ke DeepSeek
        $provider = ! empty(config('services.gemini.key')) ? 'gemini' : 'deepseek';
        $model = $provider === 'gemini'
            ? config('services.gemini.compliance_model', env('GEMINI_COMPLIANCE_MODEL', 'gemini-1.5-pro'))
            : config('services.deepseek.model', 'deepseek-chat');

        $rawResponse = $this->callProvider($provider, $systemPrompt, $userPrompt, $inlineParts, $model);
        $parsed = $this->parseResponse($rawResponse);

        $findings = array_map(function ($f, $idx) {
            return [
                'id' => 'f_' . ($idx + 1),
                'document' => $f['document'] ?? 'Umum',
                'severity' => strtoupper($f['severity'] ?? 'LOW'), // LOW, MEDIUM, HIGH, CRITICAL
                'title' => $f['title'] ?? 'Catatan Dokumen',
                'description' => $f['description'] ?? '',
                'recommendation' => $f['recommendation'] ?? '',
                'status' => 'OPEN',
                'resolution_note' => null,
                'resolved_by_id' => null,
                'resolved_by_name' => null,
                'resolved_at' => null,
            ];
        }, $parsed['findings'] ?? [], array_keys($parsed['findings'] ?? []));

        $audit = ShipmentComplianceAudit::create([
            'shipment_id' => $shipment->id,
            'audited_by' => $userId ?: Auth::id(),
            'provider' => $provider,
            'model' => $model,
            'overall_status' => strtoupper($parsed['overall_status'] ?? 'COMPLIANT'),
            'compliance_score' => (int) ($parsed['compliance_score'] ?? 95),
            'summary' => $parsed['summary'] ?? 'Pra-audit dokumen selesai dilakukan.',
            'findings' => $findings,
            'document_snapshots' => $snapshots,
            'token_usage' => [
                'provider' => $provider,
                'model' => $model,
                'multimodal_docs_count' => count($inlineParts),
                'audited_at' => now()->toDateTimeString(),
            ],
            'disclaimer' => self::DISCLAIMER,
        ]);

        ActivityLog::record(
            'Shipment',
            'PRA-AUDIT DOKUMEN (AI)',
            $shipment->awb_number ?: "ID-{$shipment->id}",
            "Status: {$audit->overall_status} (Skor: {$audit->compliance_score}%) [{$model}]"
        );

        return $audit;
    }

    /**
     * Ambil konten biner PDF / Gambar dari storage untuk dibaca langsung oleh Gemini Vision.
     */
    protected function collectDocumentParts($documents): array
    {
        $parts = [];
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $maxBytes = 4 * 1024 * 1024; // Maksimal 4 MB per file agar efisien

        // Prioritaskan dokumen inti
        $priorityTypes = ['bill of lading', 'invoice', 'packing list', 'form e', 'coo', 'ska'];

        $sorted = $documents->sortByDesc(function ($doc) use ($priorityTypes) {
            $type = strtolower($doc->document_type ?: ($doc->description ?: ''));
            foreach ($priorityTypes as $p) {
                if (str_contains($type, $p)) {
                    return 2;
                }
            }
            return 1;
        })->take(4); // Maksimal 4 berkas inti terpenting

        foreach ($sorted as $doc) {
            $mime = strtolower((string) ($doc->mime_type ?: ''));
            if (! in_array($mime, $allowedMimes, true)) {
                // Deteksi dari ekstensi jika mime_type kosong
                $ext = strtolower(pathinfo($doc->filename, PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    $mime = 'application/pdf';
                } elseif (in_array($ext, ['jpg', 'jpeg'])) {
                    $mime = 'image/jpeg';
                } elseif ($ext === 'png') {
                    $mime = 'image/png';
                } else {
                    continue;
                }
            }

            try {
                $rawContent = null;

                // Cek backblaze / s3 / local
                if ($doc->file_path) {
                    if (Storage::disk('backblaze')->exists($doc->file_path)) {
                        $rawContent = Storage::disk('backblaze')->get($doc->file_path);
                    } elseif (Storage::disk('public')->exists($doc->file_path)) {
                        $rawContent = Storage::disk('public')->get($doc->file_path);
                    } elseif (Storage::disk('local')->exists($doc->file_path)) {
                        $rawContent = Storage::disk('local')->get($doc->file_path);
                    }
                }

                if ($rawContent && strlen($rawContent) <= $maxBytes) {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $mime,
                            'data' => base64_encode($rawContent),
                        ],
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal load file untuk compliance audit ($doc->filename): " . $e->getMessage());
            }
        }

        return $parts;
    }

    protected function callProvider(string $provider, string $system, string $user, array $inlineParts = [], ?string $model = null): string
    {
        if ($provider === 'gemini') {
            return $this->callGemini($system, $user, $inlineParts, $model);
        }

        return $this->callDeepSeek($system, $user, $model);
    }

    protected function callGemini(string $system, string $user, array $inlineParts = [], ?string $model = null): string
    {
        $key = config('services.gemini.key');
        $modelName = $model ?: config('services.gemini.compliance_model', env('GEMINI_COMPLIANCE_MODEL', 'gemini-1.5-pro'));
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . urlencode($key);

        $parts = [];
        // Masukkan berkas dokumen fisik jika ada
        foreach ($inlineParts as $p) {
            $parts[] = $p;
        }
        // Masukkan teks instruksi user
        $parts[] = ['text' => $user];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(45)
            ->post($endpoint, [
                'system_instruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => [
                    ['parts' => $parts],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                ],
            ]);

        if (! $response->successful()) {
            // Bila model Pro rate-limited atau model belum terdaftar, fallback otomatis ke flash
            if ($modelName !== 'gemini-2.5-flash' && $modelName !== 'gemini-1.5-flash') {
                Log::warning("Gemini Pro gagal ($modelName), mencoba fallback ke Flash...");
                return $this->callGemini($system, $user, $inlineParts, 'gemini-2.5-flash');
            }

            Log::error('Gemini Compliance Audit Failed: ' . $response->body());
            throw new RuntimeException('Panggilan Gemini API gagal: ' . ($response->json('error.message') ?: $response->status()));
        }

        return (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }

    protected function callDeepSeek(string $system, string $user): string
    {
        $key = config('services.deepseek.key');
        $model = config('services.deepseek.model', 'deepseek-chat');

        $response = Http::withToken($key)
            ->timeout(35)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
            ]);

        if (! $response->successful()) {
            Log::error('DeepSeek Compliance Audit Failed: ' . $response->body());
            throw new RuntimeException('Panggilan DeepSeek API gagal: ' . ($response->json('error.message') ?: $response->status()));
        }

        return (string) data_get($response->json(), 'choices.0.message.content', '');
    }

    protected function parseResponse(string $raw): array
    {
        $clean = trim($raw);
        // Tangani kemungkinan blok markdown ```json ... ```
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $clean, $matches)) {
            $clean = $matches[1];
        }

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            Log::warning('Gagal parse JSON Compliance Audit: ' . mb_substr($raw, 0, 500));
            return [
                'overall_status' => 'ATTENTION',
                'compliance_score' => 80,
                'summary' => 'Dokumen berhasil dibaca namun respons sistem memerlukan konfirmasi manual.',
                'findings' => [
                    [
                        'document' => 'Umum',
                        'severity' => 'LOW',
                        'title' => 'Verifikasi Dokumen Manual',
                        'description' => 'Silakan lakukan verifikasi silang fisik antara B/L dan Packing List.',
                        'recommendation' => 'Pastikan nomor B/L dan jumlah koli sesuai sebelum diajukan ke pabean.',
                    ],
                ],
            ];
        }

        return $decoded;
    }
}
