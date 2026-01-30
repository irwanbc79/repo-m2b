<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashTransaction extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    const CATEGORIES = [
        'parkir' => ['label' => 'Parkir', 'coa' => '6204'],
        'tol' => ['label' => 'Tol', 'coa' => '6204'],
        'bensin' => ['label' => 'Bensin/BBM', 'coa' => '6201'],
        'transport' => ['label' => 'Transport Lain', 'coa' => '6201'],
        'konsumsi' => ['label' => 'Konsumsi/Makan', 'coa' => '6202'],
        'materai' => ['label' => 'Materai', 'coa' => '6203'],
        'atk' => ['label' => 'ATK', 'coa' => '6104'],
        'fotokopi' => ['label' => 'Fotokopi/Print', 'coa' => '6104'],
        'lainnya' => ['label' => 'Lainnya', 'coa' => '6209'],
    ];

    public function fund() { return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id'); }
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function journal() { return $this->belongsTo(Journal::class); }
    
    public function getCategoryLabelAttribute(): string { 
        return self::CATEGORIES[$this->category]['label'] ?? $this->category; 
    }
    public function getCategoryCoaAttribute(): string { 
        return self::CATEGORIES[$this->category]['coa'] ?? '6209'; 
    }
    
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeThisMonth($query) { 
        return $query->whereMonth('transaction_date', now()->month)
                     ->whereYear('transaction_date', now()->year); 
    }

    public static function generateNumber(): string {
        $prefix = 'PCE-' . now()->format('Ym') . '-';
        $last = self::where('transaction_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->transaction_number, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
