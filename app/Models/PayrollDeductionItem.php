<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeductionItem extends Model
{
    protected $fillable = [
        'payroll_slip_id',
        'nama_potongan',
        'nominal',
        'catatan',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
    ];

    public function payrollSlip(): BelongsTo
    {
        return $this->belongsTo(PayrollSlip::class);
    }
}
