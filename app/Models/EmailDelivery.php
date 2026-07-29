<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** Tidak jadi dikirim karena alamatnya sedang diblokir. */
    public const STATUS_SUPPRESSED = 'suppressed';

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
        // Setinggi status gagal: begitu tidak jadi dikirim, itulah kabar
        // terakhir yang relevan bagi staf.
        self::STATUS_SUPPRESSED => 92,
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

    /**
     * Riwayat peristiwa: sampai, dibuka, diklik, mental.
     */
    public function events(): HasMany
    {
        return $this->hasMany(EmailDeliveryEvent::class);
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
     * Keterangan status dalam bahasa yang dipakai staf, bukan istilah teknis
     * provider. Jumlah buka disertakan karena itu yang paling menentukan
     * tindakan berikutnya.
     */
    public function statusLabel(): string
    {
        if ($this->open_count > 1) {
            return "dibuka {$this->open_count}×";
        }

        return match ($this->status) {
            self::STATUS_QUEUED    => 'menunggu konfirmasi',
            self::STATUS_SENT      => 'dikirim',
            self::STATUS_DELIVERED => 'sampai',
            self::STATUS_OPENED    => 'dibuka',
            self::STATUS_CLICKED   => 'dibuka · tautan diklik',
            self::STATUS_DEFERRED  => 'tertunda',
            self::STATUS_BOUNCED   => 'mental',
            self::STATUS_FAILED    => 'gagal',
            self::STATUS_SUPPRESSED => 'tidak dikirim · alamat diblokir',
            default                => $this->status,
        };
    }

    /**
     * Nada warna untuk lencana status: ok | info | warn | crit | mute.
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_BOUNCED, self::STATUS_FAILED, self::STATUS_SUPPRESSED => 'crit',
            self::STATUS_OPENED, self::STATUS_CLICKED    => 'info',
            self::STATUS_DELIVERED                       => 'ok',
            self::STATUS_DEFERRED                        => 'warn',
            default                                      => 'mute',
        };
    }

    /**
     * Jenis email menurut entitas yang ditautkan. Email tanpa tautan berarti
     * email sistem (briefing harian, peringatan pembukuan).
     */
    public function jenisLabel(): string
    {
        return match ($this->related_type) {
            Invoice::class   => 'Invoice',
            Quotation::class => 'Quotation',
            Shipment::class  => 'Shipment',
            Customer::class  => 'Customer',
            default          => 'Sistem',
        };
    }

    /**
     * Nomor dokumen terkait, bila ada — jauh lebih berguna bagi staf
     * daripada nomor baris internal.
     */
    public function relatedLabel(): ?string
    {
        $related = $this->related;

        if (! $related) {
            return null;
        }

        foreach (['invoice_number', 'quotation_number', 'awb_number', 'company_name'] as $field) {
            if (! empty($related->{$field})) {
                return (string) $related->{$field};
            }
        }

        return null;
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
