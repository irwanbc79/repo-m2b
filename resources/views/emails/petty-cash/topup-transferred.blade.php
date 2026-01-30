<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1e40af; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .amount { font-size: 24px; font-weight: bold; color: #1e40af; }
        .balance { font-size: 20px; font-weight: bold; color: #059669; }
        .footer { text-align: center; padding: 15px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>💸 Dana Kas Kecil Sudah Ditransfer</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $fund->holder->name ?? 'Pemegang Kas' }}</strong>,</p>
            <p>Dana top up kas kecil sudah ditransfer!</p>
            
            <div class="info-box">
                <table width="100%">
                    <tr><td><strong>No. Top Up:</strong></td><td>{{ $topup->topup_number }}</td></tr>
                    <tr><td><strong>Tanggal Transfer:</strong></td><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
                </table>
                <hr style="margin: 15px 0;">
                <p style="margin: 0;">Jumlah Transfer:</p>
                <p class="amount">Rp {{ number_format($topup->amount_approved, 0, ',', '.') }}</p>
                <hr style="margin: 15px 0;">
                <p style="margin: 0;">Saldo Kas Kecil Sekarang:</p>
                <p class="balance">Rp {{ number_format($fund->current_balance, 0, ',', '.') }}</p>
            </div>
            
            <p>Silakan cek saldo kas kecil Anda di portal.</p>
        </div>
        <div class="footer">
            <p>Email ini dikirim otomatis dari Portal M2B</p>
        </div>
    </div>
</body>
</html>
