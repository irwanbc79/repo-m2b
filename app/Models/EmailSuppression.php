<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Alamat yang tidak boleh dikirimi lagi. Lihat migration untuk alasannya.
 */
class EmailSuppression extends Model
{
    public const SOURCE_BOUNCE   = 'bounce';
    public const SOURCE_PROVIDER = 'provider';
    public const SOURCE_MANUAL   = 'manual';

    protected $fillable = [
        'email',
        'source',
        'reason',
        'suppressed_at',
    ];

    protected $casts = [
        'suppressed_at' => 'datetime',
    ];

    /**
     * Benar bila alamat ini sedang diblokir.
     *
     * Dipanggil pada SETIAP pengiriman email, jadi sengaja dijaga sesederhana
     * mungkin: satu lookup lewat indeks unik.
     */
    public static function diblokir(string $email): bool
    {
        return static::where('email', static::normalkan($email))->exists();
    }

    public static function tandai(string $email, ?string $alasan, string $sumber = self::SOURCE_BOUNCE): void
    {
        static::updateOrCreate(
            ['email' => static::normalkan($email)],
            [
                'source'        => $sumber,
                'reason'        => $alasan ? mb_substr($alasan, 0, 500) : null,
                'suppressed_at' => now(),
            ]
        );
    }

    public static function cabut(string $email): void
    {
        static::where('email', static::normalkan($email))->delete();
    }

    public static function normalkan(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
