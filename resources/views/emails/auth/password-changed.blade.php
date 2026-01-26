<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Berhasil Diubah - M2B Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; padding: 40px 20px; }
        .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 30px; text-align: center; }
        .success-icon { font-size: 50px; margin-bottom: 15px; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 700; margin-bottom: 5px; }
        .header p { color: rgba(255,255,255,0.9); font-size: 13px; }
        .body { padding: 40px; }
        .greeting { font-size: 18px; color: #1e293b; margin-bottom: 15px; }
        .message { color: #64748b; font-size: 15px; line-height: 1.8; margin-bottom: 25px; }
        .info-box { background: #ecfdf5; border: 2px solid #10b981; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .info-box h3 { color: #065f46; font-size: 14px; margin-bottom: 15px; }
        .info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #a7f3d0; font-size: 13px; }
        .info-item:last-child { border-bottom: none; }
        .info-item .label { color: #047857; }
        .info-item .value { color: #065f46; font-weight: 600; }
        .security-notice { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 0 8px 8px 0; margin: 20px 0; }
        .security-notice h4 { color: #92400e; font-size: 13px; margin-bottom: 8px; }
        .security-notice p { color: #a16207; font-size: 12px; line-height: 1.6; margin: 0; }
        .cta-section { text-align: center; margin: 25px 0; }
        .btn-login { display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%); color: #ffffff !important; text-decoration: none; padding: 14px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .footer { background: #1e293b; padding: 25px; text-align: center; }
        .footer-brand { color: #ffffff; font-size: 14px; font-weight: 700; margin-bottom: 5px; }
        .footer-tagline { color: #94a3b8; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 10px; }
        .footer-contact { color: #94a3b8; font-size: 11px; line-height: 1.8; }
        .footer-contact a { color: #60a5fa; text-decoration: none; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="success-icon">&#128274;&#10004;</div>
            <h1>Password Berhasil Diubah!</h1>
            <p>Keamanan akun Anda telah diperbarui</p>
        </div>
        <div class="body">
            <h2 class="greeting">Halo, {{ $user->name }}!</h2>
            <p class="message">
                Password akun Portal M2B Anda telah <strong>berhasil diubah</strong>. 
                Anda sekarang dapat login menggunakan password baru Anda.
            </p>
            <div class="info-box">
                <h3>Detail Perubahan</h3>
                <div class="info-item">
                    <span class="label">Akun</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Waktu Perubahan</span>
                    <span class="value">{{ now()->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                </div>
                <div class="info-item">
                    <span class="label">Status</span>
                    <span class="value">Password Diperbarui</span>
                </div>
            </div>
            <div class="security-notice">
                <h4>Bukan Anda yang Melakukan Ini?</h4>
                <p>
                    Jika Anda tidak melakukan perubahan password ini, segera hubungi tim support kami di 
                    <strong>sales@m2b.co.id</strong> atau telepon <strong>061-44020012</strong>.
                </p>
            </div>
            <div class="cta-section">
                <a href="{{ route('login') }}" class="btn-login">Login Sekarang</a>
            </div>
        </div>
        <div class="footer">
            <div class="footer-brand">PT. MORA MULTI BERKAH</div>
            <div class="footer-tagline">Logistic | Solution | Partner</div>
            <div class="footer-contact">
                Jl. Kapt. Sumarsono Komp. Graha Metropolitan Blok G No. 14<br>
                Medan - Indonesia | Telp: <a href="tel:06144020012">061-44020012</a><br>
                Email: <a href="mailto:sales@m2b.co.id">sales@m2b.co.id</a>
            </div>
        </div>
    </div>
</body>
</html>
