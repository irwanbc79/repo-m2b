<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'location_id', 'job_order_id', 'type',
        'latitude', 'longitude', 'selfie_path', 'verified_at',
        'notes', 'is_offline_sync',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'float',
            'longitude'       => 'float',
            'verified_at'     => 'datetime',
            'is_offline_sync' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(AttendanceLocation::class, 'location_id');
    }
}
