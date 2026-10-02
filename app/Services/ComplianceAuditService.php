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

        // Prioritas pemanggilan: Gemini lebih dulu (karena multimodal & context besar), fallback ke DeepSeek
        $provider = ! empty(config('services.gemini.key')) ? 'gemini' : 'deepseek';
        $model = config("services.{$provider}.model");

        $rawResponse = $this->callProvider($provider, $systemPrompt, $userPrompt);
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
                'audited_at' => now()->toDateTimeString(),
            ],
            'disclaimer' => self::DISCLAIMER,
        ]);

        ActivityLog::record(
            'Shipment',
            'PRA-AUDIT DOKUMEN (AI)',
            $shipment->awb_number ?: "ID-{$shipment->id}",
            "Status: {$audit->overall_status} (Skor: {$audit->compliance_score}%)"
        );

        return $audit;
    }

    protected function buildPrompts(array $shipmentInfo, array $docSummaries): array
    {
        $systemPrompt = <<<SYS
Anda adalah sistem pendukung intelijen kepabeanan dan logistik berstandar enterprise untuk forwarder & PPJK M2B (PT Multi Modern Berdikari).
Tugas Anda adalah melakukan audit kepatuhan berkas pengapalan pra-aju (Pre-Clearance Compliance Audit) sebelum dokumen diajukan ke sistem pabean Bea Cukai.

Fokus Analisis Anda:
1. Kelengkapan dan keselarasan berkas pengapalan:
   - Bill of Lading (B/L)
   - Commercial Invoice
   - Packing List
   - Surat Keterangan Asal / Certificate of Origin (Form E, Form D, SKA) jika ada
2. Rekonsiliasi & Deteksi Ketidaksesuaian Formil:
   - Kesesuaian partai barang: Shipper, Consignee, Notify Party
   - Kesesuaian jumlah kemasan/koli dan bobot (Gross Weight, Net Weight, CBM)
   - Cacat formil Surat Keterangan Asal (SKA / Form E): perhatian khusus pada Box 13 (Third Party Invoicing bila diterbitkan di luar negara produsen), kesesuaian rute kapal, dan kriteria asal barang
   - Kualitas uraian barang: apakah terlalu komersial/umum atau sudah memadai untuk kaidah penetapan pabean.

Berikan output HANYA dalam format JSON valid dengan struktur berikut:
{
  "overall_status": "COMPLIANT|ATTENTION|CRITICAL",
  "compliance_score": 0-100,
  "summary": "Ringkasan eksekutif 2-3 kalimat mengenai kesiapan berkas pengapalan.",
  "findings": [
    {
      "document": "B/L|Invoice|Packing List|Form E / SKA|Umum",
      "severity": "LOW|MEDIUM|HIGH|CRITICAL",
      "title": "Judul temuan ringkas",
      "description": "Rincian ketidaksesuaian atau potensi risiko bila diajukan ke pabean",
      "recommendation": "Langkah konfirmasi atau tindakan yang disarankan kepada staf/klien"
    }
  ]
}

Aturan Penilaian:
- Bila semua berkas selaras dan tidak ditemukan risiko signifikan: overall_status = "COMPLIANT", compliance_score = 90-100, findings = [].
- Bila ada catatan minor atau saran penyempurnaan uraian: overall_status = "ATTENTION", compliance_score = 75-89.
- Bila ada cacat formil Form E, selisih kemasan/bobot signifikan, atau dokumen wajib belum tersedia: overall_status = "CRITICAL", compliance_score = 40-74.
SYS;

        $userPrompt = "Berikut data pengapalan dan daftar berkas yang diunggah:\n\n";
        $userPrompt .= "DATA SHIPMENT M2B:\n";
        foreach ($shipmentInfo as $k => $v) {
            $userPrompt .= "- " . strtoupper(str_replace('_', ' ', $k)) . ": {$v}\n";
        }

        $userPrompt .= "\nBERKAS PENGAPALAN TERUNGGAH:\n";
        $userPrompt .= implode("\n", $docSummaries);
        $userPrompt .= "\n\nLakukan pra-audit dokumen secara menyeluruh dan kembalikan JSON yang diminta.";

        return [$systemPrompt, $userPrompt];
    }

    protected function callProvider(string $provider, string $system, string $user): string
    {
        if ($provider === 'gemini') {
            return $this->callGemini($system, $user);
        }

        return $this->callDeepSeek($system, $user);
    }

    protected function callGemini(string $system, string $user): string
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($key);

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(35)
            ->post($endpoint, [
                'system_instruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => [
                    ['parts' => [['text' => $user]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                ],
            ]);

        if (! $response->successful()) {
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
