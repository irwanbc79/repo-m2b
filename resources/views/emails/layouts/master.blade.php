<!DOCTYPE html>
<html lang="{{ $lang ?? 'id' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>@yield('title', 'M2B Logistics')</title>
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<![endif]-->
<style>
    body, table, td { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; }
    body { margin: 0; padding: 0; background-color: #EEF2F7; -webkit-text-size-adjust: 100%; }
    table { border-collapse: collapse; }
    img { border: 0; display: block; }
    a { text-decoration: none; }
    .m2b-btn:hover { opacity: 0.92; }
    @media only screen and (max-width: 620px) {
        .m2b-wrapper { width: 100% !important; }
        .m2b-px { padding-left: 20px !important; padding-right: 20px !important; }
    }
</style>
</head>
<body style="margin:0; padding:0; background-color:#EEF2F7;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EEF2F7;">
<tr>
<td align="center" style="padding: 32px 16px;">

    <table role="presentation" class="m2b-wrapper" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(15,44,89,0.08);">

        {{-- Top accent stripe --}}
        <tr>
            <td style="background-color:#B91C1C; height:5px; line-height:5px; font-size:0;">&nbsp;</td>
        </tr>

        {{-- Header / letterhead --}}
        <tr>
            <td style="background-color:#0F2C59; padding:26px 30px; text-align:center;">
                <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B" height="34" style="height:34px; width:auto; margin:0 auto 10px; display:block; margin-left:auto; margin-right:auto;">
                <div style="font-size:10px; font-weight:700; letter-spacing:3px; text-transform:uppercase; color:#93a5c4;">
                    Logistic &nbsp;·&nbsp; Solution &nbsp;·&nbsp; Partner
                </div>
            </td>
        </tr>

        {{-- Content --}}
        <tr>
            <td class="m2b-px" style="padding: 36px 40px; color:#1F2937; font-size:14px; line-height:1.65;">
                @yield('content')
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="background-color:#F8FAFC; border-top:1px solid #E5E9F0; padding:26px 40px; text-align:center;">
                <div style="font-size:12px; font-weight:800; color:#0F2C59; letter-spacing:0.3px;">PT. MORA MULTI BERKAH</div>
                <div style="font-size:11px; color:#9AA5B4; margin-top:3px; line-height:1.6;">
                    Jl. Kapt. Sumarsono Komp. Graha Metropolitan Blok G No. 14, Medan - Indonesia<br>
                    Telp: 061-44020012
                </div>
                <div style="font-size:11px; color:#9AA5B4; margin-top:10px;">
                    📧 <a href="mailto:sales@m2b.co.id" style="color:#0F2C59; font-weight:600;">sales@m2b.co.id</a>
                    &nbsp;·&nbsp;
                    💬 <a href="https://wa.me/6281263027818" style="color:#0F2C59; font-weight:600;">+62 812-6302-7818</a>
                    &nbsp;·&nbsp;
                    🌐 <a href="{{ url('/') }}" style="color:#0F2C59; font-weight:600;">portal.m2b.co.id</a>
                </div>
                <div style="font-size:10px; color:#B7C0CC; margin-top:14px; line-height:1.5;">
                    @yield('footerNote', 'Email ini dikirim otomatis oleh sistem M2B, mohon tidak membalas ke alamat ini.')<br>
                    &copy; {{ date('Y') }} PT. Mora Multi Berkah. Semua hak dilindungi.
                </div>
            </td>
        </tr>
    </table>

</td>
</tr>
</table>
</body>
</html>
