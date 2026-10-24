<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_id',
        'description', 'is_internal', // <--- TAMBAHKAN INI
        'uploaded_by',
        'document_type',
        'filename',
        'file_path',
        'file_size',
        'mime_type',
        'is_public',
        'description',
        'uploaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'is_internal' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the shipment that owns the document.
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the configured storage disk for documents.
     */
    public function getStorageDisk(): string
    {
        if (config('filesystems.disks.backblaze.key') && config('filesystems.disks.backblaze.bucket')) {
            return 'backblaze';
        }
        if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket')) {
            return 's3';
        }
        return config('filesystems.default', 'local');
    }

    /**
     * Get the document's download URL.
     */
    public function getDownloadUrlAttribute(): string
    {
        return Storage::disk($this->getStorageDisk())->url($this->file_path);
    }

    /**
     * Get the document's temporary download URL (valid for 1 hour).
     */
    public function getTemporaryDownloadUrl(): string
    {
        $disk = $this->getStorageDisk();
        $storage = Storage::disk($disk);
        if (method_exists($storage, 'temporaryUrl')) {
            try {
                return $storage->temporaryUrl(
                    $this->file_path,
                    now()->addHour()
                );
            } catch (\Throwable $e) {
                // Driver may not support temporaryUrl (e.g. local)
            }
        }
        return $storage->url($this->file_path);
    }

    /**
     * Get human readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'invoice' => 'Invoice',
            'packing_list' => 'Packing List',
            'certificate' => 'Certificate',
            'other' => 'Other',
            default => 'Unknown',
        };
    }

    /**
     * Get file icon based on mime type.
     */
    public function getFileIconAttribute(): string
    {
        if (str_starts_with($this->mime_type, 'image/')) {
            return 'photo';
        } elseif ($this->mime_type === 'application/pdf') {
            return 'document-text';
        } elseif (str_contains($this->mime_type, 'word')) {
            return 'document';
        } elseif (str_contains($this->mime_type, 'excel') || str_contains($this->mime_type, 'spreadsheet')) {
            return 'table';
        }
        
        return 'document';
    }

    /**
     * Scope a query to only include public documents.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to filter by document type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Delete the document file from storage.
     */
    public function deleteFile(): bool
    {
        return Storage::disk($this->getStorageDisk())->delete($this->file_path);
    }
}
