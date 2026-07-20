<?php

namespace App\Services;

use App\Mail\SurveyDetractorAlertMail;
use App\Models\CustomerSurvey;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tindak lanjut otomatis pasca-submit survey (close the loop):
 *  - Detractor (NPS ≤ 6 / overall < 3): alert staf untuk service recovery.
 *    (Flagging is_flagged sudah otomatis di model CustomerSurvey::boot.)
 *  - Promoter (NPS ≥ 9): ajak menulis testimoni (in-the-moment, TANPA email spam).
 */
class SurveyFollowUpService
{
    /**
     * @return string|null Token testimoni bila promoter diajak (untuk CTA thank-you).
     */
    public function process(CustomerSurvey $survey): ?string
    {
        // Detractor → alert staf (recovery). Non-blocking.
        if ($survey->needsFollowUp()) {
            $this->alertStaff($survey);
            return null;
        }

        // Promoter → ajak testimoni bila customer dikenal & belum punya testimoni bermakna.
        if ($survey->nps_score !== null && $survey->nps_score >= 9) {
            return $this->invitePromoterTestimonial($survey);
        }

        return null;
    }

    protected function alertStaff(CustomerSurvey $survey): void
    {
        try {
            Mail::to(config('mail.from.address'))->send(new SurveyDetractorAlertMail($survey));
        } catch (\Throwable $e) {
            Log::warning('SurveyFollowUp alertStaff gagal: ' . $e->getMessage());
        }
    }

    protected function invitePromoterTestimonial(CustomerSurvey $survey): ?string
    {
        $customerId = $survey->customer_id;
        if (! $customerId) {
            return null; // survey anonim / tak terpetakan ke customer
        }

        try {
            $existing = Testimonial::where('customer_id', $customerId)
                ->where('status', '!=', 'rejected')
                ->latest()->first();

            // Sudah punya testimoni bermakna (approved / sudah diisi) → tak perlu ajak.
            if ($existing && ($existing->status === 'approved' || trim((string) $existing->content) !== '')) {
                return null;
            }

            $t = $existing ?: Testimonial::create([
                'customer_id'      => $customerId,
                'display_name'     => $survey->company_name ?: '',
                'company_name'     => $survey->company_name ?: '',
                'rating'           => 5,
                'content'          => '',
                'status'           => 'pending',
                'token'            => Testimonial::generateToken(),
                'token_expires_at' => now()->addDays(90),
            ]);

            if (empty($t->token) || $t->isExpired()) {
                $t->update([
                    'token'            => Testimonial::generateToken(),
                    'token_expires_at' => now()->addDays(90),
                ]);
            }

            return $t->fresh()->token;
        } catch (\Throwable $e) {
            Log::warning('SurveyFollowUp invitePromoter gagal: ' . $e->getMessage());
            return null;
        }
    }
}
