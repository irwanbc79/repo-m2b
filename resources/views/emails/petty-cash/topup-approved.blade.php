@extends('emails.layouts.master')

@section('title', 'Top Up Kas Kecil Disetujui')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F0FDF4; border:1px solid #BBF7D0; border-radius:10px; margin-bottom:22px;">
        <tr>
            <td style="padding:20px;">
                <div style="font-size:17px; font-weight:800; color:#065F46;">✅ Top Up Kas Kecil Disetujui</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; color:#4B5563;">Halo</p>
    <p style="margin:0 0 18px; font-size:16px; font-weight:700; color:#0F2C59;">{{ $fund->holder->name ?? 'Pemegang Kas' }}</p>
    <p style="margin:0 0 22px; color:#4B5563;">Request top up kas kecil Anda telah <strong style="color:#16A34A;">DISETUJUI</strong>!</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0; color:#6B7280; width:150px;">No. Request</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $topup->topup_number }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Disetujui Oleh</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $approver->name }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Tanggal Approve</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ now()->format('d/m/Y H:i') }}</td></tr>
                    <tr>
                        <td style="padding:10px 0 0; color:#6B7280; border-top:1px dashed #E5E9F0;">Jumlah Disetujui</td>
                        <td style="padding:10px 0 0; font-weight:800; font-size:17px; color:#16A34A; border-top:1px dashed #E5E9F0;">Rp {{ number_format($topup->amount_approved ?? $topup->amount_requested, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#4B5563;">Dana akan segera ditransfer ke Anda. Harap tunggu konfirmasi transfer.</p>
@endsection

@section('footerNote', 'Notifikasi otomatis Portal M2B.')
