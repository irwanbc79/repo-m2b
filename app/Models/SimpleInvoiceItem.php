<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimpleInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'simple_invoice_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Boot method - Auto calculate amount
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->amount = $item->quantity * $item->unit_price;
        });
    }

    /**
     * Relationships
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SimpleInvoice::class, 'simple_invoice_id');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $currency = $this->invoice->currency ?? 'IDR';
        
        if ($currency === 'USD') {
            return '$' . number_format($this->amount, 2);
        }
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}
