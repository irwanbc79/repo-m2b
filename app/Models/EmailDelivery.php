<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu baris = satu email keluar dari portal, beserta nasibnya.
 *
 * Lihat migration untuk alasan keberadaan tabel ini.
 */
class EmailDelivery extends Model
{
    public const STATUS_QUEUED    = 'queued';
    public const STATUS_SENT      = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_OPENED    = 'opened';
    public const STATUS_CLICKED   = 'clicked';
    public const STATUS_DEFERRED  = 'deferred';
    public const STATUS_BOUNCED   = 'bounced';
    public const STATUS_FAILED    = 'failed';

    /**
     * Urutan kemajuan status. Status TIDAK PERNAH mundur: peristiwa
     * `delivered` yang telat datang tidak boleh menurunkan email yang
     * sudah `opened`. Pola yang sama dipakai Shipment::statusOrder().
     *
     * Status gagal diberi angka tinggi karena begitu email mental atau
     * gagal, itulah kabar terakhir yang relevan bagi staf.
     */
    private const ORDER = [
        self::STATUS_QUEUED    => 1,
        self::STATUS_SENT      => 2,
        self::STATUS_DEFERRED  => 3,
        self::STATUS_DELIVERED => 4,
        self::STATUS_OPENED    => 5,
        self::STATUS_CLICKED   => 6,
        self::STATUS_BOUNCED   => 90,
        self::STATUS_FAILED    => 91,
    ];

    protected $fillable = [
        'recipient_email',
        'subject',
        'sent_at',
        'provider_message_guid',
        'related_type',
        'related_id',
        'mailable_class',
        'mailer',
        'status',
        'delivered_at',
        'first_opened_at',
        'last_opened_at',
        'open_count',
        'click_count',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'sent_at'         => 'datetime',
        'delivered_at'    => 'datetime',
        'first_opened_at' => 'datetime',
        'last_opened_at'  => 'datetime',
        'failed_at'       => 'datetime',
        'open_count'      => 'integer',
        'click_count'     => 'integer',
    ];

    /**
     * Invoice, Quotation, Shipment, atau Customer — bila berhasil dikenali.
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function statusOrder(?string $status = null): int
    {
        return self::ORDER[$status ?? $this->status] ?? 0;
    }

    /**
     * Benar bila $status lebih maju daripada status sekarang.
     */
    public function canAdvanceTo(string $status): bool
    {
        return $this->statusOrder($status) > $this->statusOrder();
    }

    public function scopeUntracked($query)
    {
        return $query->whereNull('provider_message_guid');
    }

    /**
     * Email yang mangkrak di `queued` melewati batas wajar — pertanda
     * pengiriman gagal tanpa meninggalkan pesan error.
     */
    public function scopeStuck($query, int $minutes = 60)
    {
        return $query->where('status', self::STATUS_QUEUED)
            ->where('sent_at', '<', now()->subMinutes($minutes));
    }
}
