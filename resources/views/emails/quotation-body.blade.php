<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4; }
        .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background-color: #0F2C59; color: #fff; padding: 24px 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 900; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; font-size: 11px; opacity: 0.75; text-transform: uppercase; letter-spacing: 2px; }
        .content { padding: 32px 30px; }
        .details-box { background-color: #f8f9fa; border-left: 4px solid #B91C1C; padding: 16px 18px; margin: 20px 0; font-size: 14px; border-radius: 0 6px 6px 0; }
        .details-box strong { display: block; margin-bottom: 8px; color: #0F2C59; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .action-section { background: linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 100%); border: 1px solid #c3ddf7; border-radius: 10px; padding: 24px; margin: 24px 0; text-align: center; }
        .action-section h3 { margin: 0 0 8px; color: #0F2C59; font-size: 16px; }
        .action-section p { margin: 0 0 20px; color: #555; font-size: 13px; }
        .btn-approve { display: inline-block; background-color: #16a34a; color: #fff !important; text-decoration: none; font-weight: bold; font-size: 15px; padding: 13px 32px; border-radius: 50px; margin: 0 6px 10px; box-shadow: 0 3px 10px rgba(22,163,74,0.35); }
        .btn-reject { display: inline-block; background-color: #fff; color: #B91C1C !important; text-decoration: none; font-weight: bold; font-size: 13px; padding: 10px 24px; border-radius: 50px; margin: 0 6px 10px; border: 2px solid #B91C1C; }
        .btn-wa { display: inline-block; background-color: #25D366; color: #fff !important; text-decoration: none; font-weight: bold; padding: 11px 24px; border-radius: 50px; margin: 0 6px 10px; font-size: 13px; }
        .divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }
        .portal-box { background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 18px 20px; margin: 20px 0; font-size: 13px; }
        .portal-box strong { color: #92400e; }
        .btn-portal { display: inline-block; background-color: #0F2C59; color: #fff !important; text-decoration: none; font-weight: bold; padding: 9px 20px; border-radius: 6px; margin-top: 10px; font-size: 12px; }
        .token-note { font-size: 11px; color: #999; margin-top: 10px; }
        .footer { background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 11px; color: #888; }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="container">

        {{-- Header --}}
        <div class="header">
            <h1>PT. MORA MULTI BERKAH</h1>
            <p>Logistic &nbsp;|&nbsp; Solution &nbsp;|&nbsp; Partner</p>
        </div>

        <div class="content">
            <p>Halo Pak/Bu <strong>{{ $quotation->manual_pic ?? ($quotation->customer->company_name ?? 'Customer') }}</strong>,</p>
            <p>Semoga hari Anda menyenangkan.</p>
            <p>Menindaklanjuti diskusi kita mengenai kebutuhan <strong>{{ ucfirst($quotation->service_type) }}</strong>, bersama email ini kami lampirkan penawaran harga terbaik dari kami.</p>

            {{-- Ringkasan --}}
            <div class="details-box">
                <strong>Ringkasan Penawaran:</strong>
                Nomor: <strong>{{ $quotation->quotation_number }}</strong><br>
                Rute: {{ $quotation->origin }} &rarr; {{ $quotation->destination }}<br>
                Berlaku Hingga: <strong>{{ $quotation->valid_until->format('d M Y') }}</strong>
            </div>

            <p>Kami telah menyesuaikan skema agar prosesnya lebih efisien dan <em>cost-effective</em>. Dokumen lengkap tersedia pada file <strong>PDF</strong> yang terlampir.</p>

            <hr class="divider">

            {{-- Tombol aksi utama --}}
            @if($approval_url)
            <div class="action-section">
                <h3>Bagaimana respons Anda terhadap penawaran ini?</h3>
                <p>Klik tombol di bawah untuk menyetujui atau menolak penawaran — tanpa perlu login.</p>
                <a href="{{ $approval_url }}?action=approve" class="btn-approve">✅ &nbsp;Setujui Penawaran</a>
                <a href="{{ $approval_url }}?action=reject" class="btn-reject">Tolak</a>
                <p class="token-note">Link ini khusus untuk Anda dan hanya berlaku selama masa validitas penawaran.</p>
            </div>
            @endif

            <hr class="divider">

            {{-- Ajakan portal jika belum terdaftar --}}
            @if(!$is_registered)
            <div class="portal-box">
                <strong>💡 Kelola penawaran & pengiriman lebih mudah lewat Portal M2B</strong>
                <p style="margin:8px 0 4px; color:#555;">Dengan mendaftar di portal kami, Anda dapat:</p>
                <ul style="margin:4px 0 10px; padding-left:18px; color:#555; font-size:13px;">
                    <li>Melihat & menyetujui penawaran kapan saja</li>
                    <li>Memantau status pengiriman secara real-time</li>
                    <li>Mengunduh invoice & dokumen pengiriman</li>
                    <li>Membuat booking langsung dari portal</li>
                </ul>
                <a href="{{ $portal_url }}" class="btn-portal">Daftar Sekarang — Gratis</a>
            </div>
            @else
            <div class="portal-box">
                <strong>📋 Lihat penawaran di Portal M2B</strong>
                <p style="margin:8px 0 10px; color:#555;">Anda juga dapat melihat dan menyetujui penawaran ini langsung dari portal customer kami.</p>
                <a href="{{ url('/customer/quotations') }}" class="btn-portal">Buka Portal M2B</a>
            </div>
            @endif

            <hr class="divider">

            <p>Atau hubungi kami langsung via WhatsApp:</p>
            <center>
                <a href="https://wa.me/6281263027818" class="btn-wa" target="_blank">
                    💬 &nbsp;Chat WhatsApp: +62 812 6302 7818
                </a>
            </center>

            <br>
            <p>Terima kasih atas kepercayaan Anda. Kami tunggu kabar baiknya.</p>
            <p style="margin-bottom:0;">Salam hangat,</p>
            <p style="margin-top:5px;"><strong>Sales Department</strong><br>M2B Logistic</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} PT. Mora Multi Berkah &mdash; Medan, Sumatera Utara, Indonesia.<br>
            Email ini dikirim otomatis, mohon jangan membalas ke alamat ini.
        </div>
    </div>
</div>
</body>
</html>
