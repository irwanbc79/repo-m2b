{{-- KPI Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    {{-- Revenue --}}
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-green-100 text-xs font-medium">💵 Revenue (Dibayar)</p>
                <p class="text-2xl font-black mt-1">Rp {{ number_format($kpi['revenue'] ?? 0, 0, ',', '.') }}</p>
            </div>
            @if(($kpi['revenue_change'] ?? 0) != 0)
                <span class="text-xs px-2 py-1 rounded-full {{ ($kpi['revenue_change'] ?? 0) >= 0 ? 'bg-green-400' : 'bg-red-400' }}">
                    {{ ($kpi['revenue_change'] ?? 0) >= 0 ? '▲' : '▼' }} {{ abs($kpi['revenue_change'] ?? 0) }}%
                </span>
            @endif
        </div>
        <p class="text-green-200 text-xs mt-2">vs periode sebelumnya</p>
    </div>

    {{-- Gross Profit --}}
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
        <p class="text-blue-100 text-xs font-medium">📈 Gross Profit</p>
        <p class="text-2xl font-black mt-1">Rp {{ number_format($kpi['gross_profit'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-blue-200 text-xs mt-2">Margin: {{ $kpi['margin'] ?? 0 }}%</p>
    </div>

    {{-- Shipments --}}
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-purple-100 text-xs font-medium">🚢 Total Shipment</p>
                <p class="text-2xl font-black mt-1">{{ $kpi['shipments'] ?? 0 }}</p>
            </div>
            @if(($kpi['shipments_change'] ?? 0) != 0)
                <span class="text-xs px-2 py-1 rounded-full {{ ($kpi['shipments_change'] ?? 0) >= 0 ? 'bg-purple-400' : 'bg-red-400' }}">
                    {{ ($kpi['shipments_change'] ?? 0) >= 0 ? '▲' : '▼' }} {{ abs($kpi['shipments_change'] ?? 0) }}%
                </span>
            @endif
        </div>
        <p class="text-purple-200 text-xs mt-2">{{ $kpi['completed'] ?? 0 }} selesai ({{ $kpi['completion_rate'] ?? 0 }}%)</p>
    </div>

    {{-- Net Cash Flow --}}
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white">
        <p class="text-amber-100 text-xs font-medium">💰 Net Cash Flow</p>
        <p class="text-2xl font-black mt-1 {{ ($kpi['net_cash'] ?? 0) < 0 ? 'text-red-200' : '' }}">
            Rp {{ number_format($kpi['net_cash'] ?? 0, 0, ',', '.') }}
        </p>
        <p class="text-amber-200 text-xs mt-2">In: {{ number_format(($kpi['cash_in'] ?? 0)/1000000, 1) }}jt | Out: {{ number_format(($kpi['cash_out'] ?? 0)/1000000, 1) }}jt</p>
    </div>
</div>

{{-- Second Row KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    {{-- AR Outstanding --}}
    <div class="bg-white border border-red-200 rounded-xl p-4">
        <p class="text-red-600 text-xs font-bold">📋 Piutang (AR)</p>
        <p class="text-xl font-black text-gray-800 mt-1">Rp {{ number_format($kpi['ar_outstanding'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-xs text-red-500 mt-1">⚠️ {{ $kpi['overdue_count'] ?? 0 }} overdue (Rp {{ number_format(($kpi['overdue_amount'] ?? 0)/1000000, 1) }}jt)</p>
    </div>

    {{-- AP Outstanding --}}
    <div class="bg-white border border-orange-200 rounded-xl p-4">
        <p class="text-orange-600 text-xs font-bold">📑 Hutang (AP)</p>
        <p class="text-xl font-black text-gray-800 mt-1">Rp {{ number_format($kpi['ap_outstanding'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-xs text-orange-500 mt-1">Belum dibayar ke vendor</p>
    </div>

    {{-- Active Customers --}}
    <div class="bg-white border border-blue-200 rounded-xl p-4">
        <p class="text-blue-600 text-xs font-bold">👥 Customer Aktif</p>
        <p class="text-xl font-black text-gray-800 mt-1">{{ $kpi['active_customers'] ?? 0 }}</p>
        <p class="text-xs text-blue-500 mt-1">Dalam periode ini</p>
    </div>

    {{-- Active Vendors --}}
    <div class="bg-white border border-green-200 rounded-xl p-4">
        <p class="text-green-600 text-xs font-bold">🏭 Vendor Aktif</p>
        <p class="text-xl font-black text-gray-800 mt-1">{{ $kpi['active_vendors'] ?? 0 }}</p>
        <p class="text-xs text-green-500 mt-1">Dalam periode ini</p>
    </div>
</div>

{{-- Monthly Trend Chart --}}
<div class="bg-white border rounded-xl p-6">
    <h3 class="font-bold text-gray-800 mb-4">📈 Trend 6 Bulan Terakhir</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-200">
                    <th class="text-left py-2 font-bold text-gray-600">Bulan</th>
                    <th class="text-right py-2 font-bold text-gray-600">Revenue</th>
                    <th class="text-right py-2 font-bold text-gray-600">Cost</th>
                    <th class="text-right py-2 font-bold text-gray-600">Profit</th>
                    <th class="text-right py-2 font-bold text-gray-600">Shipment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $trend)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 font-medium">{{ $trend['month'] }}</td>
                    <td class="py-3 text-right text-green-600">Rp {{ number_format($trend['revenue'], 0, ',', '.') }}</td>
                    <td class="py-3 text-right text-red-600">Rp {{ number_format($trend['cost'], 0, ',', '.') }}</td>
                    <td class="py-3 text-right font-bold {{ $trend['profit'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        Rp {{ number_format($trend['profit'], 0, ',', '.') }}
                    </td>
                    <td class="py-3 text-right">{{ $trend['shipments'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- Simple Bar Visualization --}}
    <div class="mt-6 space-y-2">
        <p class="text-xs font-bold text-gray-500 mb-2">REVENUE TREND</p>
        @php $maxRevenue = $monthlyTrend->max('revenue') ?: 1; @endphp
        @foreach($monthlyTrend as $trend)
        <div class="flex items-center gap-2">
            <span class="text-xs w-16 text-gray-500">{{ \Carbon\Carbon::parse('1 ' . $trend['month'])->format('M') }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                <div class="bg-gradient-to-r from-green-400 to-green-600 h-full rounded-full transition-all" 
                     style="width: {{ ($trend['revenue'] / $maxRevenue) * 100 }}%"></div>
            </div>
            <span class="text-xs w-24 text-right text-gray-600">{{ number_format($trend['revenue']/1000000, 1) }}jt</span>
        </div>
        @endforeach
    </div>
</div>
