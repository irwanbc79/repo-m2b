<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background:#f3f4f6; margin:0; padding:0;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;">
<tr><td align="center" style="padding:40px 20px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.08);">

    <tr><td style="background:#0F2C59; padding:28px 30px; text-align:center;">
        <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logistics" height="52" style="display:block; margin:0 auto 10px; max-height:52px;">
        <div style="font-size:10px; letter-spacing:4px; color:#93c5fd; font-weight:600;">LOGISTIC &nbsp;·&nbsp; SOLUTION &nbsp;·&nbsp; PARTNER</div>
    </td></tr>

    <tr><td style="background:linear-gradient(135deg,#eff6ff,#dbeafe); padding:34px 30px; text-align:center; border-bottom:3px solid #3b82f6;">
        <div style="font-size:48px; margin-bottom:10px;">📋</div>
        <h1 style="font-size:22px; font-weight:800; color:#1e3a5f; margin:0 0 8px;">Survey Kepuasan {{ $year }}</h1>
        <p style="font-size:15px; color:#374151; line-height:1.6; margin:0;">Halo {{ $customerName }}, di awal tahun ini kami ingin mendengar pengalaman Anda bersama M2B.</p>
    </td></tr>

    <tr><td style="padding:30px 40px;">
        <p style="font-size:15px; color:#374151; line-height:1.7; margin:0 0 18px;">
            Masukan Anda membantu kami meningkatkan ketepatan waktu, kejelasan biaya, dan penanganan dokumen kepabeanan.
            Survey ini singkat — <strong>sekitar 5 menit</strong>, dan hanya sekali di tahun {{ $year }}.
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
            <tr><td align="center">
                <a href="{{ $surveyUrl }}" style="display:inline-block; background:#0F2C59; color:#ffffff; font-size:15px; font-weight:700; padding:14px 40px; border-radius:8px; text-decoration:none;">Isi Survey Sekarang</a>
            </td></tr>
        </table>
        <p style="font-size:13px; color:#6b7280; line-height:1.6; margin:0;">
            Terima kasih telah mempercayai M2B sebagai mitra logistik & kepabeanan Anda. 🙏
        </p>
    </td></tr>

    <tr><td style="background:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 40px; text-align:center;">
        <p style="font-size:12px; color:#9ca3af; margin:0;">© {{ date('Y') }} M2B Logistics &nbsp;·&nbsp; {{ config('app.url') }}</p>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
