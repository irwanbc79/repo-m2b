<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu peristiwa dalam perjalanan sebuah email.
 *
 * Lihat migration untuk alasan tabel ini terpisah dari `email_deliveries`.
 */
class EmailDeliveryEvent extends Model
{
    protected $fillable = [
        'email_delivery_id',
        'provider_event_id',
        'provider_message_guid',
        'event_type',
        'recipient',
        'subject',
        'occurred_at',
        'detail',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(EmailDelivery::class, 'email_delivery_id');
    }

    /**
     * Peristiwa yang belum berhasil ditautkan ke catatan pengiriman.
     */
    public function scopeOrphan($query)
    {
        return $query->whereNull('email_delivery_id');
    }
}
