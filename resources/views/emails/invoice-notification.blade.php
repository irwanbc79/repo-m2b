@extends('emails.layouts.master')

@section('title', 'Notifikasi Tagihan — M2B Logistics')

@section('footerNote', 'Ada pertanyaan seputar pembayaran? Balas email ini, tim Finance kami akan membantu.')

@section('content')
    {!! nl2br(e($bodyMessage)) !!}

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-left:4px solid #0F2C59; border-radius:0 8px 8px 0; margin:22px 0;">
        <tr>
            <td style="padding:18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr>
                        <td style="padding:6px 0; color:#6B7280; width:170px;">Jenis Dokumen</td>
                        <td style="padding:6px 0; font-weight:700; color:#111827;">{{ strtolower($invoice->type) == 'proforma' ? 'Proforma Invoice' : 'Commercial Invoice' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Nomor Invoice</td>
                        <td style="padding:6px 0; font-weight:700; color:#111827;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Tanggal</td>
                        <td style="padding:6px 0; font-weight:700; color:#111827;">{{ date('d F Y', strtotime($invoice->invoice_date)) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Jatuh Tempo</td>
                        <td style="padding:6px 0; font-weight:700; color:#B91C1C;">{{ date('d F Y', strtotime($invoice->due_date)) }}</td>
                    </tr>
                    @if($invoice->shipment)
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Reff No. (Shipment)</td>
                        <td style="padding:6px 0; font-weight:700; color:#0F2C59;">{{ $invoice->shipment->awb_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:10px 0 0; color:#6B7280; border-top:1px dashed #E5E9F0;">Total Tagihan</td>
                        <td style="padding:10px 0 0; font-weight:800; font-size:17px; color:#0F2C59; border-top:1px dashed #E5E9F0;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; color:#4B5563;">Dokumen Invoice selengkapnya telah kami lampirkan dalam format <strong>PDF</strong> pada email ini.</p>
    <p style="margin:0 0 24px; color:#4B5563;">Mohon agar pembayaran dapat dilakukan sebelum tanggal jatuh tempo.</p>

    <p style="margin:20px 0 0; color:#4B5563;">Hormat Kami,</p>
    <p style="margin:4px 0 0; font-weight:700; color:#0F2C59;">Finance Dept.<br><span style="font-weight:400; color:#6B7280;">PT. Mora Multi Berkah</span></p>
@endsection
