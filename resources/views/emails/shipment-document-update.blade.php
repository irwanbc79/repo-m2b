<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembaruan Status Pengiriman Anda</title>
    <style>
        /* CSS DARI TEMPLATE LAMA (shipment-updated.blade.php) */
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 4px solid #1e3a8a; } /* Biru M2B */
        /* --- PERBAIKAN LOGO SIZE --- */
        .header img { height: 60px; max-width: 300px; width: auto; } /* Dulu 200px, sekarang 300px (150% lebih besar) */
        /* -------------------------- */
        .content { padding: 40px 30px; color: #374151; line-height: 1.6; }
        
        /* STATUS BLOCK BARU (MIRIP GAMBAR BARU BAPAK) */
        .status-box { 
            background-color: #f0f9ff; 
            border-left: 4px solid #1e3a8a; /* Biru M2B */
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 4px; 
        }
        .status-label { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: bold; letter-spacing: 1px; }
        .status-value { font-size: 22px; font-weight: bold; color: #1e3a8a; margin-top: 5px; text-transform: uppercase; }
        
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        .details-table td { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .details-label { color: #6b7280; width: 140px; font-weight: 600; }
        .details-value { color: #111827; font-weight: 700; }
        .button { display: inline-block; padding: 12px 30px; background-color: #1e3a8a; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 30px; font-size: 14px; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{-- Ganti URL Logo agar lebih aman di klien email --}}
            <img src="{{ url('images/m2b-logo.png') }}" alt="PT. Mora Multi Berkah">
        </div>

        <div class="content">
            <p>Yth. <strong>{{ $shipment->customer->company_name ?? 'Pelanggan Yth.' }}</strong>,</p>

            <p>Kami ingin menginformasikan pembaruan terkini mengenai pengiriman Anda.</p>

            <!-- STATUS BOX BARU -->
            <div class="status-box">
                <div class="status-label">{{ $statusType }}</div> 
                {{-- $newStatus bisa berupa Status Shipment atau Nama Dokumen --}}
                <div class="status-value">{{ strtoupper($newStatus) }}</div>
            </div>

            <!-- DETAIL SHIPMENT -->
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
                    <td class="details-value">{{ $updateTime }} WIB</td>
                </tr>
            </table>

            <center>
                {{-- Asumsi route customer.shipment.show ada --}}
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