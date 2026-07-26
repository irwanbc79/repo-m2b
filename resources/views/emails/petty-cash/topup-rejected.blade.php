@extends('emails.layouts.master')

@section('title', 'Top Up Kas Kecil Ditolak')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#B91C1C; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <div style="font-size:17px; font-weight:800; color:#ffffff;">❌ Top Up Kas Kecil Ditolak</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; color:#4B5563;">Halo</p>
    <p style="margin:0 0 18px; font-size:16px; font-weight:700; color:#0F2C59;">{{ $topup->requester->name ?? 'User' }}</p>
    <p style="margin:0 0 22px; color:#4B5563;">Mohon maaf, request top up kas kecil Anda <strong style="color:#B91C1C;">DITOLAK</strong>.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:16px;">
        <tr>
            <td style="padding:18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0; color:#6B7280; width:150px;">No. Request</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $topup->topup_number }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Jumlah Request</td><td style="padding:6px 0; font-weight:700; color:#111827;">Rp {{ number_format($topup->amount_requested, 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Ditolak Oleh</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $rejector->name }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FEF2F2; border-left:4px solid #B91C1C; border-radius:0 6px 6px 0; margin-bottom:22px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:12px; font-weight:800; color:#991B1B; margin-bottom:4px;">Alasan Penolakan</div>
                <div style="font-size:13px; color:#374151;">{{ $topup->reject_reason ?? 'Tidak ada alasan yang diberikan' }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#4B5563;">Silakan hubungi approver untuk informasi lebih lanjut atau ajukan request baru.</p>
@endsection

@section('footerNote', 'Notifikasi otomatis Portal M2B.')
