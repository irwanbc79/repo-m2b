<?php

namespace App\Mail;

use App\Models\CustomerSurvey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Alert ke staf: survey dengan skor rendah (detractor) — perlu service recovery cepat.
 */
class SurveyDetractorAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CustomerSurvey $survey) {}

    public function build()
    {
        $who = $this->survey->company_name ?: 'Anonim';

        return $this
            ->subject('⚠️ [M2B] Perlu Tindak Lanjut — Survey Skor Rendah (' . $who . ')')
            ->view('emails.survey-detractor-alert')
            ->with([
                'survey'       => $this->survey,
                'dashboardUrl' => url('/admin/survey/dashboard'),
            ]);
    }
}
