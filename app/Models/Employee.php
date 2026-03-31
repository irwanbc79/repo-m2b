<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'nik',
        'nama',
        'jabatan_id',
        'join_date',
        'status',
        'employment_type',
        'no_hp',
        'alamat',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollSlips(): HasMany
    {
        return $this->hasMany(PayrollSlip::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
