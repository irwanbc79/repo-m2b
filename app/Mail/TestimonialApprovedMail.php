<?php

namespace App\Mail;

use App\Models\Testimonial;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestimonialApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Testimonial $testimonial) {}

    public function build()
    {
        $lang           = $this->testimonial->customer?->preferred_language ?? 'id';
        $googleReviewUrl = config('app.google_review_url');

        return $this->subject($lang === 'en' ? 'Your Testimonial Has Been Published! ⭐' : 'Testimoni Anda Telah Ditayangkan! ⭐')
            ->view('emails.testimonial-approved')
            ->with([
                'testimonial'    => $this->testimonial,
                'lang'           => $lang,
                'googleReviewUrl' => $googleReviewUrl,
                'publicUrl'      => route('testimonial.index'),
            ]);
    }
}
