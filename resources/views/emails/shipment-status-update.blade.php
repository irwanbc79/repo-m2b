@extends('emails.layouts.master')

@section('title', __('email.shipment_status_title', [], $lang ?? 'id'))

@php
    $statusText = strtoupper($status);
    $statusLower = strtolower($status);
    if (str_contains($statusLower, 'selesai') || str_contains($statusLower, 'complet') || str_contains($statusLower, 'deliver')) {
        $statusColor = '#16A34A'; $statusBg = '#F0FDF4';
    } elseif (str_contains($statusLower, 'batal') || str_contains($statusLower, 'cancel')) {
        $statusColor = '#B91C1C'; $statusBg = '#FEF2F2';
    } else {
        $statusColor = '#0F2C59'; $statusBg = '#EFF6FF';
    }
@endphp

@section('content')
    <p style="margin:0 0 4px;">{{ __('email.greeting', [], $lang ?? 'id') }}</p>
    <p style="margin:0 0 20px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $customerName }}</p>

    <p style="margin:0 0 22px; color:#4B5563;">{{ __('email.shipment_status_intro', [], $lang ?? 'id') }}</p>

    {{-- Dokumen referensi (AWB) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:18px;">
        <tr>
            <td style="padding:14px 18px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#9AA5B4;">
                    {{ __('email.awb_number', [], $lang ?? 'id') }}
                </div>
                <div style="font-family: 'Courier New', Courier, monospace; font-size:18px; font-weight:700; color:#0F2C59; letter-spacing:0.5px; margin-top:2px;">
                    {{ $awbNumber }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Status badge --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
        <tr>
            <td align="center" style="background-color:{{ $statusBg }}; border:1px solid {{ $statusColor }}22; border-radius:8px; padding:18px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#9AA5B4; margin-bottom:6px;">
                    {{ __('email.current_status', [], $lang ?? 'id') }}
                </div>
                <div style="display:inline-block; background-color:{{ $statusColor }}; color:#ffffff; font-size:14px; font-weight:800; letter-spacing:0.5px; padding:7px 20px; border-radius:20px;">
                    {{ $statusText }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Rute --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
        <tr>
            <td width="45%" style="text-align:left;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:#9AA5B4;">{{ __('email.origin', [], $lang ?? 'id') }}</div>
                <div style="font-size:14px; font-weight:700; color:#111827; margin-top:2px;">{{ $origin }}</div>
            </td>
            <td width="10%" style="text-align:center; color:#B7C0CC; font-size:16px;">&rarr;</td>
            <td width="45%" style="text-align:right;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:#9AA5B4;">{{ __('email.destination', [], $lang ?? 'id') }}</div>
                <div style="font-size:14px; font-weight:700; color:#111827; margin-top:2px;">{{ $destination }}</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 4px; border-top:1px solid #E5E9F0;">
        <tr><td style="padding-top:14px; font-size:13px; color:#4B5563;">
            📌 <strong style="color:#111827;">{{ __('email.current_location', [], $lang ?? 'id') }}:</strong> {{ $location }}
        </td></tr>
    </table>

    @if($notes)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border-left:4px solid #F59E0B; border-radius:0 6px 6px 0; margin-top:18px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:11px; font-weight:800; color:#92400E; margin-bottom:3px;">📝 {{ __('email.notes', [], $lang ?? 'id') }}</div>
                <div style="font-size:13px; color:#78350F;">{{ $notes }}</div>
            </td>
        </tr>
    </table>
    @endif

    {{-- CTA --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;">
        <tr>
            <td align="center">
                <a href="{{ $trackingUrl }}" class="m2b-btn"
                   style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-size:14px; font-weight:700; letter-spacing:0.3px; padding:14px 36px; border-radius:8px;">
                    🔍 {{ __('email.track_shipment', [], $lang ?? 'id') }}
                </a>
            </td>
        </tr>
    </table>
@endsection
