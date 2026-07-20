<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Undangan survey kepuasan tahunan (dikirim awal tahun).
 */
class SurveyAnnualInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ?Customer $customer = null, public ?int $year = null) {}

    public function build()
    {
        $year = $this->year ?? (int) date('Y');

        return $this
            ->subject('Bantu Kami Jadi Lebih Baik — Survey Kepuasan M2B ' . $year)
            ->view('emails.survey-annual-invite')
            ->with([
                'customerName' => $this->customer?->company_name ?? 'Mitra M2B',
                'surveyUrl'    => route('survey.public'),
                'year'         => $year,
            ]);
    }
}
