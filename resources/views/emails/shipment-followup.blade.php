@extends('emails.layouts.master')

@section('title', __('email.followup_title', [], $lang ?? 'id'))

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%); border:1px solid #bbf7d0; border-radius:10px; margin-bottom:24px;">
        <tr>
            <td style="padding:28px; text-align:center;">
                <div style="font-size:44px; margin-bottom:10px;">🎉</div>
                <div style="font-size:20px; font-weight:800; color:#065f46; margin-bottom:8px;">{{ __('email.followup_hero_title', [], $lang ?? 'id') }}</div>
                <div style="font-size:14px; color:#374151;">{{ __('email.followup_hero_sub', [], $lang ?? 'id') }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; font-size:13px; color:#6B7280;">{{ __('email.followup_greeting', [], $lang ?? 'id') }}</p>
    <p style="margin:0 0 18px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $customerName }}</p>
    <p style="margin:0 0 24px; color:#4B5563; line-height:1.7;">{!! __('email.followup_intro', [], $lang ?? 'id') !!}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:16px 20px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#9AA5B4; margin-bottom:10px;">📦 {{ __('email.followup_card_title', [], $lang ?? 'id') }}</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; color:#6B7280;">{{ __('email.followup_awb', [], $lang ?? 'id') }}</td>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; font-weight:700; color:#111827; text-align:right;">{{ $awbNumber }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; color:#6B7280;">{{ __('email.followup_origin', [], $lang ?? 'id') }}</td>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; font-weight:700; color:#111827; text-align:right;">{{ $origin }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; color:#6B7280;">{{ __('email.followup_destination', [], $lang ?? 'id') }}</td>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; font-weight:700; color:#111827; text-align:right;">{{ $destination }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; color:#6B7280;">{{ __('email.followup_invoice', [], $lang ?? 'id') }}</td>
                        <td style="padding:8px 0; border-bottom:1px solid #E5E9F0; font-weight:700; color:#111827; text-align:right;">{{ $invoiceNumber }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; color:#6B7280;">{{ __('email.followup_paid_date', [], $lang ?? 'id') }}</td>
                        <td style="padding:8px 0; font-weight:700; color:#111827; text-align:right;">{{ $paymentDate }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border-radius:10px; margin-bottom:24px;">
        <tr>
            <td style="padding:26px 24px; text-align:center;">
                <p style="margin:0 0 8px; font-size:16px; font-weight:700; color:#1F2937;">💬 {{ __('email.followup_cta_title', [], $lang ?? 'id') }}</p>
                <p style="margin:0 0 22px; font-size:13px; color:#6B7280;">{{ __('email.followup_cta_sub', [], $lang ?? 'id') }}</p>
                <a href="{{ $surveyUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:13px 32px; border-radius:8px; margin-bottom:10px;">📋 {{ __('email.followup_btn_survey', [], $lang ?? 'id') }}</a>
                @if(!empty($testimonialUrl))
                <br>
                <a href="{{ $testimonialUrl }}" style="display:inline-block; background-color:#ffffff; color:#0F2C59; font-weight:700; font-size:14px; padding:12px 32px; border-radius:8px; border:2px solid #0F2C59;">⭐ {{ __('email.followup_btn_testimoni', [], $lang ?? 'id') }}</a>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px; color:#4B5563;">{{ __('email.followup_closing', [], $lang ?? 'id') }}</p>
    <p style="margin:0 0 20px; color:#4B5563;">{{ __('email.followup_bye', [], $lang ?? 'id') }}</p>

    <p style="margin:0; font-weight:700; color:#0F2C59;">{{ __('email.followup_sig_name', [], $lang ?? 'id') }}<br><span style="font-weight:400; color:#6B7280; font-size:13px;">{{ __('email.followup_sig_title', [], $lang ?? 'id') }}</span></p>
@endsection

@section('footerNote', __('email.followup_footer_note', [], $lang ?? 'id'))
