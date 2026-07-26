@extends('emails.layouts.master')

@section('title', 'Survey Kepuasan ' . $year . ' — M2B Logistics')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #bfdbfe; border-radius:10px; margin-bottom:24px;">
        <tr>
            <td style="padding:28px; text-align:center;">
                <div style="font-size:44px; margin-bottom:10px;">📋</div>
                <div style="font-size:20px; font-weight:800; color:#0F2C59; margin-bottom:8px;">Survey Kepuasan {{ $year }}</div>
                <div style="font-size:14px; color:#374151;">Halo {{ $customerName }}, di awal tahun ini kami ingin mendengar pengalaman Anda bersama M2B.</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; color:#4B5563;">Masukan Anda membantu kami meningkatkan ketepatan waktu, kejelasan biaya, dan penanganan dokumen kepabeanan. Survey ini singkat — <strong style="color:#111827;">sekitar 5 menit</strong>, dan hanya sekali di tahun {{ $year }}.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr>
            <td align="center">
                <a href="{{ $surveyUrl }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:15px; padding:13px 34px; border-radius:8px;">Isi Survey Sekarang</a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:13px; color:#6B7280; text-align:center;">Terima kasih telah mempercayai M2B sebagai mitra logistik &amp; kepabeanan Anda. 🙏</p>
@endsection

@section('footerNote', 'Notifikasi otomatis M2B Portal.')
