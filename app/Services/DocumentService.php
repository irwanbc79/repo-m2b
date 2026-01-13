<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Shipment;
use App\Models\ActivityLog;
use App\Events\DocumentUploaded;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    /**
     * Upload a document for a shipment.
     */
    public function uploadDocument(
        UploadedFile $file,
        Shipment $shipment,
        string $documentType,
        ?string $description = null,
        bool $isPublic = false
    ): Document {
        return DB::transaction(function () use ($file, $shipment, $documentType, $description, $isPublic) {
            // Upload to Backblaze B2
            $path = Storage::disk('backblaze')->putFile(
                'shipments/' . $shipment->id,
                $file,
                'public'
            );

            // Create document record
            $document = Document::create([
                'shipment_id' => $shipment->id,
                'uploaded_by' => auth()->id(),
                'document_type' => $documentType,
                'filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_public' => $isPublic,
                'description' => $description,
                'uploaded_at' => now(),
            ]);

            // Log activity
            ActivityLog::log(
                'created',
                "Uploaded document {$document->filename} for shipment {$shipment->awb_number}",
                $document
            );

            // Trigger event for notifications
            event(new DocumentUploaded($document, $shipment));

            return $document;
        });
    }

    /**
     * Upload multiple documents.
     */
    public function uploadMultipleDocuments(
        array $files,
        Shipment $shipment,
        string $documentType,
        ?string $description = null,
        bool $isPublic = false
    ): array {
        $documents = [];

        foreach ($files as $file) {
            $documents[] = $this->uploadDocument(
                $file,
                $shipment,
                $documentType,
                $description,
                $isPublic
            );
        }

        return $documents;
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(Document $document): bool
    {
        return DB::transaction(function () use ($document) {
            $filename = $document->filename;
            $shipment = $document->shipment;

            // Delete file from storage
            $document->deleteFile();

            // Delete database record
            $document->delete();

            // Log activity
            ActivityLog::log(
                'deleted',
                "Deleted document {$filename} from shipment {$shipment->awb_number}"
            );

            return true;
        });
    }

    /**
     * Get document download URL.
     */
    public function getDownloadUrl(Document $document): string
    {
        // Check if user has permission to download
        $this->authorizeDownload($document);

        return $document->getTemporaryDownloadUrl();
    }

    /**
     * Update document details.
     */
    public function updateDocument(Document $document, array $data): Document
    {
        return DB::transaction(function () use ($document, $data) {
            $oldValues = $document->only(array_keys($data));
            
            $document->update($data);

            // Log activity
            ActivityLog::log(
                'updated',
                "Updated document {$document->filename}",
                $document,
                $oldValues,
                $data
            );

            return $document->fresh();
        });
    }

    /**
     * Check if user is authorized to download document.
     */
    protected function authorizeDownload(Document $document): void
    {
        $user = auth()->user();

        // Admin can download all documents
        if ($user->isAdmin()) {
            return;
        }

        // Customer can only download their own shipment documents
        if ($user->isCustomer()) {
            $shipment = $document->shipment;
            if ($shipment->customer_id !== $user->customer->id) {
                abort(403, 'Unauthorized to download this document');
            }
        }
    }

    /**
     * Get documents by shipment with optional filters.
     */
    public function getShipmentDocuments(
        Shipment $shipment,
        ?string $documentType = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = $shipment->documents()->with('uploader');

        if ($documentType) {
            $query->where('document_type', $documentType);
        }

        return $query->latest('uploaded_at')->get();
    }

    /**
     * Validate file upload.
     */
    public function validateFile(UploadedFile $file): array
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        $errors = [];

        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds 10MB limit';
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'File type not allowed';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
