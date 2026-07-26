@extends('emails.layouts.master')

@section('title', 'Faktur Tagihan — M2B Logistics')

@section('footerNote', 'Ada pertanyaan seputar pembayaran? Balas email ini, tim Finance kami akan membantu.')

@section('content')
    <p style="margin:0 0 4px;">Yth.</p>
    <p style="margin:0 0 4px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $invoice->shipment->customer->company_name }}</p>
    <p style="margin:0 0 20px; font-size:13px; color:#6B7280;">u.p. Finance / Accounting Department</p>

    <p style="margin:0 0 22px; color:#4B5563;">Bersama ini kami sampaikan faktur tagihan (Invoice) terkait jasa pengurusan logistik dengan rincian sebagai berikut:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr>
                        <td style="padding:6px 0; color:#6B7280; width:150px;">Nomor Invoice</td>
                        <td style="padding:6px 0; font-weight:700; color:#111827;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Tanggal</td>
                        <td style="padding:6px 0; font-weight:700; color:#111827;">{{ $invoice->invoice_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Jatuh Tempo</td>
                        <td style="padding:6px 0; font-weight:700; color:#B91C1C;">{{ $invoice->due_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0 0; color:#6B7280; border-top:1px dashed #E5E9F0;">Total Tagihan</td>
                        <td style="padding:10px 0 0; font-weight:800; font-size:17px; color:#0F2C59; border-top:1px dashed #E5E9F0;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px; color:#4B5563;">Dokumen Invoice selengkapnya telah kami lampirkan dalam format <strong>PDF</strong> pada email ini.</p>
    <p style="margin:0 0 24px; color:#4B5563;">Mohon pembayaran dapat dilakukan sebelum tanggal jatuh tempo ke rekening yang tertera pada lampiran.</p>
    <p style="margin:0 0 4px; color:#4B5563;">Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>

    <p style="margin:20px 0 0; color:#4B5563;">Hormat Kami,</p>
    <p style="margin:4px 0 0; font-weight:700; color:#0F2C59;">Finance Dept.<br><span style="font-weight:400; color:#6B7280;">PT. Mora Multi Berkah</span></p>
@endsection
