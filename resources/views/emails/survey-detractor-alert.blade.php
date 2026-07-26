@extends('emails.layouts.master')

@section('title', 'Survey Skor Rendah — Perlu Tindak Lanjut')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#B91C1C; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <div style="font-size:16px; font-weight:800; color:#ffffff;">⚠️ Survey Skor Rendah — Perlu Tindak Lanjut</div>
                <div style="font-size:12px; color:#FECACA; margin-top:4px;">Segera lakukan service recovery (idealnya &lt; 48 jam).</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; margin-bottom:6px;">
        <tr><td style="padding:6px 0; color:#6B7280; width:180px;">Perusahaan</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $survey->company_name ?: 'Anonim' }}</td></tr>
        <tr><td style="padding:6px 0; color:#6B7280;">Kategori NPS</td><td style="padding:6px 0; font-weight:700; color:#B91C1C;">{{ $survey->nps_category ?? '-' }} (skor {{ $survey->nps_score ?? '-' }}/10)</td></tr>
        <tr><td style="padding:6px 0; color:#6B7280;">Kepuasan Umum</td><td style="padding:6px 0; color:#111827;">{{ $survey->overall_satisfaction ? $survey->overall_satisfaction.'/5' : '-' }}</td></tr>
        <tr><td style="padding:6px 0; color:#6B7280;">Tanggal</td><td style="padding:6px 0; color:#111827;">{{ \Carbon\Carbon::parse($survey->response_date)->format('d M Y H:i') }}</td></tr>
    </table>

    @if($survey->needs_improvement)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FEF2F2; border:1px solid #FECACA; border-radius:8px; margin-top:16px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:11px; font-weight:800; color:#991B1B; text-transform:uppercase; margin-bottom:6px;">Yang perlu diperbaiki (kata customer)</div>
                <div style="font-size:14px; color:#374151;">"{{ $survey->needs_improvement }}"</div>
            </td>
        </tr>
    </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $survey->willing_to_contact ? '#F0FDF4' : '#F8FAFC' }}; border:1px solid {{ $survey->willing_to_contact ? '#BBF7D0' : '#E5E9F0' }}; border-radius:8px; margin-top:16px;">
        <tr>
            <td style="padding:14px 16px;">
                @if($survey->willing_to_contact)
                    <div style="font-size:13px; color:#065F46;"><strong>✓ Bersedia dihubungi.</strong> Hubungi segera:</div>
                    <div style="font-size:14px; color:#111827; margin-top:6px;">{{ $survey->contact_email ?: '—' }}{{ $survey->contact_phone ? ' · '.$survey->contact_phone : '' }}</div>
                @else
                    <div style="font-size:13px; color:#6B7280;">Customer tidak mencantumkan kontak. Cek riwayat akun untuk tindak lanjut.</div>
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:26px;">
        <tr>
            <td align="center">
                <a href="{{ $dashboardUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:12px 30px; border-radius:8px;">Buka Dashboard Survey</a>
            </td>
        </tr>
    </table>
@endsection

@section('footerNote', 'Notifikasi otomatis M2B Portal · Prioritas: retensi pelanggan.')
