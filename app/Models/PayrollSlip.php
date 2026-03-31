<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function deductionItems(): HasMany
    {
        return $this->hasMany(PayrollDeductionItem::class);
    }

    /**
     * Base calculation from slip-level fields (without deduction items).
     */
    public function baseTotal(): float
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
     * Recalculate and persist total_gaji including all deduction items.
     * Call this after adding/removing deduction items.
     */
    public function syncTotal(): void
    {
        $itemsTotal     = $this->deductionItems()->sum('nominal');
        $this->total_gaji = $this->baseTotal() - (float) $itemsTotal;
        $this->saveQuietly();
    }

    /**
     * Auto-calculate total_gaji before saving.
     * For existing slips, includes stored deduction items.
     * For new slips (no id yet), items sum is 0.
     */
    protected static function booted(): void
    {
        static::saving(function (PayrollSlip $slip) {
            $itemsTotal = $slip->exists
                ? (float) $slip->deductionItems()->sum('nominal')
                : 0.0;

            $slip->total_gaji = $slip->baseTotal() - $itemsTotal;
        });
    }
}
