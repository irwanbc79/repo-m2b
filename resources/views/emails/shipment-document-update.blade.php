<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('email.document_update_title', [], $lang ?? 'id') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #1e3a8a; padding: 30px; text-align: center; border-bottom: 4px solid #172554; }
        .header h2 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #ffffff; }
        .header .subtitle { margin: 8px 0 0; font-size: 11px; letter-spacing: 3px; color: #dc2626; font-weight: 600; }
        .content { padding: 40px 30px; color: #374151; line-height: 1.6; }
        .status-box { background-color: #f0f9ff; border-left: 4px solid #1e3a8a; padding: 15px; margin: 20px 0; border-radius: 4px; text-align: center; }
        .status-label { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: bold; letter-spacing: 1px; }
        .status-value { font-size: 22px; font-weight: bold; color: #1e3a8a; margin-top: 5px; text-transform: uppercase; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        .details-table td { padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
        .details-label { color: #6b7280; width: 140px; font-weight: 600; }
        .details-value { color: #111827; font-weight: 700; }
        .button { display: inline-block; padding: 14px 35px; background-color: #1e3a8a; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 25px; font-size: 14px; }
        .footer { background-color: #f9fafb; padding: 25px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; line-height: 1.6; }
        .footer-brand { font-weight: bold; color: #64748b; }
    </style>
</head>
<body>
    @php $lang = $shipment->customer->preferred_language ?? 'id'; @endphp
    <div class="container">
        <!-- HEADER CORPORATE -->
        <div class="header">
            <h2>PT. MORA MULTI BERKAH</h2>
            <div class="subtitle">LOGISTIC | SOLUTION | PARTNER</div>
        </div>

        <!-- CONTENT -->
        <div class="content">
            <p>{{ __('email.greeting', [], $lang) }} <strong>{{ $shipment->customer->company_name ?? 'Valued Customer' }}</strong>,</p>
            <p>{{ __('email.shipment_status_intro', [], $lang) }}</p>

            <!-- STATUS BOX -->
            <div class="status-box">
                <div class="status-label">{{ $statusType }}</div>
                <div class="status-value">{{ strtoupper($newStatus) }}</div>
            </div>

            <!-- DETAIL SHIPMENT -->
            <table class="details-table">
                <tr>
                    <td class="details-label">📋 {{ __('email.awb_number', [], $lang) }}</td>
                    <td class="details-value">{{ $shipment->awb_number }}</td>
                </tr>
                <tr>
                    <td class="details-label">🚚 {{ __('email.route', [], $lang) }}</td>
                    <td class="details-value">{{ $shipment->origin }} → {{ $shipment->destination }}</td>
                </tr>
                <tr>
                    <td class="details-label">📦 {{ __('email.service', [], $lang) }}</td>
                    <td class="details-value" style="text-transform: capitalize;">{{ $shipment->service_type }} - {{ $shipment->shipment_type }}</td>
                </tr>
                <tr>
                    <td class="details-label">🕐 {{ __('email.update_time', [], $lang) }}</td>
                    <td class="details-value">{{ $updateTime }} WIB</td>
                </tr>
            </table>

            <!-- CTA BUTTON -->
            <center>
                <a href="{{ route('customer.shipment.show', $shipment->id) }}" class="button">🔍 {{ __('email.track_shipment', [], $lang) }}</a>
            </center>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <span class="footer-brand">{{ __('email.footer_company', [], $lang) }}</span><br>
            {{ __('email.footer_tagline', [], $lang) }}<br>
            📧 sales@m2b.co.id | 🌐 portal.m2b.co.id
        </div>
    </div>
</body>
</html>
