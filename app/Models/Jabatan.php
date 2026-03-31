<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = [
        'nama_jabatan',
        'gaji_pokok_default',
        'tunjangan_jabatan_default',
    ];

    protected $casts = [
        'gaji_pokok_default'       => 'decimal:2',
        'tunjangan_jabatan_default' => 'decimal:2',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
