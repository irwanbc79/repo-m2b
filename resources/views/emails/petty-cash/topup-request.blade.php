@extends('emails.layouts.master')

@section('title', 'Request Top Up Kas Kecil')

@section('content')
    <p style="margin:0 0 6px; font-size:19px; font-weight:800; color:#0F2C59;">💰 Request Top Up Kas Kecil</p>

    <p style="margin:0 0 4px; color:#4B5563;">Halo</p>
    <p style="margin:0 0 18px; font-size:16px; font-weight:700; color:#0F2C59;">{{ $fund->approver->name ?? 'Approver' }}</p>
    <p style="margin:0 0 22px; color:#4B5563;">Ada permintaan top up kas kecil yang memerlukan persetujuan Anda:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0; color:#6B7280; width:150px;">No. Request</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $topup->topup_number }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Dari</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $requester->name }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Tanggal</td><td style="padding:6px 0; font-weight:700; color:#111827;">{{ $topup->created_at->format('d/m/Y H:i') }}</td></tr>
                    <tr><td style="padding:6px 0; color:#6B7280;">Saldo Saat Ini</td><td style="padding:6px 0; font-weight:700; color:#111827;">Rp {{ number_format($topup->balance_before, 0, ',', '.') }}</td></tr>
                    <tr>
                        <td style="padding:10px 0 0; color:#6B7280; border-top:1px dashed #E5E9F0;">Jumlah Request</td>
                        <td style="padding:10px 0 0; font-weight:800; font-size:17px; color:#0F2C59; border-top:1px dashed #E5E9F0;">Rp {{ number_format($topup->amount_requested, 0, ',', '.') }}</td>
                    </tr>
                </table>
                @if($topup->notes)
                <p style="margin:12px 0 0; font-size:13px; color:#374151;"><strong>Catatan:</strong> {{ $topup->notes }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ url('/admin/kas-kecil') }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:12px 30px; border-radius:8px;">Lihat &amp; Approve</a>
            </td>
        </tr>
    </table>
@endsection

@section('footerNote', 'Notifikasi otomatis Portal M2B.')
