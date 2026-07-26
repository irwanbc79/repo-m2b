@extends('emails.layouts.master')

@section('title', 'Testimoni Baru Menunggu Moderasi')

@section('content')
    <p style="margin:0 0 6px; font-size:19px; font-weight:800; color:#0F2C59;">⭐ Testimoni Baru Menunggu Moderasi</p>
    <p style="margin:0 0 20px; color:#6B7280; font-size:13px;">Silakan review dan setujui atau tolak di halaman admin.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:16px;">
                <div style="margin-bottom:10px;">
                    @for($i = 1; $i <= 5; $i++)
                        <span style="font-size:18px; color:{{ $i <= $testimonial->rating ? '#F59E0B' : '#D1D5DB' }};">★</span>
                    @endfor
                    <span style="font-size:13px; color:#6B7280; margin-left:6px;">{{ $testimonial->rating }}/5</span>
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-left:4px solid #0F2C59; border-radius:0 8px 8px 0; margin-bottom:12px;">
                    <tr><td style="padding:12px;"><em style="font-size:14px; color:#374151;">"{{ $testimonial->content }}"</em></td></tr>
                </table>
                <div style="font-size:13px; color:#374151;">
                    <strong>{{ $testimonial->display_name ?: 'Tidak ada nama' }}</strong>
                    @if($testimonial->company_name) — {{ $testimonial->company_name }} @endif
                    @if($testimonial->position) <span style="color:#6B7280;">({{ $testimonial->position }})</span> @endif
                </div>
                <div style="font-size:12px; color:#9AA5B4; margin-top:4px;">Dikirim: {{ $testimonial->updated_at?->format('d F Y, H:i') ?? now()->format('d F Y, H:i') }} WIB</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ $moderationUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:14px; padding:12px 28px; border-radius:8px;">Buka Halaman Moderasi Testimoni</a>
            </td>
        </tr>
    </table>
@endsection

@section('footerNote', 'Notifikasi otomatis M2B Portal.')
