<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { border-bottom: 2px solid #0F2C59; padding-bottom: 10px; margin-bottom: 20px; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="color: #0F2C59; margin: 0;">PT. MORA MULTI BERKAH</h2>
            <small>Logistic | Solution | Partner</small>
        </div>

        <p>Yth. <strong>{{ $invoice->shipment->customer->company_name }}</strong>,</p>
        <p>u.p. Finance / Accounting Department</p>

        <p>Bersama ini kami sampaikan faktur tagihan (Invoice) terkait jasa pengurusan logistik dengan rincian sebagai berikut:</p>

        <table style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
            <tr>
                <td width="150" style="padding: 5px 0;"><strong>Nomor Invoice</strong></td>
                <td>: {{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td style="padding: 5px 0;"><strong>Tanggal</strong></td>
                <td>: {{ $invoice->invoice_date->format('d F Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 5px 0;"><strong>Jatuh Tempo</strong></td>
                <td>: {{ $invoice->due_date->format('d F Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 5px 0;"><strong>Total Tagihan</strong></td>
                <td style="font-weight: bold; color: #0F2C59;">: Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <p>Dokumen Invoice selengkapnya telah kami lampirkan dalam format <strong>PDF</strong> pada email ini.</p>

        <p>Mohon pembayaran dapat dilakukan sebelum tanggal jatuh tempo ke rekening yang tertera pada lampiran.</p>

        <p>Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>

        <br>
        <p>Hormat Kami,</p>
        <p><strong>Finance Dept.</strong><br>PT. Mora Multi Berkah</p>

        <div class="footer">
            Email ini dikirim secara otomatis oleh Sistem Portal M2B. Silahkan jika ingin membalas email ini. Jika ada pertanyaan seputar pembayaran silakan hubungi kami.
        </div>
    </div>
</body>
</html>