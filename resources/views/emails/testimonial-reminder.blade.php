@extends('emails.layouts.master')

@section('title', $lang === 'en' ? 'Reminder: Share Your Experience' : 'Pengingat: Bagikan Pengalaman Anda')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%); border:1px solid #BFDBFE; border-radius:10px; margin-bottom:24px;">
        <tr>
            <td style="padding:28px; text-align:center;">
                <div style="font-size:44px; margin-bottom:10px;">💬</div>
                <div style="font-size:19px; font-weight:800; color:#0F2C59; margin-bottom:8px;">
                    {{ $lang === 'en' ? "We'd Love to Hear From You!" : 'Pendapat Anda Sangat Berarti!' }}
                </div>
                <div style="font-size:14px; color:#374151;">
                    {{ $lang === 'en' ? "Hi {$customerName}, we noticed you haven't shared your experience yet." : "Halo {$customerName}, kami perhatikan Anda belum membagikan pengalaman Anda." }}
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; color:#4B5563; line-height:1.7;">
        {{ $lang === 'en'
            ? "A few days ago we sent you an invitation to leave a testimonial for shipment #{$invoiceNumber}. It only takes 2 minutes and helps us serve you better."
            : "Beberapa hari lalu kami mengirim undangan untuk memberikan testimoni terkait pengiriman #{$invoiceNumber}. Hanya butuh 2 menit dan membantu kami melayani Anda lebih baik." }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
        <tr>
            <td align="center">
                <a href="{{ $testimonialUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:15px; padding:13px 32px; border-radius:8px;">⭐ {{ $lang === 'en' ? 'Write My Testimonial' : 'Tulis Testimoni Saya' }}</a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td align="center">
                <a href="{{ $surveyUrl }}" style="display:inline-block; background-color:#ffffff; color:#0F2C59; font-weight:700; font-size:14px; padding:11px 28px; border-radius:8px; border:2px solid #0F2C59;">📋 {{ $lang === 'en' ? 'Fill Satisfaction Survey' : 'Isi Survey Kepuasan' }}</a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:13px; color:#6B7280;">
        {{ $lang === 'en' ? 'This is our final reminder. Your feedback helps us improve our service quality for all customers.' : 'Ini adalah pengingat terakhir kami. Masukan Anda membantu kami meningkatkan kualitas layanan untuk semua pelanggan.' }}
    </p>
@endsection

@section('footerNote', $lang === 'en' ? 'Thank you for trusting M2B Logistics.' : 'Terima kasih telah mempercayai M2B Logistics.')
