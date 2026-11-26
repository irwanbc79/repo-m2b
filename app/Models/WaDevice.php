<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaDevice extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'phone_number',
        'phone_number_id',
        'is_primary',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_primary'     => 'boolean',
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class, 'wa_device_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(MoraLeadNotification::class, 'wa_device_id');
    }

    /**
     * Jumlah hari sejak perangkat terakhir aktif tersinkronisasi (untuk pantau aturan 13 hari Coexistence Meta).
     */
    public function daysSinceLastSync(): ?int
    {
        if (!$this->last_synced_at) {
            return null;
        }

        return (int) $this->last_synced_at->diffInDays(now());
    }

    /**
     * Status kesehatan koneksi Coexistence: 'never' | 'healthy' (<=9 hari) | 'warning' (10-12 hari) | 'critical' (>=13 hari).
     */
    public function coexistenceHealth(): string
    {
        $days = $this->daysSinceLastSync();
        if ($days === null) {
            return 'never';
        }
        if ($days >= 13) {
            return 'critical';
        }
        if ($days >= 10) {
            return 'warning';
        }

        return 'healthy';
    }
}
