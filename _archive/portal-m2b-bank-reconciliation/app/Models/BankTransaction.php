<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $table = 'bank_transactions';

    protected $fillable = [
        'bank_name',
        'account_number',
        'transaction_date',
        'description',
        'additional_description',
        'debit_amount',
        'credit_amount',
        'balance',
        'reference_number',
        'category',
        'is_reconciled',
        'invoice_payment_id',
        'matched_by',
        'matched_at',
        'matching_notes',
        'import_batch',
        'imported_at',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'matched_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    /**
     * Kategori transaksi yang tersedia
     */
    const CATEGORIES = [
        'payment_received' => 'Pembayaran Diterima',
        'payment_sent' => 'Pembayaran Keluar',
        'salary' => 'Gaji Karyawan',
        'operational' => 'Biaya Operasional',
        'trucking' => 'Trucking',
        'loan' => 'Cicilan/Hutang',
        'deposit' => 'Setoran Tunai',
        'bank_fee' => 'Biaya Bank',
        'interest' => 'Bunga',
        'tax' => 'Pajak',
        'other' => 'Lainnya',
    ];

    /**
     * Relasi ke InvoicePayment
     */
    public function invoicePayment()
    {
        return $this->belongsTo(InvoicePayment::class, 'invoice_payment_id');
    }

    /**
     * Relasi ke User yang melakukan matching
     */
    public function matchedByUser()
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    /**
     * Scope untuk transaksi yang belum direkonsiliasi
     */
    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    /**
     * Scope untuk transaksi yang sudah direkonsiliasi
     */
    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    /**
     * Scope untuk filter berdasarkan bank
     */
    public function scopeByBank($query, $bankName)
    {
        return $query->where('bank_name', $bankName);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope untuk transaksi kredit (uang masuk)
     */
    public function scopeCredits($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    /**
     * Scope untuk transaksi debit (uang keluar)
     */
    public function scopeDebits($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    /**
     * Check apakah transaksi adalah kredit
     */
    public function isCredit(): bool
    {
        return $this->credit_amount > 0;
    }

    /**
     * Check apakah transaksi adalah debit
     */
    public function isDebit(): bool
    {
        return $this->debit_amount > 0;
    }

    /**
     * Get jumlah transaksi (positif untuk kredit, negatif untuk debit)
     */
    public function getAmountAttribute(): float
    {
        return $this->credit_amount > 0 ? $this->credit_amount : -$this->debit_amount;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $amount = $this->credit_amount > 0 ? $this->credit_amount : $this->debit_amount;
        $prefix = $this->credit_amount > 0 ? '+' : '-';
        return $prefix . ' Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Lainnya';
    }

    /**
     * Auto-detect category berdasarkan deskripsi
     */
    public static function detectCategory(string $description): string
    {
        $description = strtolower($description);

        // Payment patterns
        if (preg_match('/inv|invoice|pelunasan|tagihan/i', $description)) {
            return 'payment_received';
        }

        // Salary patterns
        if (preg_match('/gaji|thr|bonus/i', $description)) {
            return 'salary';
        }

        // Trucking patterns
        if (preg_match('/trucking|lolo|pass gate|jamcont/i', $description)) {
            return 'trucking';
        }

        // Loan patterns
        if (preg_match('/cicilan|hutang|pinjaman/i', $description)) {
            return 'loan';
        }

        // Deposit patterns
        if (preg_match('/setor|setoran/i', $description)) {
            return 'deposit';
        }

        // Bank fee patterns
        if (preg_match('/biaya adm|biaya admin|transfer fee/i', $description)) {
            return 'bank_fee';
        }

        // Interest patterns
        if (preg_match('/bunga/i', $description)) {
            return 'interest';
        }

        // Tax patterns
        if (preg_match('/pajak|pph/i', $description)) {
            return 'tax';
        }

        // Operational patterns
        if (preg_match('/operasional|ops|atk|dokumen/i', $description)) {
            return 'operational';
        }

        return 'other';
    }

    /**
     * Ekstrak nomor invoice dari deskripsi
     */
    public static function extractInvoiceNumber(string $description): ?string
    {
        // Pattern untuk berbagai format nomor invoice
        $patterns = [
            '/SI\d{11}/i',           // SI20251100009
            '/INV\/\d{4}\/\d+/i',    // INV/2512/5275
            '/INV-\d+-\d+/i',        // INV-2025-001
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }
}
