<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>

    @php
        $logo = public_path('images/m2b-logo.png');
        $sign = public_path('images/assets/signatures/sign_nurul.png');

        $watermark = public_path(
            $invoice->status === 'paid'
                ? 'images/watermark-lunas.png'
                : 'images/watermark-belum-lunas.png'
        );

        $cust = $invoice->customer ?? ($invoice->shipment->customer ?? null);
    @endphp

    <style>
        <img
    src="{{ $invoice->status === 'paid'
        ? public_path('images/watermark-lunas.png')
        : public_path('images/watermark-belum-lunas.png')
    }}"
    style="
        position: fixed;
        top: 35%;
        left: 15%;
        width: 70%;
        opacity: 0.15;
        z-index: -1000;
    "
>


        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
        }

        table { width: 100%; border-collapse: collapse; }

        /* HEADER */
        .header-table { border-bottom: 2px solid #1e3a8a; margin-bottom: 20px; }
        .logo { width: 150px; }
        .company-name { font-size: 18px; font-weight: bold; color: #1e3a8a; margin: 5px 0; }
        .invoice-title { font-size: 26px; font-weight: bold; color: #e5e7eb; text-align: right; }
        .invoice-number { font-size: 13px; font-weight: bold; color: #1e3a8a; text-align: right; }

        /* INFO */
        .bill-to {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 12px;
        }
        .label { font-size: 9px; font-weight: bold; color: #64748b; }
        .value { font-size: 11px; font-weight: bold; color: #1e293b; }

        .text-right { text-align: right; }
        .text-red { color: #dc2626; }

        /* ITEMS */
        th {
            background: #eff6ff;
            color: #1e3a8a;
            padding: 6px;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 2px solid #1e3a8a;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        /* TOTAL BOX */
        <table width="100%" style="margin-top:15px;">
<tr>
    <td width="55%"></td>
    <td width="45%">
        <table style="background:#0f172a;color:white;padding:12px;">
            <tr><td>Service + Tax</td><td align="right">{{ number_format(...) }}</td></tr>
            <tr><td>Reimbursement</td><td align="right">{{ number_format(...) }}</td></tr>
            <tr style="border-top:1px solid #475569;">
                <td><b>TOTAL BAYAR</b></td>
                <td align="right"><b>Rp {{ number_format($invoice->grand_total,0,',','.') }}</b></td>
            </tr>
        </table>
    </td>
</tr>
</table>

        .total-box td { font-size: 10px; color: #cbd5e1; }
        .total-box .val { text-align: right; font-family: monospace; color: #fff; }
        .grand td {
            border-top: 1px solid #475569;
            padding-top: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
        }

        /* FOOTER */
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            clear: both;
        }
        .bank-box {
            background: #eff6ff;
            padding: 12px;
            border: 1px solid #bfdbfe;
            width: 260px;
        }
    </style>
</head>

<body>

{{-- ================= HEADER ================= --}}
<table class="header-table">
    <tr>
        <td width="60%">
            <img src="{{ $logo }}" style="width:120px; height:auto;"><br>
            <div class="company-name">PT. MORA MULTI BERKAH</div>
            <div style="font-size:10px;">
                <b>Logistic Solution & Freight Forwarding</b><br>
                Jl. Kapt. Sumarsono Komp. Graha Metropolitan Blok G No. 14<br>
                Medan – Indonesia | Telp. (061) 440-200-12
            </div>
        </td>
        <td width="40%" class="text-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">{{ strtoupper($invoice->type) }}</div>
            <div style="font-weight:bold;">{{ $invoice->invoice_number }}</div>
        </td>
    </tr>
</table>

{{-- ================= INFO ================= --}}
<table style="margin-bottom:20px;">
    <tr>
        <td width="55%">
            <div class="bill-to">
                <div class="label">TAGIHAN KEPADA</div>
                <div class="value">{{ $cust->company_name ?? 'CUSTOMER' }}</div>
                <div style="font-size:10px;">
                    {{ $cust->address ?? '-' }}<br>
                    {{ $cust->city ?? 'Indonesia' }}
                    @if($cust && $cust->npwp)
                        <br><b>NPWP:</b> {{ $cust->npwp }}
                    @endif
                </div>
            </div>
        </td>
        <td width="45%">
            <table>
                <tr>
                    <td class="label text-right">Tanggal</td>
                    <td class="value text-right">{{ date('d/m/Y', strtotime($invoice->invoice_date)) }}</td>
                </tr>
                <tr>
                    <td class="label text-right">Jatuh Tempo</td>
                    <td class="value text-right text-red">{{ date('d/m/Y', strtotime($invoice->due_date)) }}</td>
                </tr>
                @if($invoice->shipment)
                <tr>
                    <td class="label text-right">Ref No.</td>
                    <td class="value text-right">{{ $invoice->shipment->awb_number }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- ================= ITEMS ================= --}}
@if($invoice->items->count())
<table>
    <thead>
        <tr>
            <th width="50%">Deskripsi</th>
            <th width="10%">Qty</th>
            <th width="20%" class="text-right">Harga</th>
            <th width="20%" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td class="text-center">{{ $item->qty }}</td>
            <td class="text-right">{{ number_format($item->price,0,',','.') }}</td>
            <td class="text-right">{{ number_format($item->total,0,',','.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ================= TOTAL ================= --}}
<div class="total-box">
    <table>
        <tr><td>Service + Tax</td><td class="val">{{ number_format($invoice->service_total + $invoice->tax_amount,0,',','.') }}</td></tr>
        <tr><td>Reimbursement</td><td class="val">{{ number_format($invoice->reimbursement_total,0,',','.') }}</td></tr>
        @if($invoice->pph_amount > 0)
        <tr><td>PPh 23</td><td class="val">({{ number_format($invoice->pph_amount,0,',','.') }})</td></tr>
        @endif
        @if($invoice->down_payment > 0)
        <tr><td>Down Payment</td><td class="val">- {{ number_format($invoice->down_payment,0,',','.') }}</td></tr>
        @endif
        <tr class="grand">
            <td>TOTAL BAYAR</td>
            <td class="val">Rp {{ number_format($invoice->grand_total,0,',','.') }}</td>
        </tr>
    </table>
</div>

{{-- ================= TERBILANG ================= --}}
<div style="clear:both;margin-top:15px;background:#f1f5f9;padding:8px;font-size:9px;font-style:italic;">
    <b>Terbilang:</b> {{ terbilang_pdf($invoice->grand_total) }}
</div>

{{-- ================= FOOTER ================= --}}
<div class="footer">
    <table>
        <tr>
            <td width="60%">
                <div class="label">PEMBAYARAN DITRANSFER KE</div>
                <div class="bank-box">
                    <b>BANK MANDIRI</b><br>
                    A/n: PT. Mora Multi Berkah<br>
                    <span style="font-family:monospace;font-size:13px;font-weight:bold;">
                        106-00-5598-8896
                    </span>
                </div>
            </td>
            <td width="40%" align="center">
                <div style="margin-bottom:8px;font-weight:bold;">Hormat Kami,</div>
                <img src="{{ $sign }}" style="width:120px;"><br>
                <b style="text-decoration:underline;">Nurul Asyikin</b><br>
                <span style="font-size:10px;">Finance Department</span>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
