<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxNote extends Model
{
    protected $fillable = ['user_id', 'periode', 'catatan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
