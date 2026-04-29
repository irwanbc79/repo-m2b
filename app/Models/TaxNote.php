<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxNote extends Model
{
    protected $fillable = ['user_id', 'invoice_id', 'jenis_pajak', 'nominal', 'is_resolved', 'resolved_at', 'attachments', 'periode', 'catatan'];

    protected $casts = [
        'nominal'     => 'decimal:2',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'attachments' => 'array',
    ];

    public const JENIS_PAJAK = [
        'PPh 21'    => 'PPh 21',
        'PPh 23'    => 'PPh 23',
        'PPh 25'    => 'PPh 25',
        'PPh 4(2)'  => 'PPh 4(2)',
        'PPN'       => 'PPN',
        'PPh Badan' => 'PPh Badan',
        'Lainnya'   => 'Lainnya',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }
}
