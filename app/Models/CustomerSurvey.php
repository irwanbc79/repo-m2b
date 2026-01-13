<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_year',
        'survey_quarter',
        'response_date',
        'is_anonymous',
        'company_name',
        'respondent_position',
        'respondent_position_other',
        'customer_id',
        'services_used',
        
        // Satisfaction scores
        'overall_satisfaction',
        'service_fit_needs',
        
        // Operational quality
        'timely_delivery',
        'shipment_info_clarity',
        'document_accuracy',
        'problem_handling',
        'coordination_quality',
        
        // Communication
        'responsiveness',
        'explanation_clarity',
        'staff_professionalism',
        'contact_ease',
        
        // Pricing
        'price_fairness',
        'cost_transparency',
        'invoice_accuracy',
        
        // Portal/Digital
        'portal_used',
        'portal_ease_of_use',
        'portal_info_clarity',
        'portal_usefulness',
        
        // Loyalty & NPS
        'likelihood_reuse',
        'nps_score',
        
        // Open feedback
        'appreciate_most',
        'needs_improvement',
        'future_features',
        
        // Follow-up
        'willing_to_contact',
        'contact_email',
        'contact_phone',
        
        // Metadata
        'ip_address',
        'user_agent',
        'session_id',
        'completion_time',
        
        // Admin
        'is_complete',
        'is_flagged',
        'admin_notes',
    ];

    protected $casts = [
        'services_used' => 'array',
        'is_anonymous' => 'boolean',
        'portal_used' => 'boolean',
        'willing_to_contact' => 'boolean',
        'is_complete' => 'boolean',
        'is_flagged' => 'boolean',
        'response_date' => 'datetime',
        'survey_year' => 'integer',
        'completion_time' => 'integer',
    ];

    /**
     * Relationship: Survey belongs to a Customer (optional)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope: Filter by year
     */
    public function scopeYear($query, $year)
    {
        return $query->where('survey_year', $year);
    }

    /**
     * Scope: Filter by quarter
     */
    public function scopeQuarter($query, $quarter)
    {
        return $query->where('survey_quarter', $quarter);
    }

    /**
     * Scope: Only complete surveys
     */
    public function scopeComplete($query)
    {
        return $query->where('is_complete', true);
    }

    /**
     * Scope: Flagged for review
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Scope: Promoters (NPS 9-10)
     */
    public function scopePromoters($query)
    {
        return $query->whereBetween('nps_score', [9, 10]);
    }

    /**
     * Scope: Passives (NPS 7-8)
     */
    public function scopePassives($query)
    {
        return $query->whereBetween('nps_score', [7, 8]);
    }

    /**
     * Scope: Detractors (NPS 0-6)
     */
    public function scopeDetractors($query)
    {
        return $query->whereBetween('nps_score', [0, 6]);
    }

    /**
     * Scope: Recent (last 30 days)
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('response_date', '>=', now()->subDays($days));
    }

    /**
     * Scope: Date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('response_date', [$start, $end]);
    }

    /**
     * Accessor: Get NPS Category
     */
    public function getNpsCategoryAttribute(): ?string
    {
        if (is_null($this->nps_score)) return null;
        
        if ($this->nps_score >= 9) return 'Promoter';
        if ($this->nps_score >= 7) return 'Passive';
        return 'Detractor';
    }

    /**
     * Accessor: Get satisfaction level text
     */
    public function getSatisfactionLevelAttribute(): ?string
    {
        if (is_null($this->overall_satisfaction)) return null;
        
        $levels = [
            1 => 'Sangat Tidak Puas',
            2 => 'Tidak Puas',
            3 => 'Cukup',
            4 => 'Puas',
            5 => 'Sangat Puas'
        ];
        
        return $levels[$this->overall_satisfaction] ?? null;
    }

    /**
     * Accessor: Average operational score
     */
    public function getOperationalAverageAttribute(): ?float
    {
        $scores = [
            $this->timely_delivery,
            $this->shipment_info_clarity,
            $this->document_accuracy,
            $this->problem_handling,
            $this->coordination_quality,
        ];
        
        $scores = array_filter($scores, fn($s) => !is_null($s));
        
        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;
    }

    /**
     * Accessor: Average communication score
     */
    public function getCommunicationAverageAttribute(): ?float
    {
        $scores = [
            $this->responsiveness,
            $this->explanation_clarity,
            $this->staff_professionalism,
            $this->contact_ease,
        ];
        
        $scores = array_filter($scores, fn($s) => !is_null($s));
        
        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;
    }

    /**
     * Accessor: Average pricing score
     */
    public function getPricingAverageAttribute(): ?float
    {
        $scores = [
            $this->price_fairness,
            $this->cost_transparency,
            $this->invoice_accuracy,
        ];
        
        $scores = array_filter($scores, fn($s) => !is_null($s));
        
        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;
    }

    /**
     * Accessor: Average portal score
     */
    public function getPortalAverageAttribute(): ?float
    {
        if (!$this->portal_used) return null;
        
        $scores = [
            $this->portal_ease_of_use,
            $this->portal_info_clarity,
            $this->portal_usefulness,
        ];
        
        $scores = array_filter($scores, fn($s) => !is_null($s));
        
        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;
    }

    /**
     * Get display name (company or "Anonymous")
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->is_anonymous ? 'Responden Anonim' : ($this->company_name ?: 'N/A');
    }

    /**
     * Check if needs follow-up (NPS < 7 or overall satisfaction < 3)
     */
    public function needsFollowUp(): bool
    {
        return ($this->nps_score !== null && $this->nps_score < 7) 
            || ($this->overall_satisfaction !== null && $this->overall_satisfaction < 3);
    }

    /**
     * Auto-flag if critical feedback
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($survey) {
            // Auto-set survey quarter based on response date
            $month = date('n', strtotime($survey->response_date));
            $survey->survey_quarter = 'Q' . ceil($month / 3);
            
            // Auto-flag if needs follow-up
            if (($survey->nps_score !== null && $survey->nps_score < 7) 
                || ($survey->overall_satisfaction !== null && $survey->overall_satisfaction < 3)) {
                $survey->is_flagged = true;
            }
        });
    }
}
