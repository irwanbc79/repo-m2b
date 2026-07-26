@extends('emails.layouts.master')

@section('title', 'Password Berhasil Diubah — Portal M2B')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td align="center" style="background-color:#F0FDF4; border:1px solid #BBF7D0; border-radius:10px; padding:24px;">
                <div style="font-size:40px; margin-bottom:8px;">🔒✅</div>
                <div style="font-size:18px; font-weight:800; color:#065F46;">Password Berhasil Diubah!</div>
                <div style="font-size:13px; color:#374151; margin-top:4px;">Keamanan akun Anda telah diperbarui</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:16px; font-weight:700; color:#0F2C59;">Halo, {{ $user->name }}!</p>
    <p style="margin:0 0 22px; color:#4B5563;">Password akun Portal M2B Anda telah <strong style="color:#111827;">berhasil diubah</strong>. Anda sekarang dapat login menggunakan password baru Anda.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:20px;">
        <tr>
            <td style="padding:16px 20px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#9AA5B4; margin-bottom:10px;">Detail Perubahan</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Akun</td>
                        <td style="padding:6px 0; font-weight:700; color:#111827; text-align:right;">{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; color:#6B7280;">Waktu Perubahan</td>
                        <td style="padding:6px 0; font-weight:700; color:#111827; text-align:right;">{{ now()->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border-left:4px solid #F59E0B; border-radius:0 6px 6px 0; margin-bottom:24px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:12px; font-weight:800; color:#92400E; margin-bottom:4px;">Bukan Anda yang Melakukan Ini?</div>
                <div style="font-size:13px; color:#78350F;">Segera hubungi tim support kami di <strong>sales@m2b.co.id</strong> atau telepon <strong>061-44020012</strong>.</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ route('login') }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:13px 34px; border-radius:8px;">Login Sekarang</a>
            </td>
        </tr>
    </table>
@endsection

@section('footerNote', 'Email keamanan otomatis. Jika ini bukan Anda, segera hubungi tim kami.')
