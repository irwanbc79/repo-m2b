@extends('emails.layouts.master')

@section('title', 'Dana Kas Kecil Sudah Ditransfer')

@section('content')
    <p style="margin:0 0 6px; font-size:19px; font-weight:800; color:#0F2C59;">💸 Dana Kas Kecil Sudah Ditransfer</p>

    <p style="margin:0 0 4px; color:#4B5563;">Halo</p>
    <p style="margin:0 0 18px; font-size:16px; font-weight:700; color:#0F2C59;">{{ $fund->holder->name ?? 'Pemegang Kas' }}</p>
    <p style="margin:0 0 22px; color:#4B5563;">Dana top up kas kecil sudah ditransfer!</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0; color:#6B7280; width:170px;">No. Top Up</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $topup->topup_number }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Tanggal Transfer</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ now()->format('d/m/Y H:i') }}</td></tr>
                    <tr>
                        <td style="padding:10px 0 0; color:#6B7280; border-top:1px dashed #E5E9F0;">Jumlah Transfer</td>
                        <td style="padding:10px 0 0; font-weight:800; font-size:17px; color:#0F2C59; border-top:1px dashed #E5E9F0;">Rp {{ number_format($topup->amount_approved, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0 0; color:#6B7280; border-top:1px dashed #E5E9F0;">Saldo Kas Kecil Sekarang</td>
                        <td style="padding:10px 0 0; font-weight:800; font-size:16px; color:#16A34A; border-top:1px dashed #E5E9F0;">Rp {{ number_format($fund->current_balance, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#4B5563;">Silakan cek saldo kas kecil Anda di portal.</p>
@endsection

@section('footerNote', 'Notifikasi otomatis Portal M2B.')
