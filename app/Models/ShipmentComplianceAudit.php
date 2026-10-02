<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentComplianceAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'audited_by',
        'provider',
        'model',
        'overall_status',
        'compliance_score',
        'summary',
        'findings',
        'document_snapshots',
        'token_usage',
        'disclaimer',
    ];

    protected $casts = [
        'compliance_score' => 'integer',
        'findings' => 'array',
        'document_snapshots' => 'array',
        'token_usage' => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    /**
     * Hitung status efektif setelah mempertimbangkan catatan resolusi manual staf.
     */
    public function getEffectiveStatusAttribute(): string
    {
        $findings = $this->findings ?: [];
        if (empty($findings)) {
            return 'COMPLIANT';
        }

        $hasOpenCritical = false;
        $hasOpenAttention = false;

        foreach ($findings as $f) {
            $isResolved = ! empty($f['resolved_at']) || ($f['status'] ?? '') === 'RESOLVED';
            if ($isResolved) {
                continue;
            }

            $severity = strtoupper($f['severity'] ?? 'LOW');
            if ($severity === 'HIGH' || $severity === 'CRITICAL') {
                $hasOpenCritical = true;
            } elseif ($severity === 'MEDIUM' || $severity === 'ATTENTION') {
                $hasOpenAttention = true;
            }
        }

        if ($hasOpenCritical) {
            return 'CRITICAL';
        }
        if ($hasOpenAttention) {
            return 'ATTENTION';
        }

        return 'COMPLIANT';
    }

    /**
     * Resolusi temuan oleh staf secara manual tanpa panggil ulang AI.
     */
    public function resolveFinding(int $index, string $resolutionNote, User $user): bool
    {
        $findings = $this->findings ?: [];
        if (! isset($findings[$index])) {
            return false;
        }

        $findings[$index]['status'] = 'RESOLVED';
        $findings[$index]['resolution_note'] = trim($resolutionNote);
        $findings[$index]['resolved_by_id'] = $user->id;
        $findings[$index]['resolved_by_name'] = $user->name;
        $findings[$index]['resolved_at'] = now()->toDateTimeString();

        $this->findings = $findings;

        // Jika semua temuan beresolusi, perbarui skor dan status
        if ($this->effective_status === 'COMPLIANT') {
            $this->compliance_score = max($this->compliance_score, 95);
        }

        return $this->save();
    }

    /**
     * Susun teks draf WhatsApp profesional untuk dikirim ke penanggung jawab klien.
     */
    public function buildWhatsAppText(): string
    {
        $shipment = $this->shipment;
        $customerName = $shipment->customer?->company_name ?: ($shipment->customer?->name ?: 'Bapak/Ibu');
        $bl = $shipment->awb_number ?: ($shipment->bl_number ?: '-');
        $vessel = $shipment->vessel_name ?: ($shipment->transport_name ?: '-');

        $text = "*UPDATE PRA-VERIFIKASI DOKUMEN PABEAN — M2B LOGISTICS*\n";
        $text .= "Kepada Yth. {$customerName}\n\n";
        $text .= "Berikut ringkasan hasil uji kesiapan dokumen impor pra-aju untuk pengapalan Anda:\n";
        $text .= "• *No. B/L / AWB:* {$bl}\n";
        $text .= "• *Vessel / Sarana Pengangkut:* {$vessel}\n";
        $text .= "• *Status Kesiapan:* " . ($this->effective_status === 'COMPLIANT' ? '✅ SIAP AJU (COMPLIANT)' : '⚠️ PERLU KONFIRMASI DOKUMEN') . "\n\n";

        $findings = $this->findings ?: [];
        $openFindings = array_filter($findings, fn ($f) => empty($f['resolved_at']) && ($f['status'] ?? '') !== 'RESOLVED');

        if (! empty($openFindings)) {
            $text .= "*Catatan Dokumen yang Perlu Dikonfirmasi:*\n";
            $i = 1;
            foreach ($openFindings as $f) {
                $doc = $f['document'] ?? 'Dokumen';
                $title = $f['title'] ?? 'Catatan Kepatuhan';
                $rec = $f['recommendation'] ?? ($f['description'] ?? '');
                $text .= "{$i}. [{$doc}] *{$title}*\n   ↳ {$rec}\n";
                $i++;
            }
            $text .= "\nMohon bantuan konfirmasinya agar saat kapal sandar proses pengeluaran kargo dapat segera kami jalankan tanpa kendala di sistem pabean.\n\n";
        } else {
            $text .= "Seluruh dokumen utama (B/L, Invoice, Packing List, serta SKA) telah sinkron dan siap diproses ke sistem kepabeanan.\n\n";
        }

        $text .= "*Salam hormat,*\n*Tim M2B*\n_PT Multi Modern Berdikari_";

        return $text;
    }
}
