<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'shipment_id',
        'invoice_number',
        'related_invoice_id',
        'type',
        'dp_percentage',
        'invoice_date', 
        'due_date',
        'status',
        'payment_proof',
        'payment_date', 
        
        // Keuangan
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'service_total',
        'reimbursement_total',
        'tax_amount',
        'pph_amount',
        'down_payment',
        'grand_total',
        'total_paid',
        'notes',
        'terbilang_lang',
        'last_reminded_at',
        'reminder_count',
        
        // Signature & Materai
        'signature_type',
        'signer_name',
        'signer_title',
        'signer_sign_path',
        'use_materai',
        'materai_type',
        
        // Payment Claim fields
        'payment_claimed',
        'claim_proof_path',
        'claimed_at',
        'claim_notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'last_reminded_at' => 'datetime',
        'use_materai' => 'boolean',
        'total_paid' => 'decimal:2',
        'payment_claimed' => 'boolean',
        'claimed_at' => 'datetime',
    ];

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'paid' && 
               $this->status !== 'cancelled' && 
               $this->due_date && 
               $this->due_date->endOfDay()->isPast();
    }

    // Hitung sisa tagihan
    public function getRemainingBalanceAttribute()
    {
        return $this->grand_total - ($this->total_paid ?? 0);
    }

    // Cek apakah sudah lunas
    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_balance <= 0;
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    
    // Multiple Payments
    public function payments() 
    { 
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date', 'desc'); 
    }

    public function relatedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    public function derivedInvoices()
    {
        return $this->hasMany(Invoice::class, 'related_invoice_id');
    }

    public function isProforma(): bool
    {
        return strtolower($this->type) === 'proforma';
    }

    public function isCommercial(): bool
    {
        return strtolower($this->type) === 'commercial';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function canGenerateCommercial(): bool
    {
        return $this->isProforma() 
            && $this->isPaid() 
            && !$this->hasCommercialInvoice();
    }

    public function hasCommercialInvoice(): bool
    {
        return $this->derivedInvoices()
                    ->whereRaw('LOWER(type) = ?', ['commercial'])
                    ->exists();
    }

    // Recalculate total paid from payments table
    public function recalculateTotalPaid()
    {
        $this->total_paid = $this->payments()->sum('amount');
        
        // Update status berdasarkan total pembayaran
        if ($this->total_paid <= 0) {
            $this->status = 'unpaid';
            $this->payment_date = null;
        } elseif ($this->total_paid >= $this->grand_total) {
            $this->status = 'paid';
            $this->payment_date = $this->payment_date ?? now();
        } else {
            // Pembayaran cicilan/sebagian
            $this->status = 'partial';
            $lastPayment = $this->payments()->orderBy('payment_date', 'desc')->first();
            $this->payment_date = $lastPayment ? $lastPayment->payment_date : null;
        }
        
        $this->save();
    }
}
