<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLocation extends Model
{
    protected $fillable = ['name', 'type', 'latitude', 'longitude', 'radius_meters', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude'      => 'float',
            'longitude'     => 'float',
            'radius_meters' => 'integer',
            'is_active'     => 'boolean',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
