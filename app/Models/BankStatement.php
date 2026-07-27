<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_number',
        'transaction_date',
        'booking_date',
        'type',
        'amount',
        'balance',
        'description',
        'reference_number',
        'is_reconciled',
        'invoice_id',
        'reconciled_at',
        'raw_payload',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'booking_date'     => 'datetime',
        'reconciled_at'   => 'datetime',
        'is_reconciled'   => 'boolean',
        'amount'          => 'decimal:2',
        'balance'         => 'decimal:2',
        'raw_payload'     => 'array',
    ];

    /**
     * Relasi ke Invoice (jika mutasi terikat ke pembayaran invoice)
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Scope untuk mengambil hanya transaksi Kredit (Uang Masuk)
     */
    public function scopeCredit($query)
    {
        return $query->where('type', 'CR');
    }

    /**
     * Scope untuk transaksi yang belum direkonsiliasi
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }
}
