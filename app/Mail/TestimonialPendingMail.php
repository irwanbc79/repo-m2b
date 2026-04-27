<?php

namespace App\Mail;

use App\Models\Testimonial;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestimonialPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Testimonial $testimonial) {}

    public function build()
    {
        $name = $this->testimonial->display_name ?: ($this->testimonial->company_name ?: 'Customer');

        return $this
            ->subject('[M2B] Testimoni Baru Menunggu Moderasi — ' . $name)
            ->view('emails.testimonial-pending')
            ->with([
                'testimonial'    => $this->testimonial,
                'moderationUrl'  => url('/admin/testimoni'),
            ]);
    }
}
