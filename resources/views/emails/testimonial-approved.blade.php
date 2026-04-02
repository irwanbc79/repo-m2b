<!DOCTYPE html>
<html lang="{{ $lang ?? 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $lang === 'en' ? 'Your Testimonial Has Been Published!' : 'Testimoni Anda Telah Ditayangkan!' }}</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6;">
<tr><td align="center" style="padding: 40px 20px;">

<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">

    <!-- HEADER WITH LOGO -->
    <tr>
        <td style="background: #0F2C59; padding: 28px 30px; text-align: center;">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logistics" height="56" style="display: block; margin: 0 auto 10px; max-height: 56px;">
            <div style="font-size: 10px; letter-spacing: 4px; color: #93c5fd; font-weight: 600;">LOGISTIC &nbsp;·&nbsp; SOLUTION &nbsp;·&nbsp; PARTNER</div>
        </td>
    </tr>

    <!-- HERO -->
    <tr>
        <td style="background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%); padding: 36px 30px; text-align: center; border-bottom: 3px solid #fde047;">
            <div style="font-size: 52px; margin-bottom: 12px;">⭐</div>
            <h1 style="font-size: 24px; font-weight: 800; color: #713f12; margin: 0 0 10px;">
                {{ $lang === 'en' ? 'Your Testimonial is Now Live!' : 'Testimoni Anda Telah Ditayangkan!' }}
            </h1>
            <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0;">
                {{ $lang === 'en' ? 'Thank you for sharing your experience with M2B Logistics.' : 'Terima kasih telah berbagi pengalaman Anda bersama M2B Logistics.' }}
            </p>
        </td>
    </tr>

    <!-- CONTENT -->
    <tr>
        <td style="padding: 36px 30px; color: #374151; line-height: 1.7;">

            <p style="font-size: 14px; color: #6b7280; margin: 0 0 4px;">{{ $lang === 'en' ? 'Dear' : 'Yang Terhormat' }}</p>
            <p style="font-size: 17px; font-weight: 700; color: #0F2C59; margin: 0 0 16px;">{{ $testimonial->display_name }},</p>

            <p style="font-size: 15px; color: #4b5563; margin: 0 0 24px; line-height: 1.7;">
                @if($lang === 'en')
                    We are delighted to inform you that your testimonial has been reviewed and is now <strong>published</strong> on our platform. Your feedback means a great deal to us and helps others learn about the quality of our services.
                @else
                    Kami dengan senang hati memberitahukan bahwa testimoni Anda telah ditinjau dan kini <strong>ditayangkan</strong> di platform kami. Masukan Anda sangat berarti bagi kami dan membantu orang lain mengetahui kualitas layanan kami.
                @endif
            </p>

            <!-- TESTIMONIAL PREVIEW CARD -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #1e3a8a; border-radius: 10px; margin: 0 0 28px;">
                <tr>
                    <td style="padding: 20px 24px;">
                        <!-- Rating Stars -->
                        <div style="margin-bottom: 10px; font-size: 20px; letter-spacing: 2px; color: #f59e0b;">
                            @for($i = 1; $i <= 5; $i++){{ $i <= $testimonial->rating ? '★' : '☆' }}@endfor
                        </div>
                        @if($testimonial->content)
                        <p style="font-size: 14px; color: #374151; font-style: italic; line-height: 1.7; margin: 0 0 12px;">"{{ $testimonial->content }}"</p>
                        @endif
                        <p style="font-size: 13px; font-weight: 700; color: #0F2C59; margin: 0;">{{ $testimonial->display_name }}</p>
                        @if($testimonial->position || $testimonial->company_name)
                        <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0;">
                            {{ collect([$testimonial->position, $testimonial->company_name])->filter()->implode(' · ') }}
                        </p>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- VIEW PUBLIC PAGE BUTTON -->
            <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto 28px;">
                <tr>
                    <td align="center" bgcolor="#1e3a8a" style="border-radius: 8px; background-color: #1e3a8a;">
                        <a href="{{ $publicUrl }}"
                           style="display: inline-block; padding: 14px 36px; background-color: #1e3a8a; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px; letter-spacing: 0.3px; mso-padding-alt: 14px 36px; font-family: Helvetica, Arial, sans-serif;">
                            🌐 &nbsp;{{ $lang === 'en' ? 'View Our Testimonials Page' : 'Lihat Halaman Testimoni Kami' }}
                        </a>
                    </td>
                </tr>
            </table>

            @if($googleReviewUrl)
            <!-- GOOGLE REVIEW CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; margin: 0 0 28px;">
                <tr>
                    <td style="padding: 24px; text-align: center;">
                        <p style="font-size: 16px; font-weight: 700; color: #1f2937; margin: 0 0 8px;">
                            {{ $lang === 'en' ? '⭐ Help Us Reach More Customers' : '⭐ Bantu Kami Menjangkau Lebih Banyak Pelanggan' }}
                        </p>
                        <p style="font-size: 13px; color: #4b5563; margin: 0 0 20px; line-height: 1.6;">
                            {{ $lang === 'en'
                                ? 'If you have a moment, leaving a Google review would help more businesses discover our services.'
                                : 'Jika Anda punya waktu, ulasan Google Anda akan sangat membantu lebih banyak pelanggan menemukan layanan kami.' }}
                        </p>
                        <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                            <tr>
                                <td align="center" bgcolor="#ffffff" style="border-radius: 8px; background-color: #ffffff; border: 2px solid #1e3a8a;">
                                    <a href="{{ $googleReviewUrl }}"
                                       style="display: inline-block; padding: 12px 30px; background-color: #ffffff; color: #1e3a8a; text-decoration: none; border-radius: 6px; font-weight: 700; font-size: 14px; mso-padding-alt: 12px 30px; font-family: Helvetica, Arial, sans-serif;">
                                        {{ $lang === 'en' ? 'Write a Google Review' : 'Tulis Ulasan di Google' }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            @endif

            <p style="font-size: 15px; color: #4b5563; margin: 0 0 16px;">
                {{ $lang === 'en'
                    ? 'Thank you again for your trust and continued support. We look forward to serving you again.'
                    : 'Terima kasih sekali lagi atas kepercayaan dan dukungan Anda. Kami berharap dapat melayani Anda kembali.' }}
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top: 1px solid #e5e7eb; margin-top: 24px;">
                <tr>
                    <td style="padding-top: 20px;">
                        <p style="font-weight: 700; color: #0F2C59; font-size: 15px; margin: 0 0 4px;">
                            {{ $lang === 'en' ? 'M2B Logistics Team' : 'Tim M2B Logistics' }}
                        </p>
                        <p style="font-size: 13px; color: #6b7280; margin: 0;">Customer Relations · PT. Mora Multi Berkah</p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background-color: #0F2C59; padding: 24px 30px; text-align: center;">
            <p style="font-size: 13px; font-weight: 700; color: #93c5fd; margin: 0 0 6px;">PT. MORA MULTI BERKAH</p>
            <p style="font-size: 12px; color: #cbd5e1; margin: 0 0 14px; line-height: 1.8;">
                📧 sales@m2b.co.id &nbsp;|&nbsp; 🌐 portal.m2b.co.id
            </p>
            <p style="font-size: 11px; color: #475569; margin: 0; line-height: 1.6;">
                {{ $lang === 'en'
                    ? 'This email was sent automatically. Please do not reply to this email.'
                    : 'Email ini dikirim otomatis. Mohon tidak membalas email ini.' }}
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
