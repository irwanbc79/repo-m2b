<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aktivasi Akun – M2B Portal</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:40px 0;">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                
                <!-- HEADER -->
                <tr>
                    <td style="background:#0b2e59;padding:20px;text-align:center;">
                        <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Portal" height="40">
                    </td>
                </tr>

                <!-- CONTENT -->
                <tr>
                    <td style="padding:32px;color:#333333;">
                        <h2 style="margin-top:0;">Verifikasi Alamat Email Anda</h2>

                        <p>Halo Bapak/Ibu {{ $user->name ?? 'Pengguna' }},</p>

                        <p>
                            Anda menerima email ini karena telah melakukan pendaftaran akun di
                            <strong>M2B Portal</strong>.
                            Untuk mengaktifkan akun dan mulai menggunakan layanan kami,
                            silakan verifikasi alamat email Anda dengan menekan tombol di bawah ini.
                        </p>

                        <div style="text-align:center;margin:32px 0;">
                            <a href="{{ $url }}"
                               style="background:#0b2e59;
                                      color:#ffffff;
                                      padding:14px 28px;
                                      text-decoration:none;
                                      border-radius:6px;
                                      font-weight:bold;
                                      display:inline-block;">
                                Verifikasi Email
                            </a>
                        </div>

                        <p style="font-size:13px;color:#666666;">
                            Tautan verifikasi ini berlaku selama <strong>60 menit</strong>.<br>
                            Jika Anda tidak merasa melakukan pendaftaran akun di M2B Portal,
                            abaikan email ini.
                        </p>

                        <p style="margin-top:32px;">
                            Hormat kami,<br>
                            <strong>Tim M2B Portal</strong>
                        </p>
                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td style="background:#f0f2f4;padding:16px;text-align:center;font-size:12px;color:#777777;">
                        © {{ date('Y') }} M2B Portal – PT. Mora Multi Berkah<br>
                        Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
