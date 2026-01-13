<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxExchangeRate extends Model
{
    protected $table = 'tax_exchange_rates';

    protected $fillable = [
        'currency_code',
        'currency_name',
        'rate',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];
}
