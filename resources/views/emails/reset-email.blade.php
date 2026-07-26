@extends('emails.layouts.master')

@section('title', 'Reset Password — Portal M2B')

@section('content')
    <p style="margin:0 0 4px;">Halo</p>
    <p style="margin:0 0 20px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $user->name }}</p>

    <p style="margin:0 0 24px; color:#4B5563;">Kami menerima permintaan untuk mengatur ulang kata sandi (reset password) untuk akun Portal M2B Anda.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr>
            <td align="center">
                <a href="{{ $url }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:13px 34px; border-radius:8px;">Atur Ulang Kata Sandi</a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:12px; color:#9AA5B4; text-align:center;">Tautan ini akan kadaluarsa dalam 60 menit demi keamanan.<br>Jika Anda tidak merasa melakukan permintaan ini, mohon abaikan email ini.</p>
@endsection

@section('footerNote', 'Email keamanan otomatis. Jika ini bukan Anda, segera hubungi tim kami.')
