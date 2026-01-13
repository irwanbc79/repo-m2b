<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $transaction['journal']['journal_number'] ?? 'N/A' }}</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 10pt;
            color: #666;
        }
        
        .receipt-title {
            font-size: 18pt;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            color: #1e40af;
        }
        
        .receipt-info {
            margin: 20px 0;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 200px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .amount-box {
            background: #f0f9ff;
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }
        
        .amount-label {
            font-size: 12pt;
            color: #666;
            margin-bottom: 10px;
        }
        
        .amount-value {
            font-size: 28pt;
            font-weight: bold;
            color: #2563eb;
        }
        
        .journal-details {
            margin: 30px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9fafb;
        }
        
        .journal-title {
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 15px;
            color: #1e40af;
        }
        
        .journal-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .journal-table th {
            background: #e5e7eb;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #d1d5db;
        }
        
        .journal-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .text-right { text-align: right; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        
        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            text-align: center;
            width: 45%;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 80px;
            padding-top: 10px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9pt;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .print-button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14pt;
            border-radius: 8px;
            cursor: pointer;
            margin: 20px auto;
            display: block;
        }
        
        .print-button:hover {
            background: #1e40af;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10pt;
        }
        
        .status-posted {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-draft {
            background: #fef3c7;
            color: #d97706;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print Receipt</button>
    
    <div class="receipt-header">
        <div class="company-name">M2B LOGISTICS</div>
        <div class="company-details">
            Jl. Contoh No. 123, Jakarta<br>
            Tel: (021) 1234-5678 | Email: info@m2b.co.id
        </div>
    </div>
    
    <div class="receipt-title">
        {{ $transaction['type'] === 'in' ? '💰 BUKTI PENERIMAAN KAS' : '💸 BUKTI PENGELUARAN KAS' }}
    </div>
    
    <div class="receipt-info">
        <div class="info-row">
            <div class="info-label">Nomor Jurnal:</div>
            <div class="info-value">{{ $transaction['journal']['journal_number'] ?? 'N/A' }}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Tanggal Transaksi:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('d/m/Y') }}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Tipe Transaksi:</div>
            <div class="info-value">{{ $transaction['type'] === 'in' ? 'Penerimaan Kas (Cash In)' : 'Pengeluaran Kas (Cash Out)' }}</div>
        </div>
        
        @if(isset($transaction['customer']) && $transaction['customer'])
        <div class="info-row">
            <div class="info-label">Customer:</div>
            <div class="info-value">{{ $transaction['customer']['company_name'] ?? $transaction['customer']['name'] ?? 'N/A' }}</div>
        </div>
        @endif
        
        @if(isset($transaction['vendor']) && $transaction['vendor'])
        <div class="info-row">
            <div class="info-label">Vendor:</div>
            <div class="info-value">{{ $transaction['vendor']['name'] ?? 'N/A' }}</div>
        </div>
        @endif
        
        @if(isset($transaction['shipment']) && $transaction['shipment'])
        <div class="info-row">
            <div class="info-label">Shipment AWB:</div>
            <div class="info-value">{{ $transaction['shipment']['awb_number'] ?? 'N/A' }}</div>
        </div>
        @endif
        
        <div class="info-row">
            <div class="info-label">Keterangan:</div>
            <div class="info-value">{{ $transaction['description'] ?? '-' }}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value">
                <span class="status-badge {{ ($transaction['journal']['status'] ?? 'draft') === 'posted' ? 'status-posted' : 'status-draft' }}">
                    {{ ($transaction['journal']['status'] ?? 'draft') === 'posted' ? '✓ Posted' : '⏳ Draft' }}
                </span>
            </div>
        </div>
    </div>
    
    <div class="amount-box">
        <div class="amount-label">Total Jumlah</div>
        <div class="amount-value {{ $transaction['type'] === 'in' ? 'text-green' : 'text-red' }}">
            {{ $transaction['currency'] ?? 'IDR' }} {{ number_format($transaction['amount'], 0, ',', '.') }}
        </div>
    </div>
    
    @if(isset($transaction['journal']['items']) && count($transaction['journal']['items']) > 0)
    <div class="journal-details">
        <div class="journal-title">Jurnal Akuntansi</div>
        <table class="journal-table">
            <thead>
                <tr>
                    <th>Akun</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction['journal']['items'] as $item)
                <tr>
                    <td>{{ $item['account']['name'] ?? 'N/A' }}</td>
                    <td class="text-right">{{ $item['debit'] > 0 ? number_format($item['debit'], 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $item['credit'] > 0 ? number_format($item['credit'], 0, ',', '.') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <div class="signature-section">
        <div class="signature-box">
            <div>Dibuat Oleh:</div>
            <div class="signature-line">
                {{ $transaction['creator']['name'] ?? 'N/A' }}<br>
                {{ \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') }}
            </div>
        </div>
        
        <div class="signature-box">
            <div>Disetujui Oleh:</div>
            <div class="signature-line">
                @if(($transaction['journal']['status'] ?? 'draft') === 'posted' && isset($transaction['journal']['approved_by']))
                    {{ $transaction['journal']['approver']['name'] ?? 'N/A' }}<br>
                    {{ \Carbon\Carbon::parse($transaction['journal']['approved_at'])->format('d/m/Y H:i') }}
                @else
                    <br><br>
                @endif
            </div>
        </div>
    </div>
    
    <div class="footer">
        Dokumen ini dicetak secara otomatis dari Simple Cashier<br>
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
