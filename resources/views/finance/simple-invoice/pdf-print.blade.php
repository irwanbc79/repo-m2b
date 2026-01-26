<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; line-height: 1.4; color: #000; }
        
        /* Professional Header - Match Commercial Invoice */
        .header {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e40af;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        
        .header-left {
            width: 65%;
            padding-right: 15px;
        }
        
        .header-right {
            width: 35%;
            text-align: right;
        }
        
        /* Logo + Company Info */
        .logo-section {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .logo-box {
            display: table-cell;
            vertical-align: top;
            width: 80px;
        }
        
        .logo-img {
            width: 70px;
            height: 70px;
            padding: 5px;
        }
        
        .logo-img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .company-info {
            display: table-cell;
            vertical-align: top;
            padding-left: 5px;
        }
        
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        
        .company-tagline {
            font-size: 7pt;
            color: #1e40af;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .company-address {
            font-size: 6.5pt;
            color: #333;
            line-height: 1.3;
        }
        
        /* Invoice Title */
        .invoice-title {
            font-size: 20pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 2px;
        }
        
        .invoice-type {
            font-size: 7pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Invoice Details Box */
        .invoice-details {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            margin: 10px 0;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .detail-row {
            display: table;
            width: 100%;
            margin: 2px 0;
        }
        
        .detail-label {
            display: table-cell;
            width: 35%;
            font-size: 7pt;
            color: #666;
            text-transform: uppercase;
            padding-right: 5px;
        }
        
        .detail-value {
            display: table-cell;
            font-size: 8pt;
            font-weight: 600;
            color: #1e40af;
        }
        
        /* Customer Section */
        .customer-section {
            background: #f0f7ff;
            border-left: 3px solid #1e40af;
            padding: 8px 10px;
            margin: 10px 0;
        }
        
        .section-label {
            font-size: 7pt;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .section-value {
            font-size: 10pt;
            font-weight: bold;
            color: #1e40af;
        }
        
        .section-address {
            font-size: 7pt;
            color: #333;
            margin-top: 2px;
            line-height: 1.3;
        }
        
        /* Items Table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
        
        table.items thead {
            background: #1e40af;
            color: white;
        }
        
        table.items th {
            padding: 6px 8px;
            text-align: left;
            font-size: 7pt;
            font-weight: 600;
            text-transform: uppercase;
            border: 1px solid #1e40af;
        }
        
        table.items td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            font-size: 8pt;
        }
        
        table.items tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .total-row {
            background: #e7f3ff;
            font-weight: bold;
        }
        
        .total-row td {
            border-top: 2px solid #1e40af;
        }
        
        /* Info Boxes */
        .info-box {
            border: 2px solid;
            padding: 7px 9px;
            border-radius: 3px;
            margin: 8px 0;
        }
        
        .info-box.blue {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        
        .info-box.green {
            background: #f0fdf4;
            border-color: #86efac;
        }
        
        .info-box.yellow {
            background: #fef3c7;
            border-color: #fbbf24;
        }
        
        .box-label {
            font-size: 6pt;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .box-label.blue { color: #1e40af; }
        .box-label.green { color: #166534; }
        .box-label.yellow { color: #92400e; }
        
        .box-content {
            font-size: 7.5pt;
            line-height: 1.4;
        }
        
        .box-content.blue { color: #1e40af; font-style: italic; }
        .box-content.green { color: #166534; }
        .box-content.yellow { color: #78350f; }
        
        /* Signature */
        .signature-area {
            margin-top: 15px;
            text-align: right;
        }
        
        .sig-box {
            display: inline-block;
            text-align: center;
            min-width: 150px;
        }
        
        .sig-location {
            font-size: 7pt;
            color: #666;
            margin-bottom: 2px;
        }
        
        .sig-company {
            font-size: 8pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .sig-image {
            height: 55px;
            margin: 5px 0;
        }
        
        .sig-image img {
            max-width: 130px;
            max-height: 50px;
        }
        
        .sig-line {
            border-top: 2px solid #1e40af;
            padding-top: 4px;
            font-size: 7pt;
            font-weight: 600;
            color: #1e40af;
        }
        
        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 6pt;
            color: #666;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    {{-- Professional Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="logo-section">
                        <div class="logo-box">
                            <div class="logo-img">
                                @if(file_exists(public_path('images/m2b-logo.png')))
                                <img src="{{ public_path('images/m2b-logo.png') }}" alt="M2B">
                                @endif
                            </div>
                        </div>
                        <div class="company-info">
                            <div class="company-name">PT. MORA MULTI BERKAH</div>
                            <div class="company-tagline">LOGISTIC SOLUTION & FREIGHT FORWARDING</div>
                            <div class="company-address">
                                Jl. Kapt. Sumarsono Komp. Graha Metropolitan Blok G No. 14<br>
                                Medan, Sumatera Utara - Indonesia<br>
                                Telp: 061-44020012 | Email: finance@m2b.co.id
                            </div>
                        </div>
                    </div>
                </td>
                <td class="header-right">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-type">COMMERCIAL</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Invoice Details --}}
    <div class="invoice-details">
        <table class="detail-table">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="detail-row">
                        <div class="detail-label">Nomor Invoice</div>
                        <div class="detail-value">{{ $invoice->invoice_number }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Tanggal</div>
                        <div class="detail-value">{{ $invoice->invoice_date->format('d/m/Y') }}</div>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 15px;">
                    <div class="detail-row">
                        <div class="detail-label">Jatuh Tempo</div>
                        <div class="detail-value">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : $invoice->invoice_date->addDays(7)->format('d/m/Y') }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Currency</div>
                        <div class="detail-value">IDR</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Customer Info --}}
    <div class="customer-section">
        <div class="section-label">Tagihan Kepada (Bill To):</div>
        <div class="section-value">{{ $invoice->customer_name }}</div>
        @if($invoice->customer_address)
        <div class="section-address">{{ $invoice->customer_address }}</div>
        @endif
    </div>

    {{-- Items Table --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">NO</th>
                <th style="width: 50%;">KETERANGAN</th>
                <th style="width: 10%;" class="text-center">QTY</th>
                <th style="width: 17%;" class="text-right">HARGA</th>
                <th style="width: 18%;" class="text-right">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $item->description }}</strong></td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right" style="font-size: 9pt;">TOTAL INVOICE:</td>
                <td class="text-right" style="font-size: 10pt; color: #1e40af;">
                    <strong>Rp {{ number_format($invoice->total, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Terbilang --}}
    <div class="info-box blue">
        <div class="box-label blue">💬 Terbilang:</div>
        <div class="box-content blue">{{ $invoice->terbilang }}</div>
    </div>

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="info-box yellow">
        <div class="box-label yellow">📝 Catatan:</div>
        <div class="box-content yellow">{{ $invoice->notes }}</div>
    </div>
    @endif

    {{-- Bank Info --}}
    <div class="info-box green">
        <div class="box-label green">💳 Informasi Transfer</div>
        <div class="box-content green">
            <strong>Bank:</strong> PT BANK MANDIRI (Persero) Tbk<br>
            <strong>No. Rekening:</strong> <span style="font-weight: bold;">106-00-5598809-6</span><br>
            <strong>Atas Nama:</strong> <strong>PT. MORA MULTI BERKAH</strong>
        </div>
    </div>

    {{-- Signature --}}
    <div class="signature-area">
        <div class="sig-box">
            <div class="sig-location">Medan, {{ $invoice->invoice_date->format('d F Y') }}</div>
            <div class="sig-company">PT. MORA MULTI BERKAH</div>
            <div class="sig-image">
                @if(file_exists(public_path('images/assets/signatures/sign_nurul.png')))
                <img src="{{ public_path('images/assets/signatures/sign_nurul.png') }}" alt="Signature">
                @endif
            </div>
            <div class="sig-line">Finance Department</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Terima kasih atas kepercayaan Anda 🙏<br>
        📧 sales@m2b.co.id | 📱 +62 812 6302 7818 | 🌐 www.m2b.co.id
    </div>
</body>
</html>
