<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 20px; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px;
            line-height: 1.4;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .logo { 
            max-width: 120px; 
            margin-bottom: 10px;
        }
        .company-name { 
            font-size: 20px; 
            font-weight: bold;
            margin: 10px 0;
        }
        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 10px;
        }
        .invoice-info { 
            text-align: right; 
            margin-bottom: 20px;
        }
        .invoice-info div {
            margin-bottom: 5px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
        }
        th { 
            background-color: #f0f0f0; 
            padding: 10px; 
            text-align: left;
            border: 1px solid #000;
            font-weight: bold;
        }
        td { 
            padding: 8px; 
            border: 1px solid #000;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { 
            font-weight: bold; 
            background-color: #f9f9f9;
        }
        .terbilang {
            margin: 20px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #3b82f6;
            font-style: italic;
        }
        .bank-details {
            margin: 30px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #fafafa;
        }
        .bank-details strong {
            display: block;
            margin-bottom: 5px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        .signature-line {
            margin-top: 80px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        @if(file_exists(public_path('images/m2b-logo.png')))
        <img src="{{ public_path('images/m2b-logo.png') }}" class="logo" alt="M2B Logo">
        @endif
        <div class="company-name">PT. MORA MULTI BERKAH</div>
        <div class="invoice-title">INVOICE TAGIHAN</div>
    </div>

    {{-- Invoice Info --}}
    <div class="invoice-info">
        <div><strong>No Invoice:</strong> {{ $invoice->invoice_number }}</div>
        <div><strong>Tanggal:</strong> {{ $invoice->invoice_date->format('d F Y') }}</div>
    </div>

    {{-- Customer Info --}}
    <div style="margin-bottom: 30px;">
        <div style="margin-bottom: 10px;"><strong>Kepada Yth:</strong></div>
        <div style="padding-left: 20px;">{{ $invoice->customer_name }}</div>
    </div>

    {{-- Items Table --}}
    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">NO</th>
                <th width="45%">KETERANGAN</th>
                <th width="10%" class="text-center">QTY</th>
                <th width="20%" class="text-right">HARGA</th>
                <th width="20%" class="text-right">JUMLAH ({{ $invoice->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">
                    {{ $invoice->currency === 'IDR' ? 'Rp ' : '$ ' }}
                    {{ number_format($item->unit_price, $invoice->currency === 'IDR' ? 0 : 2) }}
                </td>
                <td class="text-right">
                    {{ $invoice->currency === 'IDR' ? 'Rp ' : '$ ' }}
                    {{ number_format($item->amount, $invoice->currency === 'IDR' ? 0 : 2) }}
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right">
                    <strong>
                        {{ $invoice->currency === 'IDR' ? 'Rp ' : '$ ' }}
                        {{ number_format($invoice->total, $invoice->currency === 'IDR' ? 0 : 2) }}
                    </strong>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Terbilang --}}
    <div class="terbilang">
        <strong>Terbilang:</strong> {{ $invoice->terbilang }}
    </div>

    @if($invoice->notes)
    <div style="margin: 20px 0; padding: 10px; border-left: 3px solid #f59e0b;">
        <strong>Catatan:</strong><br>
        {{ $invoice->notes }}
    </div>
    @endif

    {{-- Bank Details --}}
    <div class="bank-details">
        <strong>*) TRANSFER TO:</strong><br>
        <strong>AN:</strong> PT. MORA MULTI BERKAH<br>
        <strong>No:</strong> 106-00-5598809-6<br>
        <strong>Bank:</strong> PT BANK MANDIRI (Persero) Tbk
    </div>

    {{-- Signature --}}
    <div class="signature">
        <div class="signature-box">
            <div style="margin-bottom: 10px;">Hormat Kami,</div>
            <div style="font-weight: bold;">PT. MORA MULTI BERKAH</div>
            <div class="signature-line">
                Finance
            </div>
        </div>
    </div>
</body>
</html>
