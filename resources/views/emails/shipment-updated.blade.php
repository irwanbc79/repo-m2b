<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 4px solid #B91C1C; }
        .header img { height: 60px; width: auto; }
        .content { padding: 40px 30px; color: #374151; line-height: 1.6; }
        .status-box { background-color: #f0f9ff; border-left: 4px solid #0F2C59; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .status-label { font-size: 12px; text-transform: uppercase; color: #6b7280; font-weight: bold; letter-spacing: 1px; }
        .status-value { font-size: 24px; font-weight: bold; color: #0F2C59; margin-top: 5px; text-transform: uppercase; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        .details-table td { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .details-label { color: #6b7280; width: 140px; }
        .details-value { color: #111827; font-weight: 600; }
        .button { display: inline-block; padding: 12px 30px; background-color: #0F2C59; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 30px; font-size: 14px; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://portal.m2b.co.id/images/m2b-logo.png" alt="M2B Portal">
        </div>

        <div class="content">
            <p>Yth. <strong>{{ $shipment->customer->company_name }}</strong>,</p>

            <p>Kami ingin menginformasikan pembaruan terkini mengenai pengiriman Anda.</p>

            <div class="status-box">
                <div class="status-label">Status Terbaru</div>
                <div class="status-value">{{ str_replace('_', ' ', $shipment->status) }}</div>
            </div>

            <table class="details-table">
                <tr>
                    <td class="details-label">Reference No</td>
                    <td class="details-value">{{ $shipment->awb_number }}</td>
                </tr>
                <tr>
                    <td class="details-label">Rute</td>
                    <td class="details-value">{{ $shipment->origin }} &rarr; {{ $shipment->destination }}</td>
                </tr>
                <tr>
                    <td class="details-label">Layanan</td>
                    <td class="details-value" style="text-transform: capitalize;">{{ $shipment->service_type }} - {{ $shipment->shipment_type }}</td>
                </tr>
                <tr>
                    <td class="details-label">Waktu Update</td>
                    <td class="details-value">{{ now()->format('d M Y, H:i') }} WIB</td>
                </tr>
            </table>

            <center>
                <a href="{{ route('customer.shipment.show', $shipment->id) }}" class="button">Lacak Pengiriman</a>
            </center>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} PT. Mora Multi Berkah.<br>
            Logistic | Solution | Partner
        </div>
    </div>
</body>
</html>