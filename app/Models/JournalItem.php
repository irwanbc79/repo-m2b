<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke Bapaknya (Journal Header)
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    // Relasi ke Akun (COA)
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}