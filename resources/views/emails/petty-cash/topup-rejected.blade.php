<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .reason { background: #fef2f2; padding: 10px; border-left: 4px solid #dc2626; margin: 15px 0; }
        .footer { text-align: center; padding: 15px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>❌ Top Up Kas Kecil Ditolak</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $topup->requester->name ?? 'User' }}</strong>,</p>
            <p>Mohon maaf, request top up kas kecil Anda <strong style="color: #dc2626;">DITOLAK</strong>.</p>
            
            <div class="info-box">
                <table width="100%">
                    <tr><td><strong>No. Request:</strong></td><td>{{ $topup->topup_number }}</td></tr>
                    <tr><td><strong>Jumlah Request:</strong></td><td>Rp {{ number_format($topup->amount_requested, 0, ',', '.') }}</td></tr>
                    <tr><td><strong>Ditolak Oleh:</strong></td><td>{{ $rejector->name }}</td></tr>
                </table>
            </div>
            
            <div class="reason">
                <strong>Alasan Penolakan:</strong><br>
                {{ $topup->reject_reason ?? 'Tidak ada alasan yang diberikan' }}
            </div>
            
            <p>Silakan hubungi approver untuk informasi lebih lanjut atau ajukan request baru.</p>
        </div>
        <div class="footer">
            <p>Email ini dikirim otomatis dari Portal M2B</p>
        </div>
    </div>
</body>
</html>
