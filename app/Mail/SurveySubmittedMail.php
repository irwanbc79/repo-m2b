<?php

namespace App\Mail;

use App\Models\CustomerSurvey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SurveySubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CustomerSurvey $survey) {}

    public function build()
    {
        return $this
            ->subject('[M2B] Survey Kepuasan Baru — ' . ($this->survey->company_name ?? 'Anonim'))
            ->view('emails.survey-submitted')
            ->with([
                'survey'       => $this->survey,
                'dashboardUrl' => url('/admin/survey/dashboard'),
            ]);
    }
}
