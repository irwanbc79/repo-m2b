<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashTopup extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'amount_requested' => 'decimal:2',
        'amount_approved' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'approved_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    public function fund() { return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function journal() { return $this->belongsTo(Journal::class); }
    
    public function isPending(): bool { return $this->status === 'pending'; }
    public function scopePending($query) { return $query->where('status', 'pending'); }

    public static function generateNumber(): string {
        $prefix = 'PCT-' . now()->format('Ym') . '-';
        $last = self::where('topup_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->topup_number, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
