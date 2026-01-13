<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 4px solid #B91C1C; }
        .header img { height: 60px; width: auto; }
        .content { padding: 40px 30px; color: #374151; line-height: 1.6; }
        .button { display: inline-block; padding: 12px 30px; background-color: #0F2C59; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 30px; font-size: 14px; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .small { font-size: 12px; color: #6b7280; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://portal.m2b.co.id/images/m2b-logo.png" alt="M2B Portal">
        </div>

        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>
            
            <p>Kami menerima permintaan untuk mengatur ulang kata sandi (reset password) untuk akun Portal M2B Anda.</p>
            
            <center>
                <a href="{{ $url }}" class="button">Atur Ulang Kata Sandi</a>
            </center>

            <p class="small">Tautan ini akan kadaluarsa dalam 60 menit demi keamanan.<br>
            Jika Anda tidak merasa melakukan permintaan ini, mohon abaikan email ini.</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} PT. Mora Multi Berkah.<br>
            Logistic | Solution | Partner
        </div>
    </div>
</body>
</html>