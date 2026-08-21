<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEtaRevision extends Model
{
    protected $fillable = [
        'shipment_id', 'previous_eta', 'revised_eta', 'change_days',
        'reason_code', 'reason_notes', 'source_party',
        'information_received_at', 'source_document_id',
        'customer_visible', 'customer_message', 'evidence_customer_visible',
        'published_at', 'viewed_at', 'customer_notified_at', 'created_by',
    ];

    protected $casts = [
        'previous_eta' => 'datetime',
        'revised_eta' => 'datetime',
        'information_received_at' => 'datetime',
        'customer_visible' => 'boolean',
        'evidence_customer_visible' => 'boolean',
        'published_at' => 'datetime',
        'viewed_at' => 'datetime',
        'customer_notified_at' => 'datetime',
        'change_days' => 'integer',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::reasonOptions()[$this->reason_code] ?? 'Lainnya';
    }

    public static function reasonOptions(): array
    {
        return [
            'carrier_schedule' => 'Perubahan jadwal kapal/pesawat',
            'rollover' => 'Vessel/cargo rollover',
            'port_congestion' => 'Kepadatan pelabuhan/bandara',
            'weather' => 'Cuaca',
            'customs' => 'Proses customs clearance',
            'transshipment' => 'Keterlambatan transshipment',
            'documents' => 'Dokumen belum lengkap',
            'vendor' => 'Keterlambatan vendor/trucking',
            'customer_request' => 'Perubahan dari customer',
            'other' => 'Lainnya',
        ];
    }
}
