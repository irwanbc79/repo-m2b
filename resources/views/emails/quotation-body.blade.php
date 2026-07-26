@extends('emails.layouts.master')

@section('title', 'Penawaran Harga — M2B Logistics')

@section('footerNote', 'Ada pertanyaan seputar penawaran ini? Balas email ini, tim Sales kami akan membantu.')

@section('content')
    <p style="margin:0 0 4px;">Halo Pak/Bu</p>
    <p style="margin:0 0 20px; font-size:17px; font-weight:800; color:#0F2C59;">{{ $quotation->manual_pic ?? ($quotation->customer->company_name ?? 'Customer') }}</p>

    <p style="margin:0 0 16px; color:#4B5563;">Semoga hari Anda menyenangkan.</p>
    <p style="margin:0 0 22px; color:#4B5563;">Menindaklanjuti diskusi kita mengenai kebutuhan <strong style="color:#111827;">{{ ucfirst($quotation->service_type) }}</strong>, bersama email ini kami lampirkan penawaran harga terbaik dari kami.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; border:1px solid #E5E9F0; border-left:4px solid #B91C1C; border-radius:0 8px 8px 0; margin-bottom:20px;">
        <tr>
            <td style="padding:16px 18px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#9AA5B4; margin-bottom:8px;">Ringkasan Penawaran</div>
                <div style="font-size:14px; color:#374151; line-height:1.7;">
                    Nomor: <strong style="color:#0F2C59;">{{ $quotation->quotation_number }}</strong><br>
                    Rute: {{ $quotation->origin }} &rarr; {{ $quotation->destination }}<br>
                    Berlaku Hingga: <strong style="color:#0F2C59;">{{ $quotation->valid_until->format('d M Y') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; color:#4B5563;">Kami telah menyesuaikan skema agar prosesnya lebih efisien dan <em>cost-effective</em>. Dokumen lengkap tersedia pada file <strong>PDF</strong> yang terlampir.</p>

    @if($approval_url)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EFF6FF; border:1px solid #C3DDF7; border-radius:10px; margin-bottom:24px;">
        <tr>
            <td align="center" style="padding:24px;">
                <p style="margin:0 0 6px; font-size:16px; font-weight:700; color:#0F2C59;">Bagaimana respons Anda terhadap penawaran ini?</p>
                <p style="margin:0 0 20px; font-size:13px; color:#6B7280;">Klik tombol di bawah untuk menyetujui atau menolak — tanpa perlu login.</p>
                <a href="{{ $approval_url }}?action=approve" style="display:inline-block; background-color:#16A34A; color:#ffffff; font-weight:700; font-size:14px; padding:13px 30px; border-radius:24px; margin:0 4px 8px;">✅ Setujui Penawaran</a>
                <a href="{{ $approval_url }}?action=reject" style="display:inline-block; background-color:#ffffff; color:#B91C1C; font-weight:700; font-size:13px; padding:11px 22px; border-radius:24px; border:2px solid #B91C1C; margin:0 4px 8px;">Tolak</a>
                <p style="margin:6px 0 0; font-size:11px; color:#9AA5B4;">Link ini khusus untuk Anda dan hanya berlaku selama masa validitas penawaran.</p>
            </td>
        </tr>
    </table>
    @endif

    @if(!$is_registered)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border:1px solid #FDE68A; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0 0 8px; font-size:13px; font-weight:800; color:#92400E;">💡 Kelola penawaran & pengiriman lebih mudah lewat Portal M2B</p>
                <p style="margin:0 0 6px; font-size:13px; color:#78350F;">Dengan mendaftar di portal kami, Anda dapat:</p>
                <ul style="margin:0 0 14px; padding-left:18px; font-size:13px; color:#78350F;">
                    <li>Melihat &amp; menyetujui penawaran kapan saja</li>
                    <li>Memantau status pengiriman secara real-time</li>
                    <li>Mengunduh invoice &amp; dokumen pengiriman</li>
                    <li>Membuat booking langsung dari portal</li>
                </ul>
                <a href="{{ $portal_url }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:12px; padding:9px 20px; border-radius:6px;">Daftar Sekarang — Gratis</a>
            </td>
        </tr>
    </table>
    @else
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFBEB; border:1px solid #FDE68A; border-radius:8px; margin-bottom:22px;">
        <tr>
            <td style="padding:18px 20px;">
                <p style="margin:0 0 8px; font-size:13px; font-weight:800; color:#92400E;">📋 Lihat penawaran di Portal M2B</p>
                <p style="margin:0 0 12px; font-size:13px; color:#78350F;">Anda juga dapat melihat dan menyetujui penawaran ini langsung dari portal customer kami.</p>
                <a href="{{ url('/customer/quotations') }}" style="display:inline-block; background-color:#0F2C59; color:#ffffff; font-weight:700; font-size:12px; padding:9px 20px; border-radius:6px;">Buka Portal M2B</a>
            </td>
        </tr>
    </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td align="center">
                <p style="margin:0 0 10px; font-size:13px; color:#6B7280;">Atau hubungi kami langsung via WhatsApp:</p>
                <a href="https://wa.me/6281263027818" style="display:inline-block; background-color:#25D366; color:#ffffff; font-weight:700; font-size:13px; padding:11px 24px; border-radius:24px;">💬 Chat WhatsApp: +62 812 6302 7818</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; color:#4B5563;">Terima kasih atas kepercayaan Anda. Kami tunggu kabar baiknya.</p>
    <p style="margin:20px 0 0; color:#4B5563;">Salam hangat,</p>
    <p style="margin:4px 0 0; font-weight:700; color:#0F2C59;">Sales Department<br><span style="font-weight:400; color:#6B7280;">M2B Logistic</span></p>
@endsection
