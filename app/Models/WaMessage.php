<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaMessage extends Model
{
    const DIRECTION_INBOUND = 'inbound';
    const DIRECTION_OUTBOUND = 'outbound';

    const ORIGIN_CLOUD_API = 'meta_cloud_api';
    const ORIGIN_COEXISTENCE_ECHO = 'wa_business_app_echo';
    const ORIGIN_PORTAL_MANUAL = 'portal_manual';

    protected $fillable = [
        'mora_lead_id',
        'wa_device_id',
        'wa_message_id',
        'wa_phone',
        'sender_name',
        'direction',
        'origin',
        'message_type',
        'body',
        'media_url',
        'media_mime',
        'status',
        'error_message',
        'sent_by',
        'wa_timestamp',
        'raw_payload',
    ];

    protected $casts = [
        'wa_timestamp' => 'datetime',
        'raw_payload'  => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(MoraLeadNotification::class, 'mora_lead_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(WaDevice::class, 'wa_device_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isInbound(): bool
    {
        return $this->direction === self::DIRECTION_INBOUND;
    }

    public function isFromBusinessAppEcho(): bool
    {
        return $this->origin === self::ORIGIN_COEXISTENCE_ECHO;
    }
}
