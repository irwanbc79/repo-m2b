@extends('emails.layouts.master')

@section('title', 'Survey Kepuasan Baru Masuk')

@section('content')
    <p style="margin:0 0 6px; font-size:19px; font-weight:800; color:#0F2C59;">📋 Survey Baru Masuk</p>
    <p style="margin:0 0 22px; color:#6B7280; font-size:13px;">{{ $survey->response_date?->format('d F Y, H:i') ?? now()->format('d F Y, H:i') }} WIB</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:16px;">
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #E5E9F0; width:50%;">
                <span style="font-size:11px; color:#6B7280; display:block; margin-bottom:2px;">Perusahaan</span>
                <strong style="font-size:14px; color:#111827;">{{ $survey->is_anonymous ? '(Anonim)' : ($survey->company_name ?? '-') }}</strong>
            </td>
            <td style="padding:14px 16px; border-bottom:1px solid #E5E9F0; width:50%;">
                <span style="font-size:11px; color:#6B7280; display:block; margin-bottom:2px;">Posisi</span>
                <strong style="font-size:14px; color:#111827;">{{ ucfirst($survey->respondent_position ?? '-') }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <span style="font-size:11px; color:#6B7280; display:block; margin-bottom:2px;">Kepuasan Keseluruhan</span>
                <strong style="font-size:20px; color:#16A34A;">{{ $survey->overall_satisfaction ?? '-' }}/5 ⭐</strong>
            </td>
            <td style="padding:14px 16px;">
                <span style="font-size:11px; color:#6B7280; display:block; margin-bottom:2px;">NPS Score</span>
                @php
                    $npsScore = $survey->nps_score ?? 0;
                    $npsColor = $npsScore >= 9 ? '#16A34A' : ($npsScore >= 7 ? '#D97706' : '#B91C1C');
                @endphp
                <strong style="font-size:20px; color:{{ $npsColor }};">{{ $survey->nps_score ?? '-' }}/10</strong>
            </td>
        </tr>
    </table>

    @if($survey->appreciate_most)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F0FDF4; border:1px solid #BBF7D0; border-radius:8px; margin-bottom:12px;">
        <tr><td style="padding:12px 16px;">
            <span style="font-size:11px; color:#065F46; font-weight:700;">Yang paling diapresiasi</span>
            <p style="margin:4px 0 0; font-size:13px; color:#374151; font-style:italic;">"{{ $survey->appreciate_most }}"</p>
        </td></tr>
    </table>
    @endif

    @if($survey->needs_improvement)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border:1px solid #FDE68A; border-radius:8px; margin-bottom:20px;">
        <tr><td style="padding:12px 16px;">
            <span style="font-size:11px; color:#92400E; font-weight:700;">Yang perlu ditingkatkan</span>
            <p style="margin:4px 0 0; font-size:13px; color:#374151; font-style:italic;">"{{ $survey->needs_improvement }}"</p>
        </td></tr>
    </table>
    @endif

    @if($survey->willing_to_contact && $survey->contact_email)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; margin-bottom:20px;">
        <tr><td style="padding:12px 16px;">
            <strong style="color:#1D4ED8; font-size:13px;">📞 Bersedia dihubungi</strong><br>
            <span style="font-size:13px; color:#374151;">Email: {{ $survey->contact_email }}@if($survey->contact_phone) | HP: {{ $survey->contact_phone }}@endif</span>
        </td></tr>
    </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ $dashboardUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:12px 28px; border-radius:8px;">Lihat Semua Survey di Dashboard</a>
            </td>
        </tr>
    </table>
@endsection

@section('footerNote', 'Notifikasi otomatis M2B Portal.')
