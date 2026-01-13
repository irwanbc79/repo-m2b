<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Notification</title>
    <style>
        /* RESET & BASE */
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7fa; color: #333333; -webkit-font-smoothing: antialiased; }
        table { border-collapse: collapse; width: 100%; }
        
        /* CONTAINER UTAMA (FRAME) */
        .email-wrapper {
            max-width: 640px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e1e4e8;
        }

        /* HEADER */
        .email-header {
            background-color: #1e3a8a; /* Biru Tua M2B */
            padding: 30px 40px;
            text-align: center;
            color: #ffffff;
            border-bottom: 4px solid #172554;
        }
        .email-header h2 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .email-header .subtitle { margin: 5px 0 0; font-size: 13px; opacity: 0.8; letter-spacing: 2px; }

        /* BODY */
        .email-body { padding: 40px; }
        .email-body p { line-height: 1.6; margin-bottom: 20px; font-size: 15px; color: #475569; }
        
        /* HIGHLIGHT BOX (PENGGANTI TABEL BIASA) */
        .highlight-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #1e3a8a;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .detail-row { padding: 6px 0; font-size: 14px; border-bottom: 1px dashed #e2e8f0; }
        .detail-row:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #64748b; width: 140px; display: inline-block; }
        .value { font-weight: 700; color: #1e293b; }
        .amount-large { color: #1e3a8a; font-size: 18px; font-weight: 900; }

        /* FOOTER */
        .email-footer {
            background-color: #f1f5f9;
            padding: 25px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            line-height: 1.5;
        }
        .footer-brand { font-weight: bold; color: #64748b; font-size: 13px; margin-bottom: 5px; display: block;}
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- HEADER -->
        <div class="email-header">
            <h2>PT. MORA MULTI BERKAH</h2>
            <div class="subtitle">LOGISTIC | SOLUTION | PARTNER</div>
        </div>

        <!-- BODY -->
        <div class="email-body">
            <!-- Ini Bagian Dinamis: Mengambil teks dari inputan Modal Bapak -->
            <!-- Jadi "Yth. PT XXX" dan kata-kata pengantar diatur dari sistem, bukan hardcode disini -->
            {!! nl2br(e($bodyMessage)) !!}

            <!-- Ringkasan Tagihan yang Rapi -->
            <div class="highlight-box">
                <div class="detail-row">
                    <span class="label">Jenis Dokumen</span>
                    <span class="value">: {{ $invoice->type == 'Proforma' ? 'Proforma Invoice' : 'Commercial Invoice' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Nomor Invoice</span>
                    <span class="value">: {{ $invoice->invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Tanggal</span>
                    <span class="value">: {{ date('d F Y', strtotime($invoice->invoice_date)) }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Jatuh Tempo</span>
                    <span class="value" style="color: #dc2626;">: {{ date('d F Y', strtotime($invoice->due_date)) }}</span>
                </div>
                @if($invoice->shipment)
                <div class="detail-row">
                    <span class="label">Reff No. (Shipment)</span>
                    <span class="value" style="color: #2563eb;">: {{ $invoice->shipment->awb_number }}</span>
                </div>
                @endif
                <div class="detail-row" style="margin-top: 10px; border-top: 2px solid #e2e8f0; padding-top: 10px;">
                    <span class="label">Total Tagihan</span>
                    <span class="value amount-large">: Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>

            <p style="font-size: 14px; margin-top: 30px;">
                Dokumen Invoice selengkapnya telah kami lampirkan dalam format <strong>PDF</strong> pada email ini.<br>
                Mohon agar pembayaran dapat dilakukan sebelum tanggal jatuh tempo.
            </p>
            
            <p style="margin-top: 40px; margin-bottom: 0;">Hormat Kami,</p>
            <p style="font-weight: bold; color: #1e3a8a; margin-top: 5px;">Finance Dept - PT. Mora Multi Berkah</p>
        </div>

        <!-- FOOTER -->
        <div class="email-footer">
            <span class="footer-brand">PT. MORA MULTI BERKAH</span>
            Jl. Kapt. Sumarsono Komp. Graha Metropolitan Blok G No. 14, Medan - Indonesia<br>
            Telp: 061-44020012 | Email: finance@m2b.co.id
            <br><br>
            <span style="font-style: italic;">Email ini dikirim secara otomatis oleh Sistem Portal M2B.</span>
        </div>
    </div>
</body>
</html>