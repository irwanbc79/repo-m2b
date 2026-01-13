<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { 
            margin: 15mm; 
            size: A4;
        }
        
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        
        /* Header with blue gradient */
        .header { 
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 25px;
            margin: -15mm -15mm 20px -15mm;
            text-align: center;
            border-bottom: 4px solid #1e3a8a;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #1e40af;
            font-family: 'Arial Black', Arial, sans-serif;
        }
        
        .company-name { 
            font-size: 24px; 
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 11px;
            opacity: 0.9;
            letter-spacing: 2px;
        }
        
        .invoice-title {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 20px;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        
        /* Info boxes */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .info-box.right {
            text-align: right;
        }
        
        .info-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .customer-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }
        
        /* Table styling */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
        }
        
        th { 
            padding: 12px 10px; 
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        
        tbody tr:hover {
            background-color: #f8fafc;
        }
        
        td { 
            padding: 12px 10px;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-row { 
            font-weight: bold;
            background: #f0f9ff;
            border-top: 2px solid #3b82f6;
            font-size: 13px;
        }
        
        /* Terbilang box */
        .terbilang {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
            font-style: italic;
            color: #1e40af;
        }
        
        .terbilang strong {
            font-style: normal;
            display: block;
            margin-bottom: 5px;
            color: #1e3a8a;
        }
        
        /* Notes box */
        .notes-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        /* Bank details */
        .bank-details {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .bank-details strong {
            color: #1e40af;
        }
        
        .bank-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
            display: block;
        }
        
        .bank-info {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .bank-row {
            display: table-row;
        }
        
        .bank-label {
            display: table-cell;
            width: 60px;
            padding: 4px 0;
            font-weight: 600;
            color: #475569;
        }
        
        .bank-value {
            display: table-cell;
            padding: 4px 0;
        }
        
        /* Signature */
        .signature-section {
            margin-top: 50px;
            text-align: right;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        
        .signature-label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .signature-company {
            font-weight: bold;
            color: #1e40af;
            font-size: 12px;
            margin-bottom: 60px;
        }
        
        .signature-line {
            border-top: 2px solid #1e40af;
            padding-top: 8px;
            font-weight: 600;
            color: #1e40af;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>
    {{-- Header with Logo --}}
    <div class="header">
        <div class="logo-section">
            <div class="logo">
                <div class="logo-text">M2B</div>
            </div>
            <div>
                <div class="company-name">PT. MORA MULTI BERKAH</div>
                <div class="company-tagline">LOGISTIC | SOLUTION | PARTNER</div>
            </div>
        </div>
    </div>

    {{-- Invoice Title --}}
    <div class="invoice-title">
        📄 INVOICE TAGIHAN
    </div>

    {{-- Info Grid --}}
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">Kepada Yth:</div>
            <div class="info-value" style="font-size: 14px;">{{ $invoice->customer_name }}</div>
        </div>
        <div class="info-box right">
            <div class="info-label">No Invoice:</div>
            <div class="info-value" style="color: #3b82f6;">{{ $invoice->invoice_number }}</div>
            <div class="info-label" style="margin-top: 10px;">Tanggal:</div>
            <div class="info-value">{{ $invoice->invoice_date->format('d F Y') }}</div>
        </div>
    </div>

    {{-- Items Table --}}
    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="50%">KETERANGAN</th>
                <th width="10%" class="text-center">QTY</th>
                <th width="17%" class="text-right">HARGA</th>
                <th width="18%" class="text-right">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td class="text-center" style="color: #64748b;">{{ $index + 1 }}</td>
                <td><strong>{{ $item->description }}</strong></td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">
                    {{ $invoice->currency === 'IDR' ? 'Rp' : '$' }}
                    {{ number_format($item->unit_price, $invoice->currency === 'IDR' ? 0 : 2) }}
                </td>
                <td class="text-right">
                    {{ $invoice->currency === 'IDR' ? 'Rp' : '$' }}
                    {{ number_format($item->amount, $invoice->currency === 'IDR' ? 0 : 2) }}
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL INVOICE:</td>
                <td class="text-right" style="color: #1e40af; font-size: 15px;">
                    {{ $invoice->currency === 'IDR' ? 'Rp' : '$' }}
                    {{ number_format($invoice->total, $invoice->currency === 'IDR' ? 0 : 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Terbilang --}}
    <div class="terbilang">
        <strong>💬 Terbilang:</strong>
        <em>{{ $invoice->terbilang }}</em>
    </div>

    {{-- Notes if any --}}
    @if($invoice->notes)
    <div class="notes-box">
        <strong>📝 Catatan:</strong><br>
        {{ $invoice->notes }}
    </div>
    @endif

    {{-- Bank Details --}}
    <div class="bank-details">
        <span class="bank-title">💳 INFORMASI TRANSFER PEMBAYARAN</span>
        <div class="bank-info">
            <div class="bank-row">
                <div class="bank-label">Bank:</div>
                <div class="bank-value">PT BANK MANDIRI (Persero) Tbk</div>
            </div>
            <div class="bank-row">
                <div class="bank-label">No. Rek:</div>
                <div class="bank-value"><strong style="font-size: 13px;">106-00-5598809-6</strong></div>
            </div>
            <div class="bank-row">
                <div class="bank-label">A/N:</div>
                <div class="bank-value"><strong>PT. MORA MULTI BERKAH</strong></div>
            </div>
        </div>
    </div>

    {{-- Signature --}}
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-label">Hormat Kami,</div>
            <div class="signature-company">PT. MORA MULTI BERKAH</div>
            <div class="signature-line">Finance Department</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Terima kasih atas kepercayaan Anda menggunakan jasa PT. Mora Multi Berkah</p>
        <p style="margin-top: 5px;">📧 Contact: sales@m2b.co.id | 📱 +62 812 6302 7818 | 🌐 www.m2b.co.id</p>
    </div>
</body>
</html>
