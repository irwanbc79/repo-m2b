<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'invoice_id', 'display_name', 'company_name',
        'position', 'rating', 'content', 'status', 'approved_by',
        'approved_at', 'admin_note', 'token', 'google_review_sent',
        'google_review_sent_at', 'ip_address',
    ];

    protected $casts = [
        'approved_at'           => 'datetime',
        'google_review_sent_at' => 'datetime',
        'google_review_sent'    => 'boolean',
        'rating'                => 'integer',
    ];

    public function customer()   { return $this->belongsTo(Customer::class); }
    public function invoice()    { return $this->belongsTo(Invoice::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopePending($q)  { return $q->where('status', 'pending'); }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
