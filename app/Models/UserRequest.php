<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'requested_by',
        'pic_name',
        'pic_email',
        'pic_phone',
        'pic_position',
        'access_level',
        'notes',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="px-2 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">⏳ Pending</span>',
            'approved' => '<span class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800">✅ Approved</span>',
            'rejected' => '<span class="px-2 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">❌ Rejected</span>',
            'cancelled' => '<span class="px-2 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-800">🚫 Cancelled</span>',
            default => '<span class="px-2 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-500">Unknown</span>',
        };
    }

    public function getAccessLevelLabelAttribute()
    {
        return match($this->access_level) {
            'view_only' => '👁️ View Only',
            'full_access' => '🔓 Full Access',
            default => 'Unknown',
        };
    }
}
