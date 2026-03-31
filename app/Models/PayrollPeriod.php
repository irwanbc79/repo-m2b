<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'bulan',
        'tahun',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'bulan'       => 'integer',
        'tahun'       => 'integer',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payrollSlips(): HasMany
    {
        return $this->hasMany(PayrollSlip::class, 'period_id');
    }

    public function getNamaBulanAttribute(): string
    {
        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $bulanNames[$this->bulan] ?? '-';
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->nama_bulan . ' ' . $this->tahun;
    }
}
