<?php

namespace App\Mail;

use App\Models\Testimonial;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestimonialReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Testimonial $testimonial) {}

    public function build()
    {
        $customer = $this->testimonial->customer;
        $invoice  = $this->testimonial->invoice;
        $lang     = $customer?->preferred_language ?? 'id';

        $subject = $lang === 'en'
            ? 'Reminder: Share Your Experience with Us'
            : 'Pengingat: Bagikan Pengalaman Anda Bersama Kami';

        return $this->subject($subject)
            ->view('emails.testimonial-reminder')
            ->with([
                'customerName'   => $customer?->company_name ?? 'Valued Customer',
                'invoiceNumber'  => $invoice?->invoice_number ?? '-',
                'testimonialUrl' => route('testimonial.form', ['token' => $this->testimonial->token]),
                'surveyUrl'      => route('survey.public'),
                'lang'           => $lang,
            ]);
    }
}
