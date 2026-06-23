<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'bank_name',
        'account_number',
        'account_holder',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];
}
