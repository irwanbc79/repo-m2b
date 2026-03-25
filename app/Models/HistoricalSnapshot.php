<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricalSnapshot extends Model
{
    protected $fillable = [
        'period',
        'period_type',
        'revenue',
        'cogs',
        'gross_profit',
        'net_profit',
        'source',
    ];

    protected $casts = [
        'revenue'      => 'float',
        'cogs'         => 'float',
        'gross_profit' => 'float',
        'net_profit'   => 'float',
    ];
}
