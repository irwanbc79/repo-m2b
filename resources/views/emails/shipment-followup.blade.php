<!DOCTYPE html>
<html lang="{{ $lang ?? 'id' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('email.followup_title', [], $lang ?? 'id') }}</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6;">
<tr><td align="center" style="padding: 40px 20px;">

<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">

    <!-- HEADER WITH LOGO -->
    <tr>
        <td style="background: #0F2C59; padding: 28px 30px; text-align: center;">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logistics" height="56" style="display: block; margin: 0 auto 10px; max-height: 56px;">
            <div style="font-size: 10px; letter-spacing: 4px; color: #93c5fd; font-weight: 600;">LOGISTIC &nbsp;·&nbsp; SOLUTION &nbsp;·&nbsp; PARTNER</div>
        </td>
    </tr>

    <!-- HERO -->
    <tr>
        <td style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); padding: 36px 30px; text-align: center; border-bottom: 3px solid #bbf7d0;">
            <div style="font-size: 52px; margin-bottom: 12px;">🎉</div>
            <h1 style="font-size: 24px; font-weight: 800; color: #065f46; margin: 0 0 10px;">{{ __('email.followup_hero_title', [], $lang ?? 'id') }}</h1>
            <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0;">{{ __('email.followup_hero_sub', [], $lang ?? 'id') }}</p>
        </td>
    </tr>

    <!-- CONTENT -->
    <tr>
        <td style="padding: 36px 30px; color: #374151; line-height: 1.7;">

            <p style="font-size: 14px; color: #6b7280; margin: 0 0 4px;">{{ __('email.followup_greeting', [], $lang ?? 'id') }}</p>
            <p style="font-size: 17px; font-weight: 700; color: #0F2C59; margin: 0 0 16px;">{{ $customerName }},</p>
            <p style="font-size: 15px; color: #4b5563; margin: 0 0 24px;">{!! __('email.followup_intro', [], $lang ?? 'id') !!}</p>

            <!-- SHIPMENT SUMMARY CARD -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin: 0 0 24px;">
                <tr>
                    <td style="padding: 16px 20px 8px;">
                        <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #6b7280; margin: 0 0 12px;">📦 &nbsp;{{ __('email.followup_card_title', [], $lang ?? 'id') }}</p>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; width: 45%;">{{ __('email.followup_awb', [], $lang ?? 'id') }}</td>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827; font-weight: 700; text-align: right;">{{ $awbNumber }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280;">{{ __('email.followup_origin', [], $lang ?? 'id') }}</td>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827; font-weight: 700; text-align: right;">{{ $origin }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280;">{{ __('email.followup_destination', [], $lang ?? 'id') }}</td>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827; font-weight: 700; text-align: right;">{{ $destination }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #6b7280;">{{ __('email.followup_invoice', [], $lang ?? 'id') }}</td>
                                <td style="padding: 9px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827; font-weight: 700; text-align: right;">{{ $invoiceNumber }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 9px 0; font-size: 14px; color: #6b7280;">{{ __('email.followup_paid_date', [], $lang ?? 'id') }}</td>
                                <td style="padding: 9px 0; font-size: 14px; color: #111827; font-weight: 700; text-align: right;">{{ $paymentDate }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- CTA SECTION -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #f8fafc; border-radius: 10px; margin: 0 0 24px;">
                <tr>
                    <td style="padding: 28px 24px; text-align: center;">
                        <p style="font-size: 17px; font-weight: 700; color: #1f2937; margin: 0 0 8px;">💬 {{ __('email.followup_cta_title', [], $lang ?? 'id') }}</p>
                        <p style="font-size: 14px; color: #6b7280; margin: 0 0 24px; line-height: 1.6;">{{ __('email.followup_cta_sub', [], $lang ?? 'id') }}</p>
                        <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                            <tr>
                                <td align="center" bgcolor="#1e3a8a" style="border-radius: 8px; background-color: #1e3a8a;">
                                    <a href="{{ $surveyUrl }}"
                                       style="display: inline-block; padding: 14px 36px; background-color: #1e3a8a; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px; letter-spacing: 0.3px; mso-padding-alt: 14px 36px; font-family: Helvetica, Arial, sans-serif;">
                                        📋 &nbsp;{{ __('email.followup_btn_survey', [], $lang ?? 'id') }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                        @if(!empty($testimonialUrl))
                        <table cellpadding="0" cellspacing="0" border="0" style="margin: 12px auto 0;">
                            <tr>
                                <td align="center" bgcolor="#ffffff" style="border-radius: 8px; background-color: #ffffff; border: 2px solid #1e3a8a;">
                                    <a href="{{ $testimonialUrl }}"
                                       style="display: inline-block; padding: 13px 36px; background-color: #ffffff; color: #1e3a8a; text-decoration: none; border-radius: 6px; font-weight: 700; font-size: 15px; mso-padding-alt: 13px 36px; font-family: Helvetica, Arial, sans-serif;">
                                        ⭐ &nbsp;{{ __('email.followup_btn_testimoni', [], $lang ?? 'id') }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                        @endif
                    </td>
                </tr>
            </table>

            <p style="font-size: 15px; color: #4b5563; margin: 0 0 16px;">{{ __('email.followup_closing', [], $lang ?? 'id') }}</p>
            <p style="font-size: 15px; color: #4b5563; margin: 0 0 24px;">{{ __('email.followup_bye', [], $lang ?? 'id') }}</p>

            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top: 1px solid #e5e7eb; margin-top: 24px;">
                <tr>
                    <td style="padding-top: 20px;">
                        <p style="font-weight: 700; color: #0F2C59; font-size: 15px; margin: 0 0 4px;">{{ __('email.followup_sig_name', [], $lang ?? 'id') }}</p>
                        <p style="font-size: 13px; color: #6b7280; margin: 0;">{{ __('email.followup_sig_title', [], $lang ?? 'id') }}</p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="background-color: #0F2C59; padding: 24px 30px; text-align: center;">
            <p style="font-size: 13px; font-weight: 700; color: #93c5fd; margin: 0 0 6px;">PT. MORA MULTI BERKAH</p>
            <p style="font-size: 12px; color: #cbd5e1; margin: 0 0 14px; line-height: 1.8;">
                📧 sales@m2b.co.id &nbsp;|&nbsp; 🌐 portal.m2b.co.id
            </p>
            <p style="font-size: 11px; color: #475569; margin: 0; line-height: 1.6;">{{ __('email.followup_footer_note', [], $lang ?? 'id') }}</p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
