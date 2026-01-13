<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { 
            margin: 10mm; 
            size: A4;
        }
        
        body { 
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; 
            font-size: 10.5px;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        
        /* Premium Blue Header */
        .header { 
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
            color: white;
            padding: 30px 40px;
            margin: -10mm -10mm 0 -10mm;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }
        
        .header-content {
            display: table;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .logo-section {
            display: table-cell;
            vertical-align: middle;
            width: 120px;
        }
        
        .logo-container {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .logo-container img {
            width: 85px;
            height: 85px;
            object-fit: contain;
        }
        
        .company-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 25px;
        }
        
        .company-name { 
            font-size: 26px; 
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .company-tagline {
            font-size: 11px;
            opacity: 0.95;
            letter-spacing: 3px;
            font-weight: 500;
        }
        
        .invoice-badge {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 200px;
        }
        
        .invoice-badge-inner {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .invoice-label {
            font-size: 11px;
            opacity: 0.9;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .invoice-number {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        /* Content Area */
        .content {
            padding: 30px 40px;
        }
        
        /* Info Cards */
        .info-cards {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .info-card {
            display: table-cell;
            width: 50%;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .info-card.left {
            margin-right: 15px;
            border-left: 4px solid #3b82f6;
        }
        
        .info-card.right {
            margin-left: 15px;
            border-left: 4px solid #10b981;
        }
        
        .info-title {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .customer-name {
            font-size: 16px;
            color: #1e40af;
            font-weight: 700;
        }
        
        /* Modern Table */
        .table-container {
            margin: 25px 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
        }
        
        th { 
            padding: 14px 12px; 
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        tbody tr {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s;
        }
        
        tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        tbody tr:hover {
            background: #eff6ff;
        }
        
        td { 
            padding: 14px 12px;
        }
        
        .item-number {
            width: 40px;
            text-align: center;
            font-weight: 600;
            color: #64748b;
        }
        
        .item-description {
            font-weight: 500;
            color: #1e293b;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-section {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-top: 3px solid #3b82f6;
        }
        
        .total-label {
            font-size: 12px;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .total-amount {
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
        }
        
        /* Highlight Boxes */
        .terbilang-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #93c5fd;
            border-radius: 10px;
            padding: 18px 20px;
            margin: 25px 0;
            position: relative;
        }
        
        .terbilang-box::before {
            content: '💬';
            position: absolute;
            left: 20px;
            top: 18px;
            font-size: 20px;
        }
        
        .terbilang-title {
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 6px;
            padding-left: 35px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .terbilang-text {
            color: #1e40af;
            font-style: italic;
            font-size: 13px;
            padding-left: 35px;
            font-weight: 500;
        }
        
        .notes-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #fbbf24;
            border-radius: 10px;
            padding: 18px 20px;
            margin: 20px 0;
        }
        
        .notes-title {
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .notes-text {
            color: #78350f;
            line-height: 1.6;
        }
        
        /* Bank Info Card */
        .bank-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .bank-card-title {
            font-size: 13px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 15px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .bank-card-title::before {
            content: '💳';
            margin-right: 8px;
        }
        
        .bank-grid {
            display: table;
            width: 100%;
        }
        
        .bank-row {
            display: table-row;
        }
        
        .bank-label {
            display: table-cell;
            width: 100px;
            padding: 8px 0;
            font-weight: 600;
            color: #166534;
        }
        
        .bank-value {
            display: table-cell;
            padding: 8px 0;
            color: #166534;
        }
        
        .account-number {
            font-size: 16px;
            font-weight: 700;
            color: #15803d;
            letter-spacing: 1px;
        }
        
        /* Signature Section */
        .signature-area {
            margin-top: 50px;
            padding-top: 20px;
        }
        
        .signature-box {
            float: right;
            text-align: center;
            min-width: 220px;
        }
        
        .signature-location {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .signature-company {
            font-weight: 700;
            color: #1e40af;
            font-size: 13px;
            margin-bottom: 70px;
        }
        
        .signature-line {
            border-top: 2px solid #1e40af;
            padding-top: 10px;
            font-weight: 600;
            color: #1e40af;
            margin-top: 10px;
        }
        
        /* Footer */
        .footer {
            margin-top: 60px;
            padding: 25px 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-top: 3px solid #3b82f6;
            margin-left: -40px;
            margin-right: -40px;
            margin-bottom: -30px;
            text-align: center;
        }
        
        .footer-thank {
            font-size: 11px;
            color: #475569;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .footer-contact {
            font-size: 9px;
            color: #64748b;
        }
        
        .footer-contact a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        /* Print Styles */
        @media print {
            body { margin: 0; }
            .header { page-break-after: avoid; }
            .table-container { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    {{-- Premium Header --}}
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-container">
                    <img src="{{ public_path('images/m2b-logo.png') }}" alt="M2B Logo">
                </div>
            </div>
            <div class="company-info">
                <div class="company-name">PT. MORA MULTI BERKAH</div>
                <div class="company-tagline">LOGISTIC | SOLUTION | PARTNER</div>
            </div>
            <div class="invoice-badge">
                <div class="invoice-badge-inner">
                    <div class="invoice-label">INVOICE NUMBER</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="content">
        {{-- Info Cards --}}
        <div class="info-cards">
            <div class="info-card left">
                <div class="info-title">📋 Kepada Yth:</div>
                <div class="customer-name">{{ $invoice->customer_name }}</div>
            </div>
            <div class="info-card right">
                <div class="info-title">📅 Tanggal Invoice:</div>
                <div class="info-value">{{ $invoice->invoice_date->format('d F Y') }}</div>
                <div class="info-title" style="margin-top: 12px;">💰 Currency:</div>
                <div class="info-value">{{ $invoice->currency }}</div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="5%" class="text-center">NO</th>
                        <th width="48%">KETERANGAN</th>
                        <th width="10%" class="text-center">QTY</th>
                        <th width="18%" class="text-right">HARGA SATUAN</th>
                        <th width="19%" class="text-right">JUMLAH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $index => $item)
                    <tr>
                        <td class="item-number">{{ $index + 1 }}</td>
                        <td class="item-description">{{ $item->description }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">
                            {{ $invoice->currency === 'IDR' ? 'Rp' : '$' }}
                            {{ number_format($item->unit_price, $invoice->currency === 'IDR' ? 0 : 2, ',', '.') }}
                        </td>
                        <td class="text-right">
                            {{ $invoice->currency === 'IDR' ? 'Rp' : '$' }}
                            {{ number_format($item->amount, $invoice->currency === 'IDR' ? 0 : 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                    <tr class="total-section">
                        <td colspan="4" class="text-right total-label">TOTAL INVOICE:</td>
                        <td class="text-right total-amount">
                            {{ $invoice->currency === 'IDR' ? 'Rp' : '$' }}
                            {{ number_format($invoice->total, $invoice->currency === 'IDR' ? 0 : 2, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Terbilang --}}
        <div class="terbilang-box">
            <div class="terbilang-title">Terbilang:</div>
            <div class="terbilang-text">{{ $invoice->terbilang }}</div>
        </div>

        {{-- Notes if any --}}
        @if($invoice->notes)
        <div class="notes-box">
            <div class="notes-title">📝 CATATAN TAMBAHAN:</div>
            <div class="notes-text">{{ $invoice->notes }}</div>
        </div>
        @endif

        {{-- Bank Details --}}
        <div class="bank-card">
            <span class="bank-card-title">Informasi Transfer Pembayaran</span>
            <div class="bank-grid">
                <div class="bank-row">
                    <div class="bank-label">Bank:</div>
                    <div class="bank-value">PT BANK MANDIRI (Persero) Tbk</div>
                </div>
                <div class="bank-row">
                    <div class="bank-label">No. Rekening:</div>
                    <div class="bank-value account-number">106-00-5598809-6</div>
                </div>
                <div class="bank-row">
                    <div class="bank-label">Atas Nama:</div>
                    <div class="bank-value"><strong>PT. MORA MULTI BERKAH</strong></div>
                </div>
            </div>
        </div>

        {{-- Signature --}}
        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-location">Medan, {{ $invoice->invoice_date->format('d F Y') }}</div>
                <div class="signature-company">PT. MORA MULTI BERKAH</div>
                <div class="signature-line">Finance Department</div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-thank">Terima kasih atas kepercayaan Anda menggunakan jasa PT. Mora Multi Berkah</div>
        <div class="footer-contact">
            📧 <a href="mailto:sales@m2b.co.id">sales@m2b.co.id</a> | 
            📱 +62 812 6302 7818 | 
            🌐 <a href="https://www.m2b.co.id">www.m2b.co.id</a>
        </div>
    </div>
</body>
</html>
