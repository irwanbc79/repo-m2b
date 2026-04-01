<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientVisit extends Model
{
    protected $fillable = [
        'user_id', 'client_name', 'latitude', 'longitude',
        'photo_path', 'notes', 'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'   => 'float',
            'longitude'  => 'float',
            'visited_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
