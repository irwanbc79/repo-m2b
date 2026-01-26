<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - M2B Portal</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f1f5f9;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="padding: 40px 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; background: #ffffff; border-radius: 16px; overflow: hidden;">
                    <tr>
                        <td style="background: #10b981; padding: 40px 30px; text-align: center;">
                            <table cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="background: #ffffff; padding: 15px 25px; border-radius: 8px;">
                                        <img src="https://portal.m2b.co.id/images/m2b-logo.png" alt="M2B" style="width: 120px; height: auto; display: block;">
                                    </td>
                                </tr>
                            </table>
                            <h1 style="color: #ffffff; font-size: 24px; margin: 20px 0 5px;">Email Terverifikasi!</h1>
                            <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 0;">Akun Anda telah aktif dan siap digunakan</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="font-size: 20px; color: #1e293b; margin: 0 0 15px;">Selamat Datang, <span style="color: #10b981;">{{ $user->name }}</span>!</h2>
                            <p style="color: #64748b; font-size: 15px; line-height: 1.8; margin: 0 0 25px;">
                                Akun Portal M2B Anda telah <strong>berhasil diverifikasi</strong>. 
                                Sekarang Anda dapat menikmati semua layanan logistik kami.
                            </p>
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="background: #f8fafc; border-left: 4px solid #3b82f6; padding: 15px;">
                                        <strong style="color: #1e293b;">Tracking Real-Time</strong><br>
                                        <span style="color: #64748b; font-size: 12px;">Lacak pengiriman Anda kapan saja</span>
                                    </td>
                                </tr>
                                <tr><td style="height: 10px;"></td></tr>
                                <tr>
                                    <td style="background: #f8fafc; border-left: 4px solid #3b82f6; padding: 15px;">
                                        <strong style="color: #1e293b;">Upload Dokumen</strong><br>
                                        <span style="color: #64748b; font-size: 12px;">Kelola dokumen pengiriman dengan aman</span>
                                    </td>
                                </tr>
                                <tr><td style="height: 10px;"></td></tr>
                                <tr>
                                    <td style="background: #f8fafc; border-left: 4px solid #3b82f6; padding: 15px;">
                                        <strong style="color: #1e293b;">Invoice Online</strong><br>
                                        <span style="color: #64748b; font-size: 12px;">Lihat dan unduh tagihan digital</span>
                                    </td>
                                </tr>
                            </table>
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ route('login') }}" style="display: inline-block; background: #f59e0b; color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 50px; font-size: 15px; font-weight: bold;">MASUK KE PORTAL</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #1e293b; padding: 25px; text-align: center;">
                            <p style="color: #ffffff; font-size: 14px; font-weight: bold; margin: 0 0 5px;">PT. MORA MULTI BERKAH</p>
                            <p style="color: #94a3b8; font-size: 11px; margin: 0 0 10px;">LOGISTIC | SOLUTION | PARTNER</p>
                            <p style="color: #94a3b8; font-size: 11px; line-height: 1.8; margin: 0;">
                                Jl. Kapt. Sumarsono Komp. Graha Metropolitan Blok G No. 14, Medan<br>
                                Telp: 061-44020012 | Email: sales@m2b.co.id
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
