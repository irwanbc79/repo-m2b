<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - M2B Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; padding: 40px 20px; }
        .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 40px 30px; text-align: center; }
        .lock-icon { font-size: 50px; margin-bottom: 15px; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 700; margin-bottom: 5px; }
        .header p { color: rgba(255,255,255,0.9); font-size: 13px; }
        .body { padding: 40px; }
        .greeting { font-size: 18px; color: #1e293b; margin-bottom: 15px; }
        .message { color: #64748b; font-size: 15px; line-height: 1.8; margin-bottom: 25px; }
        .reset-box { background: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; padding: 25px; text-align: center; margin: 25px 0; }
        .reset-box h3 { color: #92400e; font-size: 16px; margin-bottom: 10px; }
        .reset-box p { color: #a16207; font-size: 13px; margin-bottom: 20px; }
        .btn-reset { display: inline-block; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff !important; text-decoration: none; padding: 14px 40px; border-radius: 50px; font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .warning { background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; border-radius: 0 8px 8px 0; margin: 20px 0; }
        .warning p { color: #991b1b; font-size: 13px; margin: 0; }
        .security-box { background: #f1f5f9; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .security-box h4 { color: #1e293b; font-size: 13px; margin-bottom: 10px; }
        .security-box ul { list-style: none; padding: 0; margin: 0; }
        .security-box li { color: #64748b; font-size: 12px; padding: 5px 0; padding-left: 20px; position: relative; }
        .security-box li::before { content: '✓'; position: absolute; left: 0; color: #10b981; }
        .alt-link { background: #f1f5f9; border-radius: 8px; padding: 15px; margin-top: 20px; word-break: break-all; }
        .alt-link p { font-size: 12px; color: #64748b; margin-bottom: 8px; }
        .alt-link a { font-size: 11px; color: #3b82f6; text-decoration: none; }
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
            <div class="lock-icon">&#128274;</div>
            <h1>Reset Password</h1>
            <p>Permintaan untuk mengatur ulang kata sandi</p>
        </div>
        <div class="body">
            <h2 class="greeting">Halo, {{ $user->name ?? 'Pengguna' }}!</h2>
            <p class="message">
                Kami menerima permintaan untuk mengatur ulang kata sandi akun Portal M2B Anda. 
                Jika Anda yang meminta ini, klik tombol di bawah untuk melanjutkan.
            </p>
            <div class="reset-box">
                <h3>Atur Ulang Kata Sandi</h3>
                <p>Klik tombol di bawah untuk membuat password baru</p>
                <a href="{{ $resetUrl }}" class="btn-reset">Reset Password</a>
            </div>
            <div class="warning">
                <p><strong>Peringatan:</strong> Link ini akan kedaluwarsa dalam <strong>60 menit</strong>. Jika Anda tidak meminta reset password, abaikan email ini.</p>
            </div>
            <div class="security-box">
                <h4>Tips Keamanan Password</h4>
                <ul>
                    <li>Gunakan minimal 8 karakter</li>
                    <li>Kombinasikan huruf besar, kecil, angka, dan simbol</li>
                    <li>Jangan gunakan informasi pribadi yang mudah ditebak</li>
                    <li>Jangan gunakan password yang sama dengan akun lain</li>
                </ul>
            </div>
            <div class="alt-link">
                <p>Jika tombol tidak berfungsi, copy link berikut ke browser:</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
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
