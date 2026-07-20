<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background:#f3f4f6; margin:0; padding:0;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.08);">

    <tr><td style="background:#b91c1c; padding:22px 30px;">
        <div style="font-size:18px; font-weight:800; color:#ffffff;">⚠️ Survey Skor Rendah — Perlu Tindak Lanjut</div>
        <div style="font-size:12px; color:#fecaca; margin-top:4px;">Segera lakukan service recovery (idealnya &lt; 48 jam).</div>
    </td></tr>

    <tr><td style="padding:28px 34px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px; color:#374151;">
            <tr><td style="padding:6px 0; color:#6b7280; width:180px;">Perusahaan</td><td style="padding:6px 0; font-weight:700;">{{ $survey->company_name ?: 'Anonim' }}</td></tr>
            <tr><td style="padding:6px 0; color:#6b7280;">Kategori NPS</td><td style="padding:6px 0; font-weight:700; color:#b91c1c;">{{ $survey->nps_category ?? '-' }} (skor {{ $survey->nps_score ?? '-' }}/10)</td></tr>
            <tr><td style="padding:6px 0; color:#6b7280;">Kepuasan Umum</td><td style="padding:6px 0;">{{ $survey->overall_satisfaction ? $survey->overall_satisfaction.'/5' : '-' }}</td></tr>
            <tr><td style="padding:6px 0; color:#6b7280;">Tanggal</td><td style="padding:6px 0;">{{ \Carbon\Carbon::parse($survey->response_date)->format('d M Y H:i') }}</td></tr>
        </table>

        @if($survey->needs_improvement)
        <div style="margin-top:18px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 16px;">
            <div style="font-size:11px; font-weight:700; color:#991b1b; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:6px;">Yang perlu diperbaiki (kata customer)</div>
            <div style="font-size:14px; color:#374151; line-height:1.6;">"{{ $survey->needs_improvement }}"</div>
        </div>
        @endif

        <div style="margin-top:18px; background:{{ $survey->willing_to_contact ? '#ecfdf5' : '#f9fafb' }}; border:1px solid {{ $survey->willing_to_contact ? '#a7f3d0' : '#e5e7eb' }}; border-radius:8px; padding:14px 16px;">
            @if($survey->willing_to_contact)
                <div style="font-size:13px; color:#065f46;"><strong>✓ Bersedia dihubungi.</strong> Hubungi segera:</div>
                <div style="font-size:14px; color:#111827; margin-top:6px;">
                    {{ $survey->contact_email ?: '—' }}{{ $survey->contact_phone ? ' · '.$survey->contact_phone : '' }}
                </div>
            @else
                <div style="font-size:13px; color:#6b7280;">Customer tidak mencantumkan kontak. Cek riwayat akun untuk tindak lanjut.</div>
            @endif
        </div>

        <div style="text-align:center; margin-top:24px;">
            <a href="{{ $dashboardUrl }}" style="display:inline-block; background:#0F2C59; color:#ffffff; font-size:14px; font-weight:700; padding:12px 30px; border-radius:8px; text-decoration:none;">Buka Dashboard Survey</a>
        </div>
    </td></tr>

    <tr><td style="background:#f9fafb; border-top:1px solid #e5e7eb; padding:16px 30px; text-align:center;">
        <div style="font-size:11px; color:#9ca3af;">Notifikasi otomatis M2B Portal · Prioritas: retensi pelanggan</div>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
