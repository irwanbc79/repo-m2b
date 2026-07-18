<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item checklist dokumen per shipment. is_mandatory HANYA di-set oleh manusia
 * (staf) — bukan AI. status: pending/requested/fulfilled/waived.
 */
class DocumentRequirement extends Model
{
    protected $fillable = [
        'shipment_id', 'doc_type', 'responsibility', 'is_mandatory', 'status',
        'source', 'note', 'due_date', 'requested_by', 'requested_at',
        'fulfilled_document_id', 'fulfilled_by_role', 'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'due_date' => 'date',
        'requested_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function fulfilledDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'fulfilled_document_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function scopePending($q)
    {
        return $q->whereIn('status', ['pending', 'requested']);
    }

    public function scopeForCustomer($q)
    {
        return $q->where('responsibility', 'customer');
    }
}
