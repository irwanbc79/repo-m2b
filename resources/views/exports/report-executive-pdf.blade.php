<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Executive Summary</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { background: #1e293b; color: white; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #94a3b8; }
        .header .period { font-size: 12px; color: #e2e8f0; margin-top: 6px; }
        
        .section { margin: 15px 20px; }
        .section-title { font-size: 13px; font-weight: bold; color: #1e293b; border-bottom: 2px solid #3b82f6; padding-bottom: 4px; margin-bottom: 10px; }
        
        .kpi-grid { display: table; width: 100%; margin-bottom: 15px; }
        .kpi-row { display: table-row; }
        .kpi-card { display: table-cell; width: 25%; padding: 8px; text-align: center; }
        .kpi-card .value { font-size: 16px; font-weight: 900; }
        .kpi-card .label { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .kpi-card .sub { font-size: 8px; color: #94a3b8; margin-top: 2px; }
        
        .green { color: #16a34a; }
        .blue { color: #2563eb; }
        .red { color: #dc2626; }
        .orange { color: #ea580c; }
        .purple { color: #7c3aed; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; color: #475569; font-size: 10px; text-transform: uppercase; padding: 6px 8px; text-align: left; border-bottom: 2px solid #cbd5e1; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tr:nth-child(even) { background: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        
        .forecast-grid { display: table; width: 100%; }
        .forecast-item { display: table-cell; width: 25%; padding: 6px; text-align: center; border: 1px solid #e2e8f0; }
        .forecast-item .period-label { font-weight: bold; font-size: 11px; margin-bottom: 4px; }
        
        .footer { margin-top: 20px; padding: 10px 20px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>📊 M2B ADMIN — Laporan Executive Summary</h1>
        <p>Portal M2B Control Center</p>
        <div class="period">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }} &nbsp;|&nbsp; Digenerate: {{ $generatedAt }}</div>
    </div>

    {{-- KPI Cards --}}
    <div class="section">
        <div class="section-title">Key Performance Indicators</div>
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="label">Revenue (Dibayar)</div>
                    <div class="value green">Rp {{ number_format($kpi['revenue'] ?? 0, 0, ',', '.') }}</div>
                    @if(($kpi['revenue_change'] ?? 0) != 0)
                    <div class="sub">{{ ($kpi['revenue_change'] ?? 0) >= 0 ? '▲' : '▼' }} {{ abs($kpi['revenue_change'] ?? 0) }}% vs periode lalu</div>
                    @endif
                </div>
                <div class="kpi-card">
                    <div class="label">Gross Profit</div>
                    <div class="value blue">Rp {{ number_format($kpi['gross_profit'] ?? 0, 0, ',', '.') }}</div>
                    <div class="sub">Margin: {{ $kpi['margin'] ?? 0 }}%</div>
                </div>
                <div class="kpi-card">
                    <div class="label">Total Shipment</div>
                    <div class="value purple">{{ $kpi['shipments'] ?? 0 }}</div>
                    <div class="sub">{{ $kpi['completed'] ?? 0 }} selesai ({{ $kpi['completion_rate'] ?? 0 }}%)</div>
                </div>
                <div class="kpi-card">
                    <div class="label">Net Cash Flow</div>
                    <div class="value {{ ($kpi['net_cash'] ?? 0) >= 0 ? 'green' : 'red' }}">Rp {{ number_format($kpi['net_cash'] ?? 0, 0, ',', '.') }}</div>
                    <div class="sub">In: {{ number_format(($kpi['cash_in'] ?? 0)/1000000, 1) }}jt | Out: {{ number_format(($kpi['cash_out'] ?? 0)/1000000, 1) }}jt</div>
                </div>
            </div>
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="label">Piutang (AR)</div>
                    <div class="value red">Rp {{ number_format($kpi['ar_outstanding'] ?? 0, 0, ',', '.') }}</div>
                    <div class="sub">{{ $kpi['overdue_count'] ?? 0 }} overdue</div>
                </div>
                <div class="kpi-card">
                    <div class="label">Hutang (AP)</div>
                    <div class="value orange">Rp {{ number_format($kpi['ap_outstanding'] ?? 0, 0, ',', '.') }}</div>
                    <div class="sub">Belum dibayar ke vendor</div>
                </div>
                <div class="kpi-card">
                    <div class="label">Customer Aktif</div>
                    <div class="value">{{ $kpi['active_customers'] ?? 0 }}</div>
                </div>
                <div class="kpi-card">
                    <div class="label">Vendor Aktif</div>
                    <div class="value">{{ $kpi['active_vendors'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Trend --}}
    <div class="section">
        <div class="section-title">Trend 6 Bulan Terakhir</div>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Profit</th>
                    <th class="text-center">Shipment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $trend)
                <tr>
                    <td class="bold">{{ $trend['month'] }}</td>
                    <td class="text-right green">Rp {{ number_format($trend['revenue'], 0, ',', '.') }}</td>
                    <td class="text-right red">Rp {{ number_format($trend['cost'], 0, ',', '.') }}</td>
                    <td class="text-right bold {{ $trend['profit'] >= 0 ? 'blue' : 'red' }}">Rp {{ number_format($trend['profit'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $trend['shipments'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Cash Flow Forecast --}}
    <div class="section">
        <div class="section-title">Proyeksi Cash Flow</div>
        <table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th class="text-right">Estimasi Masuk</th>
                    <th class="text-right">Estimasi Keluar</th>
                    <th class="text-right">Net</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashFlowForecast as $f)
                <tr>
                    <td class="bold">{{ $f['label'] }} ke depan</td>
                    <td class="text-right green">Rp {{ number_format($f['cash_in'], 0, ',', '.') }}</td>
                    <td class="text-right red">Rp {{ number_format($f['cash_out'], 0, ',', '.') }}</td>
                    <td class="text-right bold {{ $f['net'] >= 0 ? 'green' : 'red' }}">Rp {{ number_format($f['net'], 0, ',', '.') }}</td>
                    <td class="text-center {{ $f['net'] >= 0 ? 'green' : 'red' }} bold">{{ $f['net'] >= 0 ? '✅ Surplus' : '⚠️ Defisit' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- AP Aging --}}
    <div class="section">
        <div class="section-title">Aging Hutang Vendor (AP)</div>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-center">Jumlah Bill</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>✅ Belum Jatuh Tempo</td>
                    <td class="text-center">{{ $apAging['current']->count ?? 0 }}</td>
                    <td class="text-right green">Rp {{ number_format($apAging['current']->total ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>⏳ 1-30 Hari Overdue</td>
                    <td class="text-center">{{ $apAging['overdue_30']->count ?? 0 }}</td>
                    <td class="text-right orange">Rp {{ number_format($apAging['overdue_30']->total ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>⚠️ 31-60 Hari Overdue</td>
                    <td class="text-center">{{ $apAging['overdue_60']->count ?? 0 }}</td>
                    <td class="text-right orange">Rp {{ number_format($apAging['overdue_60']->total ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>🔴 >60 Hari Overdue</td>
                    <td class="text-center">{{ $apAging['overdue_90']->count ?? 0 }}</td>
                    <td class="text-right red bold">Rp {{ number_format($apAging['overdue_90']->total ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr style="background: #f1f5f9; border-top: 2px solid #cbd5e1;">
                    <td class="bold">TOTAL</td>
                    <td class="text-center bold">{{ collect($apAging)->sum(fn($a) => $a->count ?? 0) }}</td>
                    <td class="text-right bold">Rp {{ number_format(collect($apAging)->sum(fn($a) => $a->total ?? 0), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Portal M2B — Laporan digenerate otomatis pada {{ $generatedAt }} &nbsp;|&nbsp; portal.m2b.co.id
    </div>
</body>
</html>
