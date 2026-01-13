<?php

namespace App\Services;

use App\Models\CustomerSurvey;
use App\Models\SurveyAnalyticsCache;
use Illuminate\Support\Facades\DB;

class SurveyAnalyticsService
{
    /**
     * Calculate NPS (Net Promoter Score)
     * NPS = % Promoters - % Detractors
     */
    public function calculateNPS(string $year = null, string $quarter = null): array
    {
        $cacheKey = "nps_{$year}_{$quarter}";
        
        // Try cache first
        if ($cached = SurveyAnalyticsCache::getCached($cacheKey)) {
            return $cached;
        }

        $query = CustomerSurvey::complete()->whereNotNull('nps_score');
        
        if ($year) $query->year($year);
        if ($quarter) $query->quarter($quarter);
        
        $total = $query->count();
        
        if ($total === 0) {
            return [
                'nps_score' => 0,
                'total_responses' => 0,
                'promoters' => 0,
                'passives' => 0,
                'detractors' => 0,
                'promoters_percent' => 0,
                'passives_percent' => 0,
                'detractors_percent' => 0,
            ];
        }
        
        $promoters = $query->clone()->promoters()->count();
        $passives = $query->clone()->passives()->count();
        $detractors = $query->clone()->detractors()->count();
        
        $promotersPercent = round(($promoters / $total) * 100, 2);
        $passivesPercent = round(($passives / $total) * 100, 2);
        $detractorsPercent = round(($detractors / $total) * 100, 2);
        
        $npsScore = round($promotersPercent - $detractorsPercent, 2);
        
        $result = [
            'nps_score' => $npsScore,
            'total_responses' => $total,
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'promoters_percent' => $promotersPercent,
            'passives_percent' => $passivesPercent,
            'detractors_percent' => $detractorsPercent,
        ];
        
        // Cache for 1 hour
        SurveyAnalyticsCache::setCached($cacheKey, $result, 60);
        
        return $result;
    }

    /**
     * Get overall satisfaction statistics
     */
    public function getSatisfactionStats(string $year = null, string $quarter = null): array
    {
        $query = CustomerSurvey::complete()->whereNotNull('overall_satisfaction');
        
        if ($year) $query->year($year);
        if ($quarter) $query->quarter($quarter);
        
        $total = $query->count();
        
        if ($total === 0) {
            return [
                'average' => 0,
                'total_responses' => 0,
                'distribution' => [],
            ];
        }
        
        $average = round($query->avg('overall_satisfaction'), 2);
        
        // Distribution by rating
        $distribution = $query->select('overall_satisfaction', DB::raw('count(*) as count'))
            ->groupBy('overall_satisfaction')
            ->orderBy('overall_satisfaction')
            ->get()
            ->mapWithKeys(function ($item) use ($total) {
                return [
                    $item->overall_satisfaction => [
                        'count' => $item->count,
                        'percent' => round(($item->count / $total) * 100, 2),
                    ]
                ];
            });
        
        return [
            'average' => $average,
            'total_responses' => $total,
            'distribution' => $distribution,
        ];
    }

    /**
     * Get category averages (Operational, Communication, Pricing, Portal)
     */
    public function getCategoryAverages(string $year = null, string $quarter = null): array
    {
        $query = CustomerSurvey::complete();
        
        if ($year) $query->year($year);
        if ($quarter) $query->quarter($quarter);
        
        $surveys = $query->get();
        
        if ($surveys->isEmpty()) {
            return [
                'operational' => 0,
                'communication' => 0,
                'pricing' => 0,
                'portal' => 0,
            ];
        }
        
        return [
            'operational' => round($surveys->avg('operational_average'), 2),
            'communication' => round($surveys->avg('communication_average'), 2),
            'pricing' => round($surveys->avg('pricing_average'), 2),
            'portal' => round($surveys->where('portal_used', true)->avg('portal_average'), 2),
        ];
    }

    /**
     * Get service usage statistics
     */
    public function getServiceUsage(string $year = null, string $quarter = null): array
    {
        $query = CustomerSurvey::complete()->whereNotNull('services_used');
        
        if ($year) $query->year($year);
        if ($quarter) $query->quarter($quarter);
        
        $surveys = $query->get();
        $total = $surveys->count();
        
        if ($total === 0) return [];
        
        $servicesCount = [];
        
        foreach ($surveys as $survey) {
            foreach ($survey->services_used as $service) {
                if (!isset($servicesCount[$service])) {
                    $servicesCount[$service] = 0;
                }
                $servicesCount[$service]++;
            }
        }
        
        $result = [];
        foreach ($servicesCount as $service => $count) {
            $result[$service] = [
                'count' => $count,
                'percent' => round(($count / $total) * 100, 2),
            ];
        }
        
        arsort($result);
        
        return $result;
    }

    /**
     * Get response trend (daily/weekly/monthly)
     */
    public function getResponseTrend(string $period = 'daily', int $days = 30): array
    {
        $query = CustomerSurvey::complete()
            ->where('response_date', '>=', now()->subDays($days));
        
        $format = match($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        
        $trend = $query->select(
                DB::raw("DATE_FORMAT(response_date, '{$format}') as period"),
                DB::raw('count(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->pluck('count', 'period');
        
        return $trend->toArray();
    }

    /**
     * Get top strengths (highest rated areas)
     */
    public function getTopStrengths(int $limit = 5): array
    {
        $fields = [
            'timely_delivery' => 'Ketepatan Waktu Pengiriman',
            'shipment_info_clarity' => 'Kejelasan Info Shipment',
            'document_accuracy' => 'Akurasi Dokumen',
            'problem_handling' => 'Penanganan Masalah',
            'coordination_quality' => 'Kualitas Koordinasi',
            'responsiveness' => 'Responsivitas Tim',
            'explanation_clarity' => 'Kejelasan Penjelasan',
            'staff_professionalism' => 'Profesionalisme Staff',
            'contact_ease' => 'Kemudahan Kontak',
            'price_fairness' => 'Kewajaran Harga',
            'cost_transparency' => 'Transparansi Biaya',
            'invoice_accuracy' => 'Akurasi Invoice',
        ];
        
        $averages = [];
        
        foreach ($fields as $field => $label) {
            $avg = CustomerSurvey::complete()
                ->whereNotNull($field)
                ->avg($field);
            
            if ($avg) {
                $averages[$label] = round($avg, 2);
            }
        }
        
        arsort($averages);
        
        return array_slice($averages, 0, $limit, true);
    }

    /**
     * Get areas for improvement (lowest rated areas)
     */
    public function getAreasForImprovement(int $limit = 5): array
    {
        $fields = [
            'timely_delivery' => 'Ketepatan Waktu Pengiriman',
            'shipment_info_clarity' => 'Kejelasan Info Shipment',
            'document_accuracy' => 'Akurasi Dokumen',
            'problem_handling' => 'Penanganan Masalah',
            'coordination_quality' => 'Kualitas Koordinasi',
            'responsiveness' => 'Responsivitas Tim',
            'explanation_clarity' => 'Kejelasan Penjelasan',
            'staff_professionalism' => 'Profesionalisme Staff',
            'contact_ease' => 'Kemudahan Kontak',
            'price_fairness' => 'Kewajaran Harga',
            'cost_transparency' => 'Transparansi Biaya',
            'invoice_accuracy' => 'Akurasi Invoice',
        ];
        
        $averages = [];
        
        foreach ($fields as $field => $label) {
            $avg = CustomerSurvey::complete()
                ->whereNotNull($field)
                ->avg($field);
            
            if ($avg) {
                $averages[$label] = round($avg, 2);
            }
        }
        
        asort($averages);
        
        return array_slice($averages, 0, $limit, true);
    }

    /**
     * Get keyword analysis from open-ended feedback
     */
    public function getKeywordAnalysis(string $field = 'appreciate_most', int $limit = 20): array
    {
        $surveys = CustomerSurvey::complete()
            ->whereNotNull($field)
            ->pluck($field);
        
        if ($surveys->isEmpty()) return [];
        
        $text = $surveys->implode(' ');
        
        // Simple word frequency (exclude common words)
        $stopWords = [
            'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'pada', 'ini', 'itu',
            'adalah', 'tidak', 'ada', 'juga', 'lebih', 'sangat', 'sudah', 'akan', 'bisa',
            'the', 'is', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for'
        ];
        
        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, fn($w) => !in_array($w, $stopWords) && strlen($w) > 3);
        
        $frequency = array_count_values($words);
        arsort($frequency);
        
        return array_slice($frequency, 0, $limit, true);
    }

    /**
     * Get year-over-year comparison
     */
    public function getYearComparison(int $currentYear, int $previousYear): array
    {
        $current = $this->calculateNPS($currentYear);
        $previous = $this->calculateNPS($previousYear);
        
        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'current_nps' => $current['nps_score'],
            'previous_nps' => $previous['nps_score'],
            'nps_change' => round($current['nps_score'] - $previous['nps_score'], 2),
            'current_responses' => $current['total_responses'],
            'previous_responses' => $previous['total_responses'],
        ];
    }

    /**
     * Get dashboard overview
     */
    public function getDashboardOverview(string $year = null, string $quarter = null): array
    {
        $year = $year ?: date('Y');
        
        return [
            'nps' => $this->calculateNPS($year, $quarter),
            'satisfaction' => $this->getSatisfactionStats($year, $quarter),
            'categories' => $this->getCategoryAverages($year, $quarter),
            'service_usage' => $this->getServiceUsage($year, $quarter),
            'top_strengths' => $this->getTopStrengths(3),
            'improvements' => $this->getAreasForImprovement(3),
            'total_responses' => CustomerSurvey::complete()->year($year)->count(),
            'flagged_count' => CustomerSurvey::flagged()->year($year)->count(),
        ];
    }
}
