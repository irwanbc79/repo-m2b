<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashFund extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'plafon' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'min_balance_alert' => 'decimal:2',
        'max_transaction' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function holder() { return $this->belongsTo(User::class, 'holder_user_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approver_user_id'); }
    public function transactions() { return $this->hasMany(PettyCashTransaction::class); }
    public function topups() { return $this->hasMany(PettyCashTopup::class); }
    public function settingLogs() { return $this->hasMany(PettyCashSettingLog::class); }
    
    public function needsTopup(): bool { return $this->current_balance <= $this->min_balance_alert; }
    public function getUsagePercentageAttribute(): float {
        return $this->plafon > 0 ? round((($this->plafon - $this->current_balance) / $this->plafon) * 100, 1) : 0;
    }
    public function canSpend(float $amount): bool {
        return $this->current_balance >= $amount && $amount <= $this->max_transaction;
    }
    public function getMaxTopupAmountAttribute(): float { return $this->plafon - $this->current_balance; }
    public function scopeActive($query) { return $query->where('is_active', true); }
}
