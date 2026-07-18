<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master jenis dokumen (katalog). Sumber untuk checklist, preset, & saran AI.
 */
class DocumentType extends Model
{
    protected $fillable = [
        'doc_type', 'aliases', 'category', 'service_type', 'mode', 'level',
        'responsibility', 'conditional', 'is_status_trigger', 'is_payment_obligation',
        'has_expiry', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_status_trigger' => 'boolean',
        'is_payment_obligation' => 'boolean',
        'has_expiry' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeShipmentLevel($q)
    {
        return $q->where('level', 'shipment');
    }

    /** Berlaku untuk service_type tertentu (mengikutkan 'all'). */
    public function scopeForService($q, string $serviceType)
    {
        return $q->whereIn('service_type', [strtolower($serviceType), 'all']);
    }
}
