@extends('emails.layouts.master')

@section('title', 'Selamat Datang — Portal M2B')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td align="center" style="background-color:#F0FDF4; border:1px solid #BBF7D0; border-radius:10px; padding:24px;">
                <div style="font-size:40px; margin-bottom:8px;">✅</div>
                <div style="font-size:18px; font-weight:800; color:#065F46;">Email Terverifikasi!</div>
                <div style="font-size:13px; color:#374151; margin-top:4px;">Akun Anda telah aktif dan siap digunakan</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px; color:#4B5563;">Selamat datang, <strong style="color:#0F2C59;">{{ $user->name }}</strong>! Akun Portal M2B Anda telah <strong style="color:#111827;">berhasil diverifikasi</strong>. Sekarang Anda dapat menikmati semua layanan logistik kami.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
        <tr><td style="padding:10px 14px; background-color:#F8FAFC; border-left:4px solid #0F2C59; border-radius:0 6px 6px 0; margin-bottom:8px;">
            <strong style="color:#1F2937; font-size:14px;">Tracking Real-Time</strong><br>
            <span style="color:#6B7280; font-size:12px;">Lacak pengiriman Anda kapan saja</span>
        </td></tr>
        <tr><td style="height:10px; font-size:0;">&nbsp;</td></tr>
        <tr><td style="padding:10px 14px; background-color:#F8FAFC; border-left:4px solid #0F2C59; border-radius:0 6px 6px 0;">
            <strong style="color:#1F2937; font-size:14px;">Upload Dokumen</strong><br>
            <span style="color:#6B7280; font-size:12px;">Kelola dokumen pengiriman dengan aman</span>
        </td></tr>
        <tr><td style="height:10px; font-size:0;">&nbsp;</td></tr>
        <tr><td style="padding:10px 14px; background-color:#F8FAFC; border-left:4px solid #0F2C59; border-radius:0 6px 6px 0;">
            <strong style="color:#1F2937; font-size:14px;">Invoice Online</strong><br>
            <span style="color:#6B7280; font-size:12px;">Lihat dan unduh tagihan digital</span>
        </td></tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ route('login') }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:13px 34px; border-radius:8px;">Masuk ke Portal</a>
            </td>
        </tr>
    </table>
@endsection
