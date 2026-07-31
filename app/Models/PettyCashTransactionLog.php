<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris jejak perubahan transaksi kas kecil.
 *
 * Nama pengubah ikut disalin (bukan hanya id) supaya jejaknya tetap terbaca
 * walau akun stafnya dinonaktifkan atau dihapus di kemudian hari.
 */
class PettyCashTransactionLog extends Model
{
    public const ACTION_UPDATED   = 'diubah';
    public const ACTION_CANCELLED = 'dibatalkan';

    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PettyCashTransaction::class, 'petty_cash_transaction_id');
    }

    /**
     * Label field dalam bahasa yang dipakai staf, bukan nama kolom.
     */
    public static function labelField(string $field): string
    {
        return [
            'amount'           => 'Jumlah',
            'category'         => 'Kategori',
            'description'      => 'Keterangan',
            'transaction_date' => 'Tanggal',
            'shipment_id'      => 'Job',
            'proof_file'       => 'Bukti',
        ][$field] ?? $field;
    }
}
