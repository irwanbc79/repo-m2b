<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni Baru Menunggu Moderasi</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6;">
<tr><td align="center" style="padding: 40px 20px;">

<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">

    <!-- HEADER -->
    <tr>
        <td style="background: #0F2C59; padding: 28px 30px; text-align: center;">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logistics" height="48" style="display: block; margin: 0 auto 10px;">
            <div style="font-size: 10px; letter-spacing: 4px; color: #93c5fd; font-weight: 600;">TESTIMONI PELANGGAN</div>
        </td>
    </tr>

    <!-- TITLE -->
    <tr>
        <td style="padding: 28px 30px 16px;">
            <h2 style="margin: 0 0 6px; font-size: 20px; color: #0F2C59;">⭐ Testimoni Baru Menunggu Moderasi</h2>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">Silakan review dan setujui atau tolak di halaman admin.</p>
        </td>
    </tr>

    <!-- CARD TESTIMONI -->
    <tr>
        <td style="padding: 0 30px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
                <tr>
                    <td style="padding: 16px;">
                        <!-- Rating bintang -->
                        <div style="margin-bottom: 10px;">
                            @for($i = 1; $i <= 5; $i++)
                                <span style="font-size: 20px; color: {{ $i <= $testimonial->rating ? '#f59e0b' : '#d1d5db' }};">★</span>
                            @endfor
                            <span style="font-size: 13px; color: #6b7280; margin-left: 6px;">{{ $testimonial->rating }}/5</span>
                        </div>

                        <!-- Isi testimoni -->
                        <blockquote style="margin: 0 0 12px; padding: 12px; background: #fff; border-left: 4px solid #0F2C59; border-radius: 0 8px 8px 0; font-size: 14px; color: #374151; font-style: italic; line-height: 1.6;">
                            "{{ $testimonial->content }}"
                        </blockquote>

                        <!-- Info pengirim -->
                        <div style="font-size: 13px; color: #374151;">
                            <strong>{{ $testimonial->display_name ?: 'Tidak ada nama' }}</strong>
                            @if($testimonial->company_name)
                                — {{ $testimonial->company_name }}
                            @endif
                            @if($testimonial->position)
                                <span style="color: #6b7280;">({{ $testimonial->position }})</span>
                            @endif
                        </div>
                        <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                            Dikirim: {{ $testimonial->updated_at?->format('d F Y, H:i') ?? now()->format('d F Y, H:i') }} WIB
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- CTA -->
    <tr>
        <td style="padding: 0 30px 32px; text-align: center;">
            <a href="{{ $moderationUrl }}"
               style="display: inline-block; background: #0F2C59; color: #ffffff; text-decoration: none;
                      font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 8px;">
                Buka Halaman Moderasi Testimoni
            </a>
        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background: #f8fafc; padding: 16px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; font-size: 11px; color: #9ca3af;">
                PT. Mora Multi Berkah &nbsp;·&nbsp; portal.m2b.co.id &nbsp;·&nbsp; Notifikasi Otomatis
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
