<?php

/**
 * ================================================================================
 * TAMBAHAN UNTUK MODEL InvoicePayment
 * ================================================================================
 * 
 * Tambahkan method ini ke file app/Models/InvoicePayment.php
 * Method ini diperlukan untuk relasi dengan BankTransaction
 * 
 * Lokasi file: app/Models/InvoicePayment.php
 */

// ================================================================================
// COPY METHOD DI BAWAH INI KE MODEL InvoicePayment
// ================================================================================

/**
 * Relasi ke BankTransaction
 * Sebuah payment bisa memiliki satu bank transaction yang di-match
 */
public function bankTransaction()
{
    return $this->hasOne(\App\Models\BankTransaction::class, 'invoice_payment_id');
}

/**
 * Check apakah payment sudah di-reconcile dengan bank transaction
 */
public function isReconciled(): bool
{
    return $this->bankTransaction()->exists();
}

// ================================================================================
// CONTOH LENGKAP MODEL InvoicePayment DENGAN ADDITION
// ================================================================================

/*
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        // ... kolom lainnya
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relasi ke Invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ========================================
    // TAMBAHAN UNTUK BANK RECONCILIATION
    // ========================================
    
    /**
     * Relasi ke BankTransaction
     * Sebuah payment bisa memiliki satu bank transaction yang di-match
     */
    public function bankTransaction()
    {
        return $this->hasOne(BankTransaction::class, 'invoice_payment_id');
    }

    /**
     * Check apakah payment sudah di-reconcile dengan bank transaction
     */
    public function isReconciled(): bool
    {
        return $this->bankTransaction()->exists();
    }
}
*/
