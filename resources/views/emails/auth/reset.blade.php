<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background-color: #0F2C59; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; letter-spacing: 2px; }
        .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
        .button { display: inline-block; padding: 12px 24px; background-color: #B91C1C; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
        .footer { background-color: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #eeeeee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>M2B PORTAL</h1>
            <div style="color: #a0aec0; font-size: 12px; margin-top: 5px;">LOGISTIC | SOLUTION | PARTNER</div>
        </div>

        <div class="content">
            <p><strong>Yth. Pelanggan M2B,</strong></p>
            
            <p>Kami menerima permintaan untuk mengatur ulang kata sandi (reset password) untuk akun portal M2B Anda.</p>
            
            <p>Demi keamanan akun logistik Anda, silakan klik tombol di bawah ini untuk membuat kata sandi baru. Tautan ini hanya berlaku selama 60 menit.</p>
            
            <center>
                <a href="{{ $url }}" class="button">Reset Password Saya</a>
            </center>

            <p style="margin-top: 30px;">Jika Anda tidak merasa melakukan permintaan ini, mohon abaikan email ini. Akun Anda tetap aman.</p>
            
            <p>Hormat kami,<br>
            <strong>Tim Manajemen M2B</strong></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} PT. Mora Multi Berkah. All rights reserved.<br>
            Ini adalah email otomatis, mohon tidak membalas email ini.
        </div>
    </div>
</body>
</html>