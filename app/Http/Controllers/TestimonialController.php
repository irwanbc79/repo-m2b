<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestimonialApprovedMail;

class TestimonialController extends Controller
{
    // Halaman form publik (dari link email)
    public function form(string $token)
    {
        $testimonial = Testimonial::where('token', $token)->firstOrFail();

        // Kalau sudah diisi sebelumnya, redirect ke thank you
        if (!empty($testimonial->content)) {
            return redirect()->route('testimonial.thankyou');
        }

        return view('testimonials.form', compact('testimonial', 'token'));
    }

    // Submit form testimoni
    public function submit(Request $request, string $token)
    {
        $testimonial = Testimonial::where('token', $token)->firstOrFail();

        if (!empty($testimonial->content)) {
            return redirect()->route('testimonial.thankyou');
        }

        $request->validate([
            'display_name' => 'required|string|max:100',
            'company_name' => 'nullable|string|max:150',
            'position'     => 'nullable|string|max:100',
            'rating'       => 'required|integer|min:1|max:5',
            'content'      => 'required|string|min:10|max:1000',
        ]);

        $testimonial->update([
            'display_name' => $request->display_name,
            'company_name' => $request->company_name,
            'position'     => $request->position,
            'rating'       => $request->rating,
            'content'      => $request->content,
            'ip_address'   => $request->ip(),
        ]);

        return redirect()->route('testimonial.thankyou');
    }

    // Halaman terima kasih setelah submit
    public function thankyou()
    {
        $googleReviewUrl = config('app.google_review_url');
        return view('testimonials.thankyou', compact('googleReviewUrl'));
    }

    // Halaman publik tampil testimoni yang approved
    public function index()
    {
        $testimonials = Testimonial::approved()
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->latest('approved_at')
            ->get();

        $avgRating = $testimonials->avg('rating');
        $totalCount = $testimonials->count();

        return view('testimonials.index', compact('testimonials', 'avgRating', 'totalCount'));
    }
}
