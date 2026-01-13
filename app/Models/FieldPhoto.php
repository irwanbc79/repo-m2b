<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FieldPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'user_id',
        'original_filename',
        'file_path',
        'thumbnail_path',
        'file_size',
        'mime_type',
        'width',
        'height',
        'description',
        'upload_ip',
        'latitude',
        'longitude',
        'location_address',
        'location_accuracy',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileUrlAttribute()
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path 
            ? Storage::disk('public')->url($this->thumbnail_path)
            : $this->file_url;
    }

    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getLocationMapUrlAttribute()
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }
        
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    public function hasLocation()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function scopeForShipment($query, $shipmentId)
    {
        return $query->where('shipment_id', $shipmentId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude');
    }
}
