@extends('emails.layouts.master')

@section('title', $lang === 'en' ? 'Your Testimonial Has Been Published!' : 'Testimoni Anda Telah Ditayangkan!')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#fefce8 0%,#fef9c3 100%); border:1px solid #FDE047; border-radius:10px; margin-bottom:24px;">
        <tr>
            <td style="padding:28px; text-align:center;">
                <div style="font-size:44px; margin-bottom:10px;">⭐</div>
                <div style="font-size:20px; font-weight:800; color:#713F12; margin-bottom:8px;">
                    {{ $lang === 'en' ? 'Your Testimonial is Now Live!' : 'Testimoni Anda Telah Ditayangkan!' }}
                </div>
                <div style="font-size:14px; color:#374151;">
                    {{ $lang === 'en' ? 'Thank you for sharing your experience with M2B Logistics.' : 'Terima kasih telah berbagi pengalaman Anda bersama M2B Logistics.' }}
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; font-size:13px; color:#6B7280;">{{ $lang === 'en' ? 'Dear' : 'Yang Terhormat' }}</p>
    <p style="margin:0 0 18px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $testimonial->display_name }}</p>

    <p style="margin:0 0 24px; color:#4B5563; line-height:1.7;">
        @if($lang === 'en')
            We are delighted to inform you that your testimonial has been reviewed and is now <strong style="color:#111827;">published</strong> on our platform. Your feedback means a great deal to us and helps others learn about the quality of our services.
        @else
            Kami dengan senang hati memberitahukan bahwa testimoni Anda telah ditinjau dan kini <strong style="color:#111827;">ditayangkan</strong> di platform kami. Masukan Anda sangat berarti bagi kami dan membantu orang lain mengetahui kualitas layanan kami.
        @endif
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-left:4px solid #0F2C59; border-radius:0 8px 8px 0; margin-bottom:24px;">
        <tr>
            <td style="padding:18px 20px;">
                <div style="margin-bottom:8px; font-size:18px; letter-spacing:2px; color:#F59E0B;">
                    @for($i = 1; $i <= 5; $i++){{ $i <= $testimonial->rating ? '★' : '☆' }}@endfor
                </div>
                @if($testimonial->content)
                <p style="font-size:14px; color:#374151; font-style:italic; margin:0 0 10px;">"{{ $testimonial->content }}"</p>
                @endif
                <p style="font-size:13px; font-weight:700; color:#0F2C59; margin:0;">{{ $testimonial->display_name }}</p>
                @if($testimonial->position || $testimonial->company_name)
                <p style="font-size:12px; color:#6B7280; margin:2px 0 0;">{{ collect([$testimonial->position, $testimonial->company_name])->filter()->implode(' · ') }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td align="center">
                <a href="{{ $publicUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:13px 32px; border-radius:8px;">🌐 {{ $lang === 'en' ? 'View Our Testimonials Page' : 'Lihat Halaman Testimoni Kami' }}</a>
            </td>
        </tr>
    </table>

    @if($googleReviewUrl)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EFF6FF; border:1px solid #BAE6FD; border-radius:10px; margin-bottom:24px;">
        <tr>
            <td style="padding:22px; text-align:center;">
                <p style="margin:0 0 8px; font-size:15px; font-weight:700; color:#1F2937;">
                    {{ $lang === 'en' ? '⭐ Help Us Reach More Customers' : '⭐ Bantu Kami Menjangkau Lebih Banyak Pelanggan' }}
                </p>
                <p style="margin:0 0 18px; font-size:13px; color:#4B5563;">
                    {{ $lang === 'en' ? 'If you have a moment, leaving a Google review would help more businesses discover our services.' : 'Jika Anda punya waktu, ulasan Google Anda akan sangat membantu lebih banyak pelanggan menemukan layanan kami.' }}
                </p>
                <a href="{{ $googleReviewUrl }}" style="display:inline-block; background-color:#ffffff; color:#0F2C59; font-weight:700; font-size:13px; padding:11px 26px; border-radius:8px; border:2px solid #0F2C59;">
                    {{ $lang === 'en' ? 'Write a Google Review' : 'Tulis Ulasan di Google' }}
                </a>
            </td>
        </tr>
    </table>
    @endif

    <p style="margin:0 0 20px; color:#4B5563;">
        {{ $lang === 'en' ? 'Thank you again for your trust and continued support. We look forward to serving you again.' : 'Terima kasih sekali lagi atas kepercayaan dan dukungan Anda. Kami berharap dapat melayani Anda kembali.' }}
    </p>

    <p style="margin:0; font-weight:700; color:#0F2C59;">{{ $lang === 'en' ? 'M2B Logistics Team' : 'Tim M2B Logistics' }}<br><span style="font-weight:400; color:#6B7280; font-size:13px;">Customer Relations · PT. Mora Multi Berkah</span></p>
@endsection

@section('footerNote', $lang === 'en' ? 'This email was sent automatically. Please do not reply to this email.' : 'Email ini dikirim otomatis. Mohon tidak membalas email ini.')
