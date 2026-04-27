<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Kepuasan Baru Masuk</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6;">
<tr><td align="center" style="padding: 40px 20px;">

<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">

    <!-- HEADER -->
    <tr>
        <td style="background: #0F2C59; padding: 28px 30px; text-align: center;">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logistics" height="48" style="display: block; margin: 0 auto 10px;">
            <div style="font-size: 10px; letter-spacing: 4px; color: #93c5fd; font-weight: 600;">SURVEY KEPUASAN PELANGGAN</div>
        </td>
    </tr>

    <!-- TITLE -->
    <tr>
        <td style="padding: 28px 30px 0;">
            <h2 style="margin: 0 0 6px; font-size: 20px; color: #0F2C59;">📋 Survey Baru Masuk</h2>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                {{ $survey->response_date?->format('d F Y, H:i') ?? now()->format('d F Y, H:i') }} WIB
            </p>
        </td>
    </tr>

    <!-- INFO BARIS -->
    <tr>
        <td style="padding: 20px 30px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                        <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 2px;">Perusahaan</span>
                        <strong style="font-size: 15px; color: #111827;">
                            {{ $survey->is_anonymous ? '(Anonim)' : ($survey->company_name ?? '-') }}
                        </strong>
                    </td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                        <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 2px;">Posisi</span>
                        <strong style="font-size: 15px; color: #111827;">
                            {{ ucfirst($survey->respondent_position ?? '-') }}
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                        <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 2px;">Kepuasan Keseluruhan</span>
                        <strong style="font-size: 22px; color: #059669;">
                            {{ $survey->overall_satisfaction ?? '-' }}/5 ⭐
                        </strong>
                    </td>
                    <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                        <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 2px;">NPS Score</span>
                        <strong style="font-size: 22px; color:
                            @if(($survey->nps_score ?? 0) >= 9) #059669
                            @elseif(($survey->nps_score ?? 0) >= 7) #d97706
                            @else #dc2626
                            @endif;">
                            {{ $survey->nps_score ?? '-' }}/10
                        </strong>
                    </td>
                </tr>
                @if($survey->appreciate_most)
                <tr>
                    <td colspan="2" style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                        <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Yang paling diapresiasi</span>
                        <p style="margin: 0; font-size: 14px; color: #374151; font-style: italic;">"{{ $survey->appreciate_most }}"</p>
                    </td>
                </tr>
                @endif
                @if($survey->needs_improvement)
                <tr>
                    <td colspan="2" style="padding: 12px 16px;">
                        <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Yang perlu ditingkatkan</span>
                        <p style="margin: 0; font-size: 14px; color: #374151; font-style: italic;">"{{ $survey->needs_improvement }}"</p>
                    </td>
                </tr>
                @endif
            </table>
        </td>
    </tr>

    @if($survey->willing_to_contact && $survey->contact_email)
    <!-- FOLLOW-UP -->
    <tr>
        <td style="padding: 0 30px 16px;">
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px;">
                <strong style="color: #1d4ed8; font-size: 13px;">📞 Bersedia dihubungi</strong><br>
                <span style="font-size: 13px; color: #374151;">
                    Email: {{ $survey->contact_email }}
                    @if($survey->contact_phone) | HP: {{ $survey->contact_phone }} @endif
                </span>
            </div>
        </td>
    </tr>
    @endif

    <!-- CTA -->
    <tr>
        <td style="padding: 8px 30px 32px; text-align: center;">
            <a href="{{ $dashboardUrl }}"
               style="display: inline-block; background: #0F2C59; color: #ffffff; text-decoration: none;
                      font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 8px;">
                Lihat Semua Survey di Dashboard
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
