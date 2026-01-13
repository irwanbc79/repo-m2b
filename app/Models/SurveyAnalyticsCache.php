<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SurveyAnalyticsCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'cache_key',
        'cache_data',
        'valid_until',
    ];

    protected $casts = [
        'cache_data' => 'array',
        'valid_until' => 'datetime',
    ];

    /**
     * Get cached data if still valid
     */
    public static function getCached(string $key): ?array
    {
        $cache = self::where('cache_key', $key)
            ->where('valid_until', '>', now())
            ->first();
        
        return $cache ? $cache->cache_data : null;
    }

    /**
     * Set cache with TTL (default 1 hour)
     */
    public static function setCached(string $key, array $data, int $minutes = 60): void
    {
        self::updateOrCreate(
            ['cache_key' => $key],
            [
                'cache_data' => $data,
                'valid_until' => now()->addMinutes($minutes),
            ]
        );
    }

    /**
     * Invalidate specific cache
     */
    public static function invalidate(string $key): void
    {
        self::where('cache_key', $key)->delete();
    }

    /**
     * Clear all expired cache
     */
    public static function clearExpired(): void
    {
        self::where('valid_until', '<', now())->delete();
    }
}
