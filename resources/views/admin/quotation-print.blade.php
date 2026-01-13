<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header-table { width: 100%; border-bottom: 3px solid #0F2C59; margin-bottom: 20px; }
        .logo-img { width: 120px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .table-data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-data th { background: #0F2C59; color: white; padding: 8px; border: 1px solid #0F2C59; }
        .table-data td { border: 1px solid #ccc; padding: 6px; }
        .footer-table { width: 100%; margin-top: 20px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 5px; border-bottom: 1px solid #eee; }
        .grand-total { background: #0F2C59; color: white; font-weight: bold; }
        
        /* STYLE KHUSUS NOTES (DIPINDAHKAN KE SINI AGAR RAPI) */
        .notes-section {
            margin-top: 10px; 
            padding-right: 20px;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .notes-content p { margin-bottom: 8px; text-align: justify; }
        .notes-content ul, .notes-content ol { 
            margin-top: 5px; 
            margin-bottom: 10px; 
            padding-left: 20px; 
        }
        .notes-content li { 
            margin-bottom: 4px; 
            text-align: justify;
        }
    </style>
</head>
<body onload="window.print()">
    
    @php
        $logoBase64 = '';
        $path = public_path('images/m2b-logo.png');
        if (file_exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        // Fungsi Terbilang Indonesia
        if (!function_exists('penyebut')) {
            function penyebut($nilai) {
                $nilai = abs($nilai);
                $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
                $temp = "";
                if ($nilai < 12) $temp = " ". $huruf[$nilai];
                else if ($nilai < 20) $temp = penyebut($nilai - 10). " Belas";
                else if ($nilai < 100) $temp = penyebut($nilai/10)." Puluh". penyebut($nilai % 10);
                else if ($nilai < 200) $temp = " Seratus" . penyebut($nilai - 100);
                else if ($nilai < 1000) $temp = penyebut($nilai/100) . " Ratus" . penyebut($nilai % 100);
                else if ($nilai < 1000000) $temp = penyebut($nilai/1000) . " Ribu" . penyebut($nilai % 1000);
                else if ($nilai < 1000000000) $temp = penyebut($nilai/1000000) . " Juta" . penyebut($nilai % 1000000);
                else if ($nilai < 1000000000000) $temp = penyebut($nilai/1000000000) . " Milyar" . penyebut($nilai % 1000000000);
                return $temp;
            }
        }

        // Fungsi Terbilang English
        if (!function_exists('numberToWordsEn')) {
            function numberToWordsEn($number) {
                $number = abs(floor($number));
                if ($number == 0) return 'Zero';
                
                $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 
                         'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
                
                $result = '';
                
                if ($number >= 1000000000) {
                    $result .= numberToWordsEn(floor($number / 1000000000)) . ' Billion ';
                    $number %= 1000000000;
                }
                if ($number >= 1000000) {
                    $result .= numberToWordsEn(floor($number / 1000000)) . ' Million ';
                    $number %= 1000000;
                }
                if ($number >= 1000) {
                    $result .= numberToWordsEn(floor($number / 1000)) . ' Thousand ';
                    $number %= 1000;
                }
                if ($number >= 100) {
                    $result .= numberToWordsEn(floor($number / 100)) . ' Hundred ';
                    $number %= 100;
                }
                if ($number >= 20) {
                    $result .= $tens[floor($number / 10)] . ' ';
                    $number %= 10;
                }
                if ($number > 0) {
                    $result .= $ones[$number] . ' ';
                }
                
                return trim($result);
            }
        }

        // Generate terbilang berdasarkan pilihan bahasa
        $lang = $quotation->terbilang_lang ?? 'id';
        $terbilangID = trim(penyebut($quotation->grand_total)) . " Rupiah";
        $terbilangEN = numberToWordsEn($quotation->grand_total) . " Rupiah";
        
        if ($lang === 'id') {
            $terbilangText = $terbilangID;
        } elseif ($lang === 'en') {
            $terbilangText = $terbilangEN;
        } else {
            // both
            $terbilangText = $terbilangID . "\n" . $terbilangEN;
        }
    @endphp

    <div class="container">
        <table class="header-table">
            <tr>
                <td width="60%">
                    @if($logoBase64) <img src="{{ $logoBase64 }}" class="logo-img"> @else <h2>M2B</h2> @endif
                    <div style="margin-top:10px; color:#0F2C59; font-weight:bold;">PT. MORA MULTI BERKAH</div>
                    <div style="font-size:10px;">Logistic | Solution | Partner</div>
                </td>
                <td width="40%" align="right">
                    <h2 style="color:#0F2C59; margin-bottom:5px;">QUOTATION</h2>
                    <strong>No: {{ $quotation->quotation_number }}</strong><br>
                    Date: {{ $quotation->quotation_date->format('d M Y') }}<br>
                    Valid Until: {{ $quotation->valid_until->format('d M Y') }}
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td style="border:1px solid #eee; padding:10px; width:48%; vertical-align:top;">
                    <strong style="color:#666; font-size:10px;">PREPARED FOR:</strong><br>
                    @if($quotation->customer)
                        <strong>{{ $quotation->customer->company_name }}</strong><br>
                        {{ $quotation->customer->address }}
                    @else
                        <strong>{{ $quotation->manual_company }}</strong><br>
                        UP: {{ $quotation->manual_pic }}<br>
                        Email: {{ $quotation->manual_email }}<br>
                        Phone: {{ $quotation->manual_phone }}
                    @endif
                </td>
                <td width="4%"></td>
                <td style="border:1px solid #eee; padding:10px; width:48%; vertical-align:top;">
                    <strong style="color:#666; font-size:10px;">SERVICE DETAILS:</strong><br>
                    Route: <strong>{{ $quotation->origin }} &rarr; {{ $quotation->destination }}</strong><br>
                    Type: {{ ucfirst($quotation->service_type) }}
                </td>
            </tr>
        </table>

        <table class="table-data">
            <thead>
                <tr><th>Description</th><th width="10%" align="center">Qty</th><th width="20%" align="right">Unit Price</th><th width="20%" align="right">Total</th></tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }} <span style="font-size:9px; color:#999;">({{ ucfirst($item->item_type) }})</span></td>
                    <td align="center">{{ $item->qty + 0 }}</td>
                    <td align="right">{{ number_format($item->price) }}</td>
                    <td align="right">{{ number_format($item->total) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="footer-table">
            <tr>
                <td width="60%" vertical-align="top">
                    <div style="background:#f3f4f6; padding:10px; border-radius:4px; font-style:italic; font-size:11px; margin-bottom: 15px;">
                        {!! nl2br("# " . e($terbilangText) . " #") !!}
                    </div>

                    <div style="margin-top:20px;">
    <strong>-----------------------------------------------------------------</strong>
    <div style="font-size:11px; margin-top:5px; text-align:justify;">
        {!! $quotation->notes !!}
    </div>
</div>

<style>
    /* CSS AGAR LIST DI PDF RAPI */
    ul, ol { margin-left: 20px; padding-left: 5px; margin-top: 5px; }
    li { margin-bottom: 3px; text-align: justify; }
    p { margin-bottom: 8px; text-align: justify; }
</style>
                </td>

                <td width="5%"></td>

                <td width="35%" vertical-align="top">
                    <table class="summary-table">
                        <tr><td>Subtotal Jasa</td><td align="right">{{ number_format($quotation->service_total) }}</td></tr>
                        <tr><td>PPN (VAT)</td><td align="right">{{ number_format($quotation->tax_amount) }}</td></tr>
                        <tr><td>PPH 23</td><td align="right" style="color:red;">({{ number_format($quotation->pph_amount) }})</td></tr>
                        <tr><td>Reimbursement</td><td align="right">{{ number_format($quotation->reimbursement_total) }}</td></tr>
                        <tr class="grand-total"><td style="padding:10px;">GRAND TOTAL</td><td align="right" style="padding:10px;">Rp {{ number_format($quotation->grand_total) }}</td></tr>
                    </table>

                    <div style="margin-top:50px; text-align:right; padding-right:10px;">
                        <p style="font-size:11px;">Best Regards,<br><strong>PT. MORA MULTI BERKAH</strong></p>
                        <br><br><br>
                        <div style="border-bottom:1px solid #000; width:150px; display:inline-block;"></div><br>
                        Sales Department
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>