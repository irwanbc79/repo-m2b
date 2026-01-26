<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - M2B Portal</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f1f5f9;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="padding: 40px 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; background: #ffffff; border-radius: 16px; overflow: hidden;">
                    <tr>
                        <td style="background: #1e3a8a; padding: 40px 30px; text-align: center;">
                            <table cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="background: #ffffff; padding: 15px 25px; border-radius: 8px;">
                                        <img src="https://portal.m2b.co.id/images/m2b-logo.png" alt="M2B" style="width: 120px; height: auto; display: block;">
                                    </td>
                                </tr>
                            </table>
                            <h1 style="color: #ffffff; font-size: 22px; margin: 20px 0 5px;">PT. MORA MULTI BERKAH</h1>
                            <p style="color: rgba(255,255,255,0.8); font-size: 13px; letter-spacing: 2px; margin: 0;">LOGISTIC | SOLUTION | PARTNER</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="font-size: 20px; color: #1e293b; margin: 0 0 15px;">Halo, <span style="color: #3b82f6;">{{ $user->name }}</span>!</h2>
                            <p style="color: #64748b; font-size: 15px; line-height: 1.8; margin: 0 0 25px;">
                                Terima kasih telah mendaftar di <strong>Portal M2B</strong>. 
                                Untuk mengaktifkan akun Anda, silakan verifikasi alamat email dengan mengklik tombol di bawah ini.
                            </p>
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f0f9ff; border: 2px dashed #3b82f6; border-radius: 12px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 30px; text-align: center;">
                                        <h3 style="color: #1e3a8a; font-size: 18px; margin: 0 0 10px;">Verifikasi Email Anda</h3>
                                        <p style="color: #64748b; font-size: 14px; margin: 0 0 20px;">Klik tombol di bawah untuk mengkonfirmasi email Anda</p>
                                        <a href="{{ $verificationUrl }}" style="display: inline-block; background: #10b981; color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 50px; font-size: 15px; font-weight: bold;">VERIFIKASI SEKARANG</a>
                                    </td>
                                </tr>
                            </table>
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #fef3c7; border-left: 4px solid #f59e0b; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 15px;">
                                        <p style="color: #92400e; font-size: 13px; margin: 0;"><strong>Penting:</strong> Link verifikasi ini akan kedaluwarsa dalam <strong>60 menit</strong>.</p>
                                    </td>
                                </tr>
                            </table>
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f1f5f9; border-radius: 8px; margin-top: 20px;">
                                <tr>
                                    <td style="padding: 15px;">
                                        <p style="font-size: 12px; color: #64748b; margin: 0 0 8px;">Jika tombol tidak berfungsi, copy link berikut:</p>
                                        <p style="font-size: 11px; color: #3b82f6; margin: 0; word-break: break-all;">{{ $verificationUrl }}</p>
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
