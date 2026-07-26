@extends('emails.layouts.master')

@section('title', 'Reset Password — Portal M2B')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td align="center" style="background-color:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:24px;">
                <div style="font-size:40px; margin-bottom:8px;">🔒</div>
                <div style="font-size:18px; font-weight:800; color:#991B1B;">Reset Password</div>
                <div style="font-size:13px; color:#374151; margin-top:4px;">Permintaan untuk mengatur ulang kata sandi</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:16px; font-weight:700; color:#0F2C59;">Halo, {{ $user->name ?? 'Pengguna' }}!</p>
    <p style="margin:0 0 24px; color:#4B5563;">Kami menerima permintaan untuk mengatur ulang kata sandi akun Portal M2B Anda. Jika Anda yang meminta ini, klik tombol di bawah untuk melanjutkan.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border:2px solid #F59E0B; border-radius:10px; margin-bottom:20px;">
        <tr>
            <td align="center" style="padding:22px;">
                <div style="font-size:15px; font-weight:800; color:#92400E; margin-bottom:6px;">Atur Ulang Kata Sandi</div>
                <div style="font-size:13px; color:#78350F; margin-bottom:16px;">Klik tombol di bawah untuk membuat password baru</div>
                <a href="{{ $resetUrl }}" style="display:inline-block; background-color:#B91C1C; color:#ffffff; font-weight:700; font-size:14px; padding:13px 34px; border-radius:24px;">Reset Password</a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FEF2F2; border-left:4px solid #DC2626; border-radius:0 6px 6px 0; margin-bottom:20px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px; color:#991B1B;"><strong>Peringatan:</strong> Link ini akan kedaluwarsa dalam <strong>60 menit</strong>. Jika Anda tidak meminta reset password, abaikan email ini.</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border-radius:8px; margin-bottom:20px;">
        <tr>
            <td style="padding:16px 20px;">
                <div style="font-size:12px; font-weight:800; color:#1F2937; margin-bottom:8px;">Tips Keamanan Password</div>
                <div style="font-size:12px; color:#6B7280; line-height:1.8;">
                    ✓ Gunakan minimal 8 karakter<br>
                    ✓ Kombinasikan huruf besar, kecil, angka, dan simbol<br>
                    ✓ Jangan gunakan informasi pribadi yang mudah ditebak<br>
                    ✓ Jangan gunakan password yang sama dengan akun lain
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px; font-size:12px; color:#6B7280;">Jika tombol tidak berfungsi, copy link berikut ke browser:</p>
    <p style="margin:0; font-size:11px; color:#0F2C59; word-break:break-all;">{{ $resetUrl }}</p>
@endsection

@section('footerNote', 'Email keamanan otomatis. Jika ini bukan Anda, segera hubungi tim kami.')
