<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Peristiwa pengiriman mentah dari Kirim Email (webhook).
 *
 * Lihat migration untuk alasan kenapa payload disimpan utuh.
 */
class EmailWebhookEvent extends Model
{
    protected $fillable = [
        'event_type',
        'message_guid',
        'recipient',
        'subject',
        'payload',
        'received_at',
        'processed_at',
        'process_note',
    ];

    protected $casts = [
        'payload'      => 'array',
        'received_at'  => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }
}
