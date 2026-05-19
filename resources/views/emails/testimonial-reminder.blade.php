<!DOCTYPE html>
<html lang="{{ $lang ?? 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $lang === 'en' ? 'Reminder: Share Your Experience' : 'Pengingat: Bagikan Pengalaman Anda' }}</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6;">
<tr><td align="center" style="padding: 40px 20px;">

<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">

    <!-- HEADER -->
    <tr>
        <td style="background: #0F2C59; padding: 28px 30px; text-align: center;">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logistics" height="56" style="display: block; margin: 0 auto 10px; max-height: 56px;">
            <div style="font-size: 10px; letter-spacing: 4px; color: #93c5fd; font-weight: 600;">LOGISTIC &nbsp;·&nbsp; SOLUTION &nbsp;·&nbsp; PARTNER</div>
        </td>
    </tr>

    <!-- HERO -->
    <tr>
        <td style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 36px 30px; text-align: center; border-bottom: 3px solid #3b82f6;">
            <div style="font-size: 52px; margin-bottom: 12px;">💬</div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e3a5f; margin: 0 0 10px;">
                {{ $lang === 'en' ? 'We'd Love to Hear From You!' : 'Pendapat Anda Sangat Berarti!' }}
            </h1>
            <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0;">
                {{ $lang === 'en'
                    ? "Hi {$customerName}, we noticed you haven't shared your experience yet."
                    : "Halo {$customerName}, kami perhatikan Anda belum membagikan pengalaman Anda." }}
            </p>
        </td>
    </tr>

    <!-- BODY -->
    <tr>
        <td style="padding: 32px 40px;">
            <p style="font-size: 15px; color: #374151; line-height: 1.7; margin: 0 0 20px;">
                {{ $lang === 'en'
                    ? "A few days ago we sent you an invitation to leave a testimonial for shipment #{$invoiceNumber}. It only takes 2 minutes and helps us serve you better."
                    : "Beberapa hari lalu kami mengirim undangan untuk memberikan testimoni terkait pengiriman #{$invoiceNumber}. Hanya butuh 2 menit dan membantu kami melayani Anda lebih baik." }}
            </p>

            <!-- CTA TESTIMONI -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 16px;">
                <tr>
                    <td align="center">
                        <a href="{{ $testimonialUrl }}"
                           style="display: inline-block; background: #0F2C59; color: #ffffff; font-size: 15px; font-weight: 700; padding: 14px 36px; border-radius: 8px; text-decoration: none; letter-spacing: 0.3px;">
                            ⭐ {{ $lang === 'en' ? 'Write My Testimonial' : 'Tulis Testimoni Saya' }}
                        </a>
                    </td>
                </tr>
            </table>

            <!-- CTA SURVEY -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 28px;">
                <tr>
                    <td align="center">
                        <a href="{{ $surveyUrl }}"
                           style="display: inline-block; background: #ffffff; color: #0F2C59; font-size: 14px; font-weight: 600; padding: 12px 32px; border-radius: 8px; text-decoration: none; border: 2px solid #0F2C59;">
                            📋 {{ $lang === 'en' ? 'Fill Satisfaction Survey' : 'Isi Survey Kepuasan' }}
                        </a>
                    </td>
                </tr>
            </table>

            <p style="font-size: 13px; color: #6b7280; line-height: 1.6; margin: 0;">
                {{ $lang === 'en'
                    ? 'This is our final reminder. Your feedback helps us improve our service quality for all customers.'
                    : 'Ini adalah pengingat terakhir kami. Masukan Anda membantu kami meningkatkan kualitas layanan untuk semua pelanggan.' }}
            </p>
        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 40px; text-align: center;">
            <p style="font-size: 12px; color: #9ca3af; margin: 0 0 6px;">
                {{ $lang === 'en' ? 'Thank you for trusting M2B Logistics.' : 'Terima kasih telah mempercayai M2B Logistics.' }}
            </p>
            <p style="font-size: 12px; color: #9ca3af; margin: 0;">
                © {{ date('Y') }} M2B Logistics &nbsp;·&nbsp; {{ config('app.url') }}
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
