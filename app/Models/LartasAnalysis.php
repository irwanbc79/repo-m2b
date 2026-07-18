<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil analisa AI Lartas (F4). AI hanya merekomendasikan; keputusan manusia.
 */
class LartasAnalysis extends Model
{
    protected $fillable = [
        'shipment_id',
        'hs_code',
        'service_type',
        'commodity',
        'recommendations',
        'summary',
        'model',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'recommendations' => 'array',
        'generated_at'    => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
