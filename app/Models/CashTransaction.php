<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        // Existing fields (TIDAK DIHAPUS)
        'transaction_date',
        'type',
        'amount',
        'account_id',
        'counter_account_id',
        'invoice_id',
        'shipment_id',
        'vendor_id',
        'description',
        'proof_file',
        'journal_id',
        'created_by',
        
        // New fields untuk Simple Cashier
        'customer_id',
        'transaction_type',
        'cost_category',
        'counterpart_name',
        'counterpart_type',
        'currency',
        'exchange_rate',
        'amount_idr',
        'attachment_path',
        'attachment_filename',
        'vendor_bill_id',
        'is_posted',
        'posted_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_idr' => 'decimal:2',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    /* ================= RELATIONS ================= */

    public function cashAccount()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function counterAccount()
    {
        return $this->belongsTo(Account::class, 'counter_account_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function vendorBill()
    {
        return $this->belongsTo(VendorBill::class, 'vendor_bill_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /* ================= ACCESSORS ================= */
    
    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        if ($this->currency === 'IDR') {
            return 'Rp ' . number_format($this->amount, 0, ',', '.');
        }
        return $this->currency . ' ' . number_format($this->amount, 2, '.', ',');
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
    
    /**
     * Get transaction type label
     */
    public function getTransactionTypeLabelAttribute()
    {
        return match($this->transaction_type) {
            'cash_in' => 'Terima Uang',
            'cash_out' => 'Keluar Uang',
            default => '-'
        };
    }
    
    /**
     * Get cost category label
     */
    public function getCostCategoryLabelAttribute()
    {
        return match($this->cost_category) {
            'shipment' => 'Biaya Shipment',
            'overhead' => 'Biaya Overhead',
            'other' => 'Lainnya',
            default => '-'
        };
    }
    
    /* ================= SCOPES ================= */
    
    public function scopeCashIn($query)
    {
        return $query->where('transaction_type', 'cash_in');
    }
    
    public function scopeCashOut($query)
    {
        return $query->where('transaction_type', 'cash_out');
    }
    
    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }
    
    public function scopeUnposted($query)
    {
        return $query->where('is_posted', false);
    }
    
    public function scopeForShipment($query, $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }
    
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
    }
}
