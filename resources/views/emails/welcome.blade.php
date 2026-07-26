@extends('emails.layouts.master')

@section('title', 'Selamat Bergabung — Portal M2B')

@section('content')
    <p style="margin:0 0 4px;">Yth. Bapak/Ibu</p>
    <p style="margin:0 0 20px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $user->name }}</p>

    <p style="margin:0 0 20px; color:#4B5563;">Terima kasih telah bergabung dengan <strong style="color:#111827;">PT. Mora Multi Berkah (M2B)</strong>. Akun portal Anda telah berhasil dibuat.</p>

    <p style="margin:0 0 10px; color:#4B5563;">Melalui portal ini, Anda dapat menikmati kemudahan layanan kami:</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
        <tr><td style="padding:6px 0; font-size:14px; color:#374151;">📍 Melacak status pengiriman (Tracking) secara real-time</td></tr>
        <tr><td style="padding:6px 0; font-size:14px; color:#374151;">📄 Mengupload dokumen (Invoice, Packing List, dan dokumen lainnya) dengan aman</td></tr>
        <tr><td style="padding:6px 0; font-size:14px; color:#374151;">🧾 Melihat riwayat transaksi logistik Anda</td></tr>
    </table>

    <p style="margin:0 0 20px; color:#4B5563;">Silakan klik tombol di bawah ini untuk mengakses dashboard Anda:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ route('login') }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:13px 34px; border-radius:8px;">Login ke Portal</a>
            </td>
        </tr>
    </table>
@endsection
