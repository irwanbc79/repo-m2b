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
        .amount { font-size: 24px; font-weight: bold; color: #059669; }
        .btn { display: inline-block; padding: 12px 24px; background: #1e40af; color: white; text-decoration: none; border-radius: 6px; margin-top: 15px; }
        .footer { text-align: center; padding: 15px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>💰 Request Top Up Kas Kecil</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $fund->approver->name ?? 'Approver' }}</strong>,</p>
            <p>Ada permintaan top up kas kecil yang memerlukan persetujuan Anda:</p>
            
            <div class="info-box">
                <table width="100%">
                    <tr><td><strong>No. Request:</strong></td><td>{{ $topup->topup_number }}</td></tr>
                    <tr><td><strong>Dari:</strong></td><td>{{ $requester->name }}</td></tr>
                    <tr><td><strong>Tanggal:</strong></td><td>{{ $topup->created_at->format('d/m/Y H:i') }}</td></tr>
                    <tr><td><strong>Saldo Saat Ini:</strong></td><td>Rp {{ number_format($topup->balance_before, 0, ',', '.') }}</td></tr>
                </table>
                <hr style="margin: 15px 0;">
                <p style="margin: 0;">Jumlah Request:</p>
                <p class="amount">Rp {{ number_format($topup->amount_requested, 0, ',', '.') }}</p>
                @if($topup->notes)
                <p><strong>Catatan:</strong> {{ $topup->notes }}</p>
                @endif
            </div>
            
            <center>
                <a href="{{ url('/admin/kas-kecil') }}" class="btn">Lihat & Approve</a>
            </center>
        </div>
        <div class="footer">
            <p>Email ini dikirim otomatis dari Portal M2B</p>
        </div>
    </div>
</body>
</html>
