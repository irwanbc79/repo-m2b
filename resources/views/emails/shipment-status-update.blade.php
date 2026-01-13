<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembaruan Status Pengiriman</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1a56db;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #1a56db;
            margin: 0;
            font-size: 24px;
        }
        .header .logo {
            font-size: 32px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            background-color: #1a56db;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: 600;
            color: #555;
            width: 40%;
        }
        .info-table td:last-child {
            color: #333;
        }
        .btn {
            display: inline-block;
            background-color: #1a56db;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #1e40af;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 12px;
        }
        .notes {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #1a56db;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📦 M2B Portal</div>
            <h1>Pembaruan Status Pengiriman</h1>
        </div>
        
        <p>Yth. <strong>{{ $customerName }}</strong>,</p>
        
        <p>Berikut adalah pembaruan status pengiriman Anda:</p>
        
        <div style="text-align: center; margin: 25px 0;">
            <span class="status-badge">{{ $status }}</span>
        </div>
        
        <table class="info-table">
            <tr>
                <td>📋 No. AWB</td>
                <td><strong>{{ $awbNumber }}</strong></td>
            </tr>
            <tr>
                <td>📍 Asal</td>
                <td>{{ $origin }}</td>
            </tr>
            <tr>
                <td>🎯 Tujuan</td>
                <td>{{ $destination }}</td>
            </tr>
            <tr>
                <td>📌 Lokasi Saat Ini</td>
                <td>{{ $location }}</td>
            </tr>
        </table>
        
        @if($notes)
        <div class="notes">
            <strong>📝 Catatan:</strong><br>
            {{ $notes }}
        </div>
        @endif
        
        <div style="text-align: center;">
            <a href="{{ $trackingUrl }}" class="btn">🔍 Lacak Pengiriman</a>
        </div>
        
        <div class="footer">
            <p>Email ini dikirim secara otomatis dari <strong>M2B Portal</strong></p>
            <p>PT. Mora Multi Berkah | Jasa Pengurusan Kepabeanan</p>
            <p>📧 info@m2b.co.id | 🌐 portal.m2b.co.id</p>
        </div>
    </div>
</body>
</html>
