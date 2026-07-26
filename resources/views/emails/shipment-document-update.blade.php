@extends('emails.layouts.master')

@php $lang = $shipment->customer->preferred_language ?? 'id'; @endphp

@section('title', __('email.document_update_title', [], $lang))

@section('content')
    <p style="margin:0 0 4px;">{{ __('email.greeting', [], $lang) }}</p>
    <p style="margin:0 0 20px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $shipment->customer->company_name ?? 'Valued Customer' }}</p>

    <p style="margin:0 0 22px; color:#4B5563;">{{ __('email.shipment_status_intro', [], $lang) }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:18px;">
        <tr>
            <td style="padding:14px 18px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#9AA5B4;">
                    {{ __('email.awb_number', [], $lang) }}
                </div>
                <div style="font-family: 'Courier New', Courier, monospace; font-size:18px; font-weight:700; color:#0F2C59; letter-spacing:0.5px; margin-top:2px;">
                    {{ $shipment->awb_number }}
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
        <tr>
            <td align="center" style="background-color:#EFF6FF; border:1px solid #0F2C5922; border-radius:8px; padding:18px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#9AA5B4; margin-bottom:6px;">
                    {{ $statusType }}
                </div>
                <div style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-size:14px; font-weight:800; letter-spacing:0.5px; padding:7px 20px; border-radius:20px;">
                    {{ strtoupper($newStatus) }}
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
        <tr>
            <td style="padding:10px 0; border-bottom:1px solid #E5E9F0; color:#6B7280; width:150px;">🚚 {{ __('email.route', [], $lang) }}</td>
            <td style="padding:10px 0; border-bottom:1px solid #E5E9F0; font-weight:700; color:#111827;">{{ $shipment->origin }} &rarr; {{ $shipment->destination }}</td>
        </tr>
        <tr>
            <td style="padding:10px 0; border-bottom:1px solid #E5E9F0; color:#6B7280;">📦 {{ __('email.service', [], $lang) }}</td>
            <td style="padding:10px 0; border-bottom:1px solid #E5E9F0; font-weight:700; color:#111827; text-transform:capitalize;">{{ $shipment->service_type }} - {{ $shipment->shipment_type }}</td>
        </tr>
        <tr>
            <td style="padding:10px 0; color:#6B7280;">🕐 {{ __('email.update_time', [], $lang) }}</td>
            <td style="padding:10px 0; font-weight:700; color:#111827;">{{ $updateTime }} WIB</td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;">
        <tr>
            <td align="center">
                <a href="{{ route('customer.shipment.show', $shipment->id) }}"
                   style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-size:14px; font-weight:700; letter-spacing:0.3px; padding:14px 36px; border-radius:8px;">
                    🔍 {{ __('email.track_shipment', [], $lang) }}
                </a>
            </td>
        </tr>
    </table>
@endsection
