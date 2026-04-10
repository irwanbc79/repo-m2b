<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->type }} - {{ $invoice->invoice_number }}</title>
    @php
        $logo = public_path('images/m2b-logo.png');
        $sign = public_path('images/assets/signatures/sign_nurul.png');
        $watermark = public_path($invoice->status === 'paid' ? 'images/watermark-lunas.png' : 'images/watermark-belum-lunas.png');
        $cust = $invoice->customer ?? ($invoice->shipment->customer ?? null);
    @endphp
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; position: relative; }
        table { width: 100%; border-collapse: collapse; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-red { color: #dc2626; }
        .text-blue { color: #1e3a8a; }
        .text-green { color: #166534; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* WATERMARK */
        .watermark {
            position: fixed;
            top: 30%;
            left: 10%;
            width: 80%;
            opacity: 0.08;
            z-index: -1;
        }
        
        /* HEADER */
        .header-table { border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 16px; font-weight: bold; color: #1e3a8a; margin: 5px 0; }
        .invoice-title { font-size: 28px; font-weight: 900; color: #cbd5e1; letter-spacing: 2px; }
        .invoice-type { font-size: 11px; font-weight: bold; color: #64748b; letter-spacing: 3px; }
        
        /* BILL TO BOX */
        .bill-box { background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px; }
        .bill-label { font-size: 8px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        
        /* INFO TABLE */
        .info-label { font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .info-value { font-size: 11px; font-weight: bold; }
        
        /* SECTION TITLE */
        .section-title { font-size: 10px; font-weight: bold; border-bottom: 2px solid; padding-bottom: 3px; margin: 15px 0 8px 0; display: inline-block; font-style: italic; }
        .section-service { color: #1e3a8a; border-color: #1e3a8a; }
        .section-reimburse { color: #166534; border-color: #166534; }
        
        /* ITEMS TABLE */
        .items-table th { background: #eff6ff; color: #1e3a8a; padding: 8px 6px; font-size: 9px; text-transform: uppercase; font-weight: bold; border-bottom: 2px solid #1e3a8a; }
        .items-table-green th { background: #f0fdf4; color: #166534; border-color: #166534; }
        .items-table td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .subtotal-row td { border-bottom: none; padding-top: 10px; }
        .subtotal-label { font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .subtotal-value { font-weight: bold; font-style: italic; }
        
        /* TOTAL BOX */
        .total-box { background: #0f172a; color: white; padding: 15px; margin-top: 15px; }
        .total-row { margin-bottom: 8px; }
        .total-label { font-size: 9px; color: #94a3b8; text-transform: uppercase; font-weight: bold; }
        .total-value { font-size: 11px; color: white; font-family: monospace; font-weight: bold; }
        .grand-total { border-top: 1px solid #475569; padding-top: 12px; margin-top: 12px; }
        .grand-label { font-size: 10px; color: #60a5fa; font-weight: bold; }
        .grand-value { font-size: 18px; color: white; font-weight: 900; }
        
        /* TERBILANG */
        .terbilang { background: #f1f5f9; padding: 10px; font-size: 9px; font-style: italic; margin-top: 15px; border-left: 3px solid #1e3a8a; }
        
        /* FOOTER */
        .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .bank-box { background: #eff6ff; padding: 12px; border: 1px solid #bfdbfe; }
        .bank-name { font-weight: bold; color: #1e3a8a; }
        .bank-account { font-family: monospace; font-size: 14px; font-weight: bold; letter-spacing: 1px; }
        /* WATERMARK STAMP */
        .stamp-overlay {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: 60px;
            font-weight: 900;
            letter-spacing: 6px;
            padding: 15px 30px;
            border: 6px solid;
            border-radius: 15px;
            opacity: 0.10;
            z-index: 1000;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .stamp-paid { color: #16a34a; border-color: #16a34a; }
        .stamp-unpaid { color: #dc2626; border-color: #dc2626; }
    </style>
</head>
<body style="position:relative;">

<!-- WATERMARK -->
@if($invoice->status === "paid")
    <div class="stamp-overlay stamp-paid">LUNAS</div>
@else
    <div class="stamp-overlay stamp-unpaid">BELUM LUNAS</div>
@endif

<!-- HEADER -->
<table class="header-table">
    <tr>
        <td width="55%">
            <img src="{{ $logo }}" style="width:100px; height:auto; margin-bottom:8px;"><br>
            <div class="company-name">PT. MORA MULTI BERKAH</div>
            <div style="font-size:9px; color:#475569;">
                <b>LOGISTIC SOLUTION & FREIGHT FORWARDING</b><br>
                Jl. Kapt. Sumarsono Komp. Graha Metropolitan Blok G No. 14<br>
                Medan, Sumatera Utara - Indonesia<br>
                Telp: 061-44020012 | Email: finance@m2b.co.id
            </div>
        </td>
        <td width="45%" class="text-right" style="vertical-align:top;">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-type">{{ strtoupper($invoice->type) }}</div>
        </td>
    </tr>
</table>

<!-- INFO SECTION -->
<table style="margin-bottom:20px;">
    <tr>
        <td width="55%" style="vertical-align:top;">
            <div class="bill-box">
                <div class="bill-label">Tagihan Kepada (Bill To):</div>
                <div style="font-size:12px; font-weight:bold; color:#1e293b; margin:5px 0;">{{ $cust->company_name ?? 'CUSTOMER' }}</div>
                <div style="font-size:9px; color:#475569;">
                    {{ $cust->address ?? '-' }}<br>
                    {{ $cust->city ?? '' }}
                    @if($cust && $cust->npwp)
                        <br><b>NPWP:</b> {{ $cust->npwp }}
                    @endif
                </div>
            </div>
        </td>
        <td width="45%" style="vertical-align:top; padding-left:15px;">
            <table>
                <tr>
                    <td class="info-label">Nomor Invoice</td>
                    <td class="info-value text-right text-blue">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="info-label">Tanggal</td>
                    <td class="info-value text-right">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="info-label">Jatuh Tempo</td>
                    <td class="info-value text-right text-red">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</td>
                </tr>
                @if($invoice->shipment)
                <tr style="border-top:1px dashed #cbd5e1;">
                    <td class="info-label" style="padding-top:5px;">Ref No.</td>
                    <td class="info-value text-right text-blue" style="padding-top:5px;">{{ $invoice->shipment->awb_number }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<!-- I. SERVICES -->
@if($invoice->items->where('item_type', 'service')->count() > 0)
<div class="section-title section-service">I. Jasa Pengurusan (Services)</div>
<table class="items-table">
    <thead>
        <tr>
            <th width="50%" style="text-align:left;">Description</th>
            <th width="10%">Qty</th>
            <th width="20%" style="text-align:right;">Unit Price</th>
            <th width="20%" style="text-align:right;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items->where('item_type', 'service') as $item)
        <tr>
            <td class="font-bold">{{ $item->description }}</td>
            <td class="text-center">{{ $item->qty + 0 }}</td>
            <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
            <td class="text-right font-bold">{{ number_format($item->total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="3" class="text-right subtotal-label">Service Subtotal</td>
            <td class="text-right subtotal-value">{{ number_format($invoice->service_total, 0, ',', '.') }}</td>
        </tr>
        @if($invoice->tax_amount > 0)
        <tr>
            <td colspan="3" class="text-right subtotal-label">VAT / PPN ({{ $invoice->tax_rate }}%)</td>
            <td class="text-right subtotal-value">{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
    </tbody>
</table>
@endif

<!-- II. REIMBURSEMENT -->
@if($invoice->items->where('item_type', 'reimbursement')->count() > 0)
<div class="section-title section-reimburse">II. Dana Talangan (Reimbursement) - Non PPN</div>
<table class="items-table items-table-green">
    <thead>
        <tr>
            <th width="50%" style="text-align:left;">Description</th>
            <th width="10%">Qty</th>
            <th width="20%" style="text-align:right;">Unit Price</th>
            <th width="20%" style="text-align:right;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items->where('item_type', 'reimbursement') as $item)
        <tr>
            <td class="font-bold">{{ $item->description }}</td>
            <td class="text-center">{{ $item->qty + 0 }}</td>
            <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
            <td class="text-right font-bold">{{ number_format($item->total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="3" class="text-right subtotal-label">Reimbursement Total</td>
            <td class="text-right subtotal-value">{{ number_format($invoice->reimbursement_total, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
@endif

<!-- TOTAL BOX -->
<table style="margin-top:20px;">
    <tr>
        <td width="50%"></td>
        <td width="50%">
            <div class="total-box">
                <table>
                    <tr>
                        <td class="total-label">Service Subtotal</td>
                        <td class="total-value text-right">{{ number_format($invoice->service_total, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td class="total-label">PPN ({{ $invoice->tax_rate }}%)</td>
                        <td class="total-value text-right">{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="total-label">Reimbursement (Non PPN)</td>
                        <td class="total-value text-right">{{ number_format($invoice->reimbursement_total, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->pph_amount > 0)
                    <tr>
                        <td class="total-label">PPh 23 ({{ $invoice->pph_rate }}%)</td>
                        <td class="total-value text-right" style="color:#f87171;">-{{ number_format($invoice->pph_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="grand-label">BALANCE DUE</td>
                        <td class="grand-value text-right">IDR {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<!-- TERBILANG -->
<div class="terbilang">
    <b># {{ ucwords((new NumberFormatter("id", NumberFormatter::SPELLOUT))->format($invoice->grand_total)) }} Rupiah #</b>
</div>
<!-- CATATAN PEMBAYARAN -->
@if(!empty($invoice->payment_notes))
<div style="border-left:4px solid #f59e0b; padding:10px 15px; margin:15px 0; background:#fffbeb; font-size:10px;">
    <div style="font-weight:bold; color:#b45309; text-transform:uppercase; font-size:9px; margin-bottom:5px;">📌 Catatan Pembayaran / Payment Notes:</div>
    <div style="color:#475569;">{!! nl2br(e($invoice->payment_notes)) !!}</div>
</div>
@endif

<!-- FOOTER -->
<div class="footer">
    <table>
        <tr>
            <td width="55%" style="vertical-align:top;">
                <div style="font-size:8px; color:#64748b; font-weight:bold; text-transform:uppercase; margin-bottom:8px;">Pembayaran Ditransfer Ke:</div>
                <div class="bank-box">
                    <div class="bank-name">BANK MANDIRI</div>
                    <div style="font-size:9px; color:#475569;">A/n: PT. Mora Multi Berkah</div>
                    <div class="bank-account">106-00-5598-8896</div>
                </div>
            </td>
            <td width="45%" class="text-center" style="vertical-align:top;">
                <div style="font-size:10px; margin-bottom:5px;">Hormat Kami,</div>
                <img src="{{ $sign }}" style="width:100px; margin:10px 0;"><br>
                <div style="font-weight:bold; text-decoration:underline;">Nurul Asyikin</div>
                <div style="font-size:9px; color:#64748b;">Finance Department</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
