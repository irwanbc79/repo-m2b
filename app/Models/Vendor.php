<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'address',
        'bank_details',
        'npwp',
        'website',
        'avg_rating',
        'total_ratings',
        'vendor_score',
        'vendor_grade',
        'last_evaluated_at',
    ];

    protected $casts = [
        'avg_rating' => 'decimal:2',
        'vendor_score' => 'decimal:2',
        'last_evaluated_at' => 'date',
    ];

    /**
     * Relasi: Satu vendor bisa punya banyak Job Costs.
     */
    public function jobCosts()
    {
        return $this->hasMany(JobCost::class);
    }

    /**
     * Relasi: Satu vendor bisa punya banyak Kontak PIC.
     */
    public function contacts()
    {
        return $this->hasMany(VendorContact::class);
    }

    /**
     * Relasi: Satu vendor bisa punya banyak Rating.
     */
    public function ratings()
    {
        return $this->hasMany(VendorRating::class);
    }

    /**
     * Hitung dan update score vendor
     */
    public function calculateScore(): void
    {
        // Rating Score (dari tabel vendor_ratings)
        $avgRating = $this->ratings()->avg('rating') ?? 0;
        $totalRatings = $this->ratings()->count();

        // Job Performance (dari job_costs)
        $totalJobs = $this->jobCosts()->count();
        $paidJobs = $this->jobCosts()->where('status', 'paid')->count();
        
        // Payment rate score (0-100)
        $paymentScore = $totalJobs > 0 ? ($paidJobs / $totalJobs) * 100 : 70;

        // Volume score - lebih banyak job = lebih terpercaya
        // 1 job = 40, 2-3 jobs = 60, 4-5 jobs = 75, 6+ jobs = 90, 10+ jobs = 100
        $volumeScore = match(true) {
            $totalJobs >= 10 => 100,
            $totalJobs >= 6 => 90,
            $totalJobs >= 4 => 75,
            $totalJobs >= 2 => 60,
            $totalJobs >= 1 => 40,
            default => 0,
        };

        // Rating score - jika belum ada rating, gunakan default 70 (netral)
        $ratingScore = $totalRatings > 0 ? ($avgRating / 5) * 100 : 70;

        // Calculate final score (weighted)
        // Jika ada rating: Rating 35%, Payment 35%, Volume 30%
        // Jika belum ada rating: Payment 50%, Volume 50%
        if ($totalRatings > 0) {
            $finalScore = ($ratingScore * 0.35) + ($paymentScore * 0.35) + ($volumeScore * 0.30);
        } else {
            $finalScore = ($paymentScore * 0.50) + ($volumeScore * 0.50);
        }

        // Determine grade
        $grade = match(true) {
            $finalScore >= 85 => 'A+',
            $finalScore >= 75 => 'A',
            $finalScore >= 65 => 'B',
            $finalScore >= 50 => 'C',
            default => 'D',
        };

        // Update vendor
        $this->update([
            'avg_rating' => round($avgRating, 2),
            'total_ratings' => $totalRatings,
            'vendor_score' => round($finalScore, 2),
            'vendor_grade' => $grade,
            'last_evaluated_at' => now(),
        ]);
    }

    /**
     * Get grade badge color
     */
    public function getGradeBadgeAttribute(): array
    {
        return match($this->vendor_grade) {
            'A+' => ['color' => 'yellow', 'icon' => '🥇', 'label' => 'Preferred'],
            'A' => ['color' => 'green', 'icon' => '🥈', 'label' => 'Trusted'],
            'B' => ['color' => 'blue', 'icon' => '🥉', 'label' => 'Standard'],
            'C' => ['color' => 'orange', 'icon' => '⚠️', 'label' => 'Monitor'],
            default => ['color' => 'red', 'icon' => '🚫', 'label' => 'Review'],
        };
    }
}
