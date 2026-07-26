@extends('emails.layouts.master')

@section('title', 'Verifikasi Email — Portal M2B')

@section('content')
    <p style="margin:0 0 4px;">Halo</p>
    <p style="margin:0 0 20px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $user->name }}</p>

    <p style="margin:0 0 24px; color:#4B5563;">Terima kasih telah mendaftar di <strong style="color:#111827;">Portal M2B</strong>. Untuk mengaktifkan akun Anda, silakan verifikasi alamat email dengan mengklik tombol di bawah ini.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EFF6FF; border:2px dashed #0F2C59; border-radius:10px; margin-bottom:20px;">
        <tr>
            <td align="center" style="padding:26px;">
                <div style="font-size:16px; font-weight:800; color:#0F2C59; margin-bottom:6px;">Verifikasi Email Anda</div>
                <div style="font-size:13px; color:#6B7280; margin-bottom:18px;">Klik tombol di bawah untuk mengkonfirmasi email Anda</div>
                <a href="{{ $verificationUrl }}" style="display:inline-block; background-color:#16A34A; color:#ffffff; font-weight:700; font-size:14px; padding:13px 34px; border-radius:24px;">Verifikasi Sekarang</a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border-left:4px solid #F59E0B; border-radius:0 6px 6px 0; margin-bottom:20px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px; color:#92400E;"><strong>Penting:</strong> Link verifikasi ini akan kedaluwarsa dalam <strong>60 menit</strong>.</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px; font-size:12px; color:#6B7280;">Jika tombol tidak berfungsi, copy link berikut:</p>
    <p style="margin:0; font-size:11px; color:#0F2C59; word-break:break-all;">{{ $verificationUrl }}</p>
@endsection
