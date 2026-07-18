<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\Shipment;

/**
 * Logika checklist dokumen per shipment (F1).
 *
 * Prinsip: is_mandatory HANYA di-set manusia (confirmMandatory/requestFromCustomer).
 * autoFulfillOnUpload memanfaatkan pencocokan description↔doc_type (dokumen
 * menyimpan tipe spesifik di kolom `description`, selaras computeMilestoneFromDocuments).
 */
class DocumentChecklistService
{
    /**
     * Seed baseline dokumen "selalu" untuk shipment (idempotent).
     * Transport doc difilter sesuai moda shipment (best-effort).
     */
    public function buildForShipment(Shipment $shipment): void
    {
        $mode = strtolower($shipment->shipment_type ?? $shipment->mode ?? 'sea');

        $types = DocumentType::active()->shipmentLevel()
            ->forService($shipment->service_type ?? 'import')
            ->where('conditional', 'selalu')
            ->where(function ($q) use ($mode) {
                $q->whereNull('mode')->orWhere('mode', $mode);
            })
            ->orderBy('sort_order')
            ->get();

        foreach ($types as $t) {
            DocumentRequirement::firstOrCreate(
                ['shipment_id' => $shipment->id, 'doc_type' => $t->doc_type],
                [
                    'responsibility' => $t->responsibility,
                    'is_mandatory'   => false, // manusia yang tetapkan wajib
                    'status'         => 'pending',
                    'source'         => 'preset',
                ]
            );
        }

        // Pastikan yang sudah ter-upload langsung tercermin.
        foreach ($shipment->documents as $doc) {
            $this->autoFulfillOnUpload($doc, 'admin', false);
        }
    }

    /** Tambah item checklist manual. */
    public function addRequirement(Shipment $shipment, string $docType, array $opts = []): DocumentRequirement
    {
        $type = $this->typeFor($docType);

        return DocumentRequirement::updateOrCreate(
            ['shipment_id' => $shipment->id, 'doc_type' => $docType],
            array_merge([
                'responsibility' => $type->responsibility ?? ($opts['responsibility'] ?? 'customer'),
                'source'         => $opts['source'] ?? 'manual',
                'note'           => $opts['note'] ?? null,
            ], $opts['fields'] ?? [])
        );
    }

    /** Minta dokumen ke customer (status=requested + wajib, dgn jejak). */
    public function requestFromCustomer(Shipment $shipment, string $docType, ?string $note, ?string $dueDate, ?int $userId = null): DocumentRequirement
    {
        $uid = $userId ?? 1; // fallback user sistem (konsisten dgn service lain)
        $req = $this->addRequirement($shipment, $docType, ['source' => 'manual']);
        $req->update([
            'responsibility' => 'customer',
            'is_mandatory'   => true,
            'status'         => $req->status === 'fulfilled' ? 'fulfilled' : 'requested',
            'note'           => $note,
            'due_date'       => $dueDate,
            'requested_by'   => $uid,
            'requested_at'   => now(),
            'confirmed_by'   => $uid,
            'confirmed_at'   => now(),
        ]);

        return $req;
    }

    /** Tetapkan sebuah requirement jadi WAJIB (keputusan manusia). */
    public function confirmMandatory(DocumentRequirement $req, ?int $userId = null): DocumentRequirement
    {
        $req->update([
            'is_mandatory' => true,
            'confirmed_by' => $userId ?? 1,
            'confirmed_at' => now(),
        ]);

        return $req;
    }

    /**
     * Cocokkan dokumen yang di-upload ke requirement yang belum terpenuhi.
     * $role = 'customer' | 'admin' (siapa yang upload).
     */
    public function autoFulfillOnUpload(Document $doc, string $role = 'admin', bool $createIfMissing = false): ?DocumentRequirement
    {
        $descL = strtolower((string) $doc->description);
        $fileL = strtolower((string) $doc->filename);
        if ($descL === '' && $fileL === '') {
            return null;
        }

        $reqs = DocumentRequirement::where('shipment_id', $doc->shipment_id)
            ->where('status', '!=', 'fulfilled')
            ->orderByDesc('is_mandatory')
            ->get();

        foreach ($reqs as $req) {
            foreach ($this->namesFor($req->doc_type) as $name) {
                $n = strtolower(trim($name));
                if ($n === '') {
                    continue;
                }
                $nFile = str_replace([' ', '/'], '_', $n); // nama file admin: BILL_OF_LADING_...
                // Cocok bila deskripsi ATAU nama file MENGANDUNG nama/alias (bukan cuma
                // diawali) — menangkap "Email Attachment: Invoice.pdf", "BPN_xxx", dll.
                if (str_contains($descL, $n) || str_contains($fileL, $n) || str_contains($fileL, $nFile)) {
                    $req->update([
                        'status'                => 'fulfilled',
                        'fulfilled_document_id' => $doc->id,
                        'fulfilled_by_role'     => $role,
                    ]);
                    return $req;
                }
            }
        }

        return null; // tak ada requirement cocok — biarkan
    }

    /** Cocokkan ulang SELURUH dokumen shipment ke checklist (setelah upgrade matching). */
    public function rescanShipment(Shipment $shipment): int
    {
        $count = 0;
        foreach ($shipment->documents as $doc) {
            $role = ($doc->document_type ?? '') === 'customer_upload' ? 'customer' : 'admin';
            if ($this->autoFulfillOnUpload($doc, $role)) {
                $count++;
            }
        }
        return $count;
    }

    /** Tandai lengkap MANUAL (verifikasi staf); opsional tautkan dokumen. */
    public function markFulfilledManual(DocumentRequirement $req, ?int $userId = null, ?int $documentId = null): void
    {
        $req->update([
            'status'                => 'fulfilled',
            'fulfilled_by_role'     => 'manual',
            'fulfilled_document_id' => $documentId,
            'confirmed_by'          => $userId ?? 1,
            'confirmed_at'          => now(),
        ]);
    }

    /** Batalkan pemenuhan → kembali pending. */
    public function revertFulfillment(DocumentRequirement $req): void
    {
        $req->update([
            'status'                => 'pending',
            'fulfilled_document_id' => null,
            'fulfilled_by_role'     => null,
        ]);
    }

    /** Skor kesiapan: berbasis dokumen WAJIB (bila ada), fallback semua. */
    public function readinessScore(Shipment $shipment): array
    {
        $reqs = DocumentRequirement::where('shipment_id', $shipment->id)->get();
        $mandatory = $reqs->where('is_mandatory', true);
        $basis = $mandatory->count() ? $mandatory : $reqs;

        $total = $basis->count();
        $fulfilled = $basis->where('status', 'fulfilled')->count();
        $percent = $total ? (int) round($fulfilled / $total * 100) : 100;

        return [
            'total'     => $total,
            'fulfilled' => $fulfilled,
            'pending'   => $total - $fulfilled,
            'percent'   => $percent,
        ];
    }

    /** Nama + alias untuk pencocokan. */
    protected function namesFor(string $docType): array
    {
        $type = $this->typeFor($docType);
        return array_merge([$docType], $type?->aliases ?? []);
    }

    protected function typeFor(string $docType): ?DocumentType
    {
        return DocumentType::where('doc_type', $docType)->first();
    }
}
