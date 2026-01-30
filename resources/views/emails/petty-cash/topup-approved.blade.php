<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .amount { font-size: 24px; font-weight: bold; color: #059669; }
        .footer { text-align: center; padding: 15px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Top Up Kas Kecil Disetujui</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $fund->holder->name ?? 'Pemegang Kas' }}</strong>,</p>
            <p>Request top up kas kecil Anda telah <strong style="color: #059669;">DISETUJUI</strong>!</p>
            
            <div class="info-box">
                <table width="100%">
                    <tr><td><strong>No. Request:</strong></td><td>{{ $topup->topup_number }}</td></tr>
                    <tr><td><strong>Disetujui Oleh:</strong></td><td>{{ $approver->name }}</td></tr>
                    <tr><td><strong>Tanggal Approve:</strong></td><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
                </table>
                <hr style="margin: 15px 0;">
                <p style="margin: 0;">Jumlah Disetujui:</p>
                <p class="amount">Rp {{ number_format($topup->amount_approved ?? $topup->amount_requested, 0, ',', '.') }}</p>
            </div>
            
            <p>Dana akan segera ditransfer ke Anda. Harap tunggu konfirmasi transfer.</p>
        </div>
        <div class="footer">
            <p>Email ini dikirim otomatis dari Portal M2B</p>
        </div>
    </div>
</body>
</html>
