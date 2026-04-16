<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 
        // DATA MANUAL (V3)
        'manual_company', 
        'manual_pic', 
        'manual_email', 
        'manual_phone',
        
        // DATA UTAMA
        'quotation_number', // <--- Pastikan koma ini ada
        'quotation_date', 
        'valid_until',
        'status', 
        
        // Keuangan
        'subtotal', 
        'service_total',
        'reimbursement_total',
        'tax_amount', 
        'pph_amount',
        'grand_total', 
        
        // Info Rute
        'origin', 
        'destination', 
        'service_type', 
        'quotation_type',
        'notes', 'terbilang_lang',

        // Approval
        'approval_token', 'approval_status',
        'approved_at', 'approved_by',
        'approval_ip', 'approval_user_agent',

        // Signed document
        'signed_document_path', 'signed_document_at',
    ];

    protected $casts = [
        'quotation_date'      => 'date',
        'valid_until'         => 'date',
        'approved_at'         => 'datetime',
        'signed_document_at'  => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }
}