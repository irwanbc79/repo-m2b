<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class VendorBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'vendor_id',
        'bill_number',
        'bill_date',
        'due_date',
        'payment_terms',
        'custom_days',
        'amount',
        'currency',
        'exchange_rate',
        'amount_idr',
        'paid_amount',
        'status',
        'paid_date',
        'cost_category',
        'description',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_idr' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /* ================= RELATIONS ================= */

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class, 'vendor_bill_id');
    }

    /* ================= ACCESSORS ================= */
    
    /**
     * Get remaining amount to pay
     */
    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }
    
    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        if ($this->currency === 'IDR') {
            return 'Rp ' . number_format($this->amount, 0, ',', '.');
        }
        return $this->currency . ' ' . number_format($this->amount, 2, '.', ',');
    }
    
    /**
     * Get formatted remaining amount
     */
    public function getFormattedRemainingAttribute()
    {
        if ($this->currency === 'IDR') {
            return 'Rp ' . number_format($this->remaining_amount, 0, ',', '.');
        }
        return $this->currency . ' ' . number_format($this->remaining_amount, 2, '.', ',');
    }
    
    /**
     * Check if overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->status !== 'paid' && $this->due_date < now();
    }
    
    /**
     * Get days until/past due
     */
    public function getDaysUntilDueAttribute()
    {
        return now()->diffInDays($this->due_date, false);
    }
    
    /**
     * Get payment terms label
     */
    public function getPaymentTermsLabelAttribute()
    {
        return match($this->payment_terms) {
            'cod' => 'COD (Cash on Delivery)',
            '7_days' => '7 Hari',
            '14_days' => '14 Hari',
            '30_days' => '30 Hari',
            'custom' => $this->custom_days . ' Hari',
            default => '-'
        };
    }
    
    /**
     * Get cost category label
     */
    public function getCostCategoryLabelAttribute()
    {
        return match($this->cost_category) {
            'freight' => 'Freight',
            'trucking' => 'Trucking',
            'customs' => 'Customs Clearance',
            'documentation' => 'Documentation',
            'handling' => 'Handling',
            'insurance' => 'Insurance',
            'other' => 'Lainnya',
            default => '-'
        };
    }
    
    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'unpaid' => 'red',
            'partial' => 'yellow',
            'paid' => 'green',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }
    
    /**
     * Get attachment URL
     */
    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment_path) {
            return Storage::url($this->attachment_path);
        }
        return null;
    }

    /* ================= SCOPES ================= */

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
                    ->where('due_date', '<', now());
    }

    public function scopeForShipment($query, $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }
    
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /* ================= METHODS ================= */
    
    /**
     * Record payment
     */
    public function recordPayment($amount, $paymentDate = null)
    {
        $this->paid_amount += $amount;
        
        // Update status
        if ($this->paid_amount >= $this->amount) {
            $this->status = 'paid';
            $this->paid_date = $paymentDate ?? now();
        } else if ($this->paid_amount > 0) {
            $this->status = 'partial';
        }
        
        $this->save();
    }
}
