@extends('emails.layouts.master')

@section('title', 'Follow-up M2B Logistics')

@section('content')
    <p style="margin:0 0 4px;">Halo Kak</p>
    <p style="margin:0 0 18px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $lead->name }}</p>

    <p style="margin:0 0 14px; color:#4B5563;">Terima kasih sudah mendaftar di Portal M2B Logistics. Kami ingin memastikan kebutuhan Anda terbantu dengan tepat.</p>
    <p style="margin:0 0 14px; color:#4B5563;">Boleh dibantu informasikan rencana Anda? Beberapa hal yang membantu kami menyiapkan penawaran:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr><td style="padding:5px 0; font-size:14px; color:#374151;">• Kebutuhan layanan: <strong>Impor / Ekspor / Pengiriman Domestik</strong></td></tr>
        <tr><td style="padding:5px 0; font-size:14px; color:#374151;">• Jenis barang / komoditas</td></tr>
        <tr><td style="padding:5px 0; font-size:14px; color:#374151;">• Rute asal → tujuan</td></tr>
        <tr><td style="padding:5px 0; font-size:14px; color:#374151;">• Nomor WhatsApp aktif agar tim kami mudah menghubungi</td></tr>
    </table>

    <p style="margin:0 0 20px; color:#4B5563;">Anda juga dapat masuk ke portal dan melengkapi profil perusahaan Anda:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td align="center">
                <a href="{{ $loginUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:12px 26px; border-radius:8px;">Masuk ke Portal M2B</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px; color:#4B5563;">Cukup balas email ini dengan detail rencana Anda, tim Sales kami akan segera menindaklanjuti. Terima kasih!</p>

    <p style="margin:0; color:#6B7280;">Salam,<br>Tim Sales M2B Logistics</p>
@endsection

@section('footerNote', 'Email ini dikirim karena Anda mendaftar di portal.m2b.co.id. Jika ini bukan Anda, abaikan saja email ini.')
