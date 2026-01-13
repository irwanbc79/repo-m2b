<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'sub_category',
        'service_type',
        'description',
        'default_price',
        'coa_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the Chart of Account for this product
     */
    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    /**
     * Get invoice items that use this product
     */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'product_id');
    }

    /**
     * Scope: Active products only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by service type
     */
    public function scopeServiceType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->default_price, 0, ',', '.');
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute()
    {
        $labels = [
            'import' => 'Import',
            'export' => 'Export',
            'domestic' => 'Domestic',
            'consultation' => 'Consultation',
            'reimbursement' => 'Reimbursement',
        ];

        return $labels[$this->category] ?? $this->category;
    }

    /**
     * Get service type label
     */
    public function getServiceTypeLabelAttribute()
    {
        return $this->service_type === 'service' ? 'Jasa' : 'Reimbursement';
    }
}
