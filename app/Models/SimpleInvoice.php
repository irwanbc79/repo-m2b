<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimpleInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'invoice_date', 'due_date', 'customer_id', 'customer_name',
        'customer_address', 'currency', 'subtotal', 'total', 'terbilang',
        'notes', 'status', 'paid_date', 'payment_proof', 'payment_notes', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($invoice) {
            $invoice->invoice_number = static::generateInvoiceNumber();
            $invoice->terbilang = static::numberToWords($invoice->total, $invoice->currency ?? 'IDR');
        });
        
        // Tambahan: Update terbilang saat total berubah
        static::updating(function ($invoice) {
            if ($invoice->isDirty('total')) {
                $invoice->terbilang = static::numberToWords($invoice->total, $invoice->currency ?? 'IDR');
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('n');
        $romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        
        // Cari sequence tertinggi dari SEMUA invoice di tahun ini (termasuk soft deleted)
        $lastInvoice = static::withTrashed()
            ->whereYear('invoice_date', $year)
            ->where('invoice_number', 'LIKE', 'INV%')
            ->orderByRaw("CAST(SUBSTRING(invoice_number, 4, 3) AS UNSIGNED) DESC")
            ->first();
        
        if ($lastInvoice && preg_match('/^INV(\d{3})-/', $lastInvoice->invoice_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }
        
        // Generate nomor dengan retry mechanism untuk menghindari race condition
        $maxRetries = 10;
        for ($i = 0; $i < $maxRetries; $i++) {
            $invoiceNumber = sprintf('INV%03d-M2B/%s/%s', $sequence + $i, $romanMonths[$month], $year);
            
            // Cek apakah nomor sudah ada (termasuk yang soft deleted)
            $exists = static::withTrashed()
                ->where('invoice_number', $invoiceNumber)
                ->exists();
            
            if (!$exists) {
                return $invoiceNumber;
            }
        }
        
        // Fallback: gunakan timestamp jika semua retry gagal
        return sprintf('INV%03d-M2B/%s/%s-%d', $sequence, $romanMonths[$month], $year, time());
    }


    public static function numberToWords(?float $number, string $currency = 'IDR'): string
    {
        // Handle null atau 0
        if ($number === null || $number == 0) {
            return ($currency === 'IDR') ? 'Nol Rupiah' : 'Nol';
        }
        
        $number = floor($number);
        
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        
        if ($number < 12) {
            $result = $words[$number];
        } elseif ($number < 20) {
            $result = $words[$number - 10] . ' Belas';
        } elseif ($number < 100) {
            $result = $words[floor($number / 10)] . ' Puluh';
            if ($number % 10 > 0) $result .= ' ' . $words[$number % 10];
        } elseif ($number < 200) {
            $result = 'Seratus';
            if ($number > 100) $result .= ' ' . static::numberToWords($number - 100, '');
        } elseif ($number < 1000) {
            $result = $words[floor($number / 100)] . ' Ratus';
            if ($number % 100 > 0) $result .= ' ' . static::numberToWords($number % 100, '');
        } elseif ($number < 2000) {
            $result = 'Seribu';
            if ($number > 1000) $result .= ' ' . static::numberToWords($number - 1000, '');
        } elseif ($number < 1000000) {
            $result = static::numberToWords(floor($number / 1000), '') . ' Ribu';
            if ($number % 1000 > 0) $result .= ' ' . static::numberToWords($number % 1000, '');
        } elseif ($number < 1000000000) {
            $result = static::numberToWords(floor($number / 1000000), '') . ' Juta';
            if ($number % 1000000 > 0) $result .= ' ' . static::numberToWords($number % 1000000, '');
        } else {
            $result = static::numberToWords(floor($number / 1000000000), '') . ' Milyar';
            if ($number % 1000000000 > 0) $result .= ' ' . static::numberToWords($number % 1000000000, '');
        }
        
        $result = trim(preg_replace('/\s+/', ' ', $result));
        return ($currency === 'IDR') ? $result . ' Rupiah' : $result;
    }

    public function items() { return $this->hasMany(SimpleInvoiceItem::class)->orderBy('sort_order'); }
    public function customer() { return $this->belongsTo(\App\Models\Customer::class); }
    public function creator() { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    
    public function getFormattedTotalAttribute() { return 'Rp ' . number_format($this->total, 0, ',', '.'); }
    public function getStatusBadgeAttribute() {
        $colors = ['unpaid' => 'bg-red-100 text-red-800', 'paid' => 'bg-green-100 text-green-800'];
        return sprintf('<span class="px-2 py-1 rounded text-xs font-semibold %s">%s</span>', 
            $colors[$this->status] ?? 'bg-gray-100 text-gray-800', strtoupper($this->status));
    }
}
