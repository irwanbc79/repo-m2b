<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSlip extends Model
{
    protected $fillable = [
        'employee_id',
        'period_id',
        'gaji_pokok',
        'tunjangan_transport',
        'tunjangan_jabatan',
        'tunjangan_lainnya',
        'lembur_jam',
        'lembur_nominal',
        'potongan_bpjs_kes',
        'potongan_bpjs_tk',
        'potongan_lainnya',
        'total_gaji',
        'catatan',
    ];

    protected $casts = [
        'gaji_pokok'          => 'decimal:2',
        'tunjangan_transport' => 'decimal:2',
        'tunjangan_jabatan'   => 'decimal:2',
        'tunjangan_lainnya'   => 'decimal:2',
        'lembur_jam'          => 'decimal:2',
        'lembur_nominal'      => 'decimal:2',
        'potongan_bpjs_kes'   => 'decimal:2',
        'potongan_bpjs_tk'    => 'decimal:2',
        'potongan_lainnya'    => 'decimal:2',
        'total_gaji'          => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    /**
     * Auto-calculate total_gaji from all components.
     */
    public function getTotalGajiAttribute(): float
    {
        return (float) $this->gaji_pokok
            + (float) $this->tunjangan_transport
            + (float) $this->tunjangan_jabatan
            + (float) $this->tunjangan_lainnya
            + (float) $this->lembur_nominal
            - (float) $this->potongan_bpjs_kes
            - (float) $this->potongan_bpjs_tk
            - (float) $this->potongan_lainnya;
    }

    /**
     * Recalculate and persist total_gaji before saving.
     */
    protected static function booted(): void
    {
        static::saving(function (PayrollSlip $slip) {
            $slip->total_gaji = (float) $slip->gaji_pokok
                + (float) $slip->tunjangan_transport
                + (float) $slip->tunjangan_jabatan
                + (float) $slip->tunjangan_lainnya
                + (float) $slip->lembur_nominal
                - (float) $slip->potongan_bpjs_kes
                - (float) $slip->potongan_bpjs_tk
                - (float) $slip->potongan_lainnya;
        });
    }
}
