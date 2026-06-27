<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentMessage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_type === 'customer';
    }

    /** Pesan dari customer yang belum dibaca admin. */
    public function scopeUnreadForAdmin($query)
    {
        return $query->where('sender_type', 'customer')->whereNull('read_at');
    }

    /** Pesan dari admin yang belum dibaca customer. */
    public function scopeUnreadForCustomer($query)
    {
        return $query->where('sender_type', 'admin')->whereNull('read_at');
    }
}
