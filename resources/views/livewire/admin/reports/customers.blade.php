<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
            <p class="text-blue-600 text-xs font-bold">👥 TOTAL CUSTOMER</p>
            <p class="text-3xl font-black text-blue-700">{{ $totalCustomers }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-green-600 text-xs font-bold">🆕 CUSTOMER BARU</p>
            <p class="text-3xl font-black text-green-700">{{ $newCustomers }}</p>
            <p class="text-xs text-green-500">Dalam periode ini</p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-center">
            <p class="text-purple-600 text-xs font-bold">🔄 CUSTOMER REPEAT</p>
            <p class="text-3xl font-black text-purple-700">{{ $returningCustomers }}</p>
            <p class="text-xs text-purple-500">Dalam periode ini</p>
        </div>
    </div>

    {{-- Customer Performance Table --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="font-bold text-gray-800 mb-4">🏆 Customer Performance</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-3">#</th>
                        <th class="text-left py-3">Customer</th>
                        <th class="text-center py-3">Shipments</th>
                        <th class="text-right py-3">Revenue</th>
                        <th class="text-right py-3">Dibayar</th>
                        <th class="text-right py-3">Outstanding</th>
                        <th class="text-center py-3">Terms</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customerPerformance as $index => $customer)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3">
                            <span class="w-6 h-6 inline-flex items-center justify-center rounded-full text-xs font-bold {{ $index < 3 ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-100 text-gray-600' }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="py-3">
                            <p class="font-medium text-gray-800">{{ $customer['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $customer['code'] }}</p>
                        </td>
                        <td class="py-3 text-center font-bold">{{ $customer['shipments'] }}</td>
                        <td class="py-3 text-right text-green-600 font-medium">Rp {{ number_format($customer['revenue'], 0, ',', '.') }}</td>
                        <td class="py-3 text-right text-blue-600">Rp {{ number_format($customer['paid'], 0, ',', '.') }}</td>
                        <td class="py-3 text-right {{ $customer['outstanding'] > 0 ? 'text-red-600 font-bold' : 'text-gray-400' }}">
                            Rp {{ number_format($customer['outstanding'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-center">
                            <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ $customer['payment_terms'] }} hari</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">Tidak ada data customer aktif</td></tr>
                    @endforelse
                </tbody>
                @if($customerPerformance->count() > 0)
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="2" class="py-3 text-right">TOTAL:</td>
                        <td class="py-3 text-center">{{ $customerPerformance->sum('shipments') }}</td>
                        <td class="py-3 text-right text-green-700">Rp {{ number_format($customerPerformance->sum('revenue'), 0, ',', '.') }}</td>
                        <td class="py-3 text-right text-blue-700">Rp {{ number_format($customerPerformance->sum('paid'), 0, ',', '.') }}</td>
                        <td class="py-3 text-right text-red-700">Rp {{ number_format($customerPerformance->sum('outstanding'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Visual Bar Chart --}}
    @if($customerPerformance->count() > 0)
    <div class="bg-white border rounded-xl p-6">
        <h3 class="font-bold text-gray-800 mb-4">📊 Revenue by Customer</h3>
        @php $maxRevenue = $customerPerformance->max('revenue') ?: 1; @endphp
        <div class="space-y-3">
            @foreach($customerPerformance->take(10) as $customer)
            <div class="flex items-center gap-3">
                <span class="w-32 text-sm text-gray-600 truncate">{{ $customer['name'] }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-full rounded-full flex items-center justify-end pr-2 transition-all" 
                         style="width: {{ max(($customer['revenue'] / $maxRevenue) * 100, 5) }}%">
                        <span class="text-white text-xs font-bold">{{ number_format($customer['revenue']/1000000, 1) }}jt</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
