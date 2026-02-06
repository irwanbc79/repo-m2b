<div class="space-y-6">
    {{-- 1. Performa Layanan (Import/Export/Domestic) --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="text-lg font-semibold mb-4">📦 Performa Layanan</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-3">Layanan</th>
                        <th class="text-right p-3">Shipments</th>
                        <th class="text-right p-3">Revenue</th>
                        <th class="text-right p-3">Cost</th>
                        <th class="text-right p-3">Profit</th>
                        <th class="text-right p-3">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($servicePerformance ?? [] as $service)
                    @php $svc = is_array($service) ? (object)$service : $service; @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-medium
                                @if($svc->service_type == 'import') bg-blue-100 text-blue-700
                                @elseif($svc->service_type == 'export') bg-green-100 text-green-700
                                @elseif($svc->service_type == 'domestic') bg-yellow-100 text-yellow-700
                                @else bg-purple-100 text-purple-700 @endif">
                                {{ strtoupper($svc->service_type ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-medium">{{ number_format($svc->shipment_count ?? 0) }}</td>
                        <td class="p-3 text-right">Rp {{ number_format($svc->revenue ?? 0, 0, ',', '.') }}</td>
                        <td class="p-3 text-right text-gray-600">Rp {{ number_format($svc->cost ?? 0, 0, ',', '.') }}</td>
                        <td class="p-3 text-right {{ ($svc->profit ?? 0) >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                            Rp {{ number_format($svc->profit ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-right">
                            <span class="px-2 py-1 rounded text-xs font-medium
                                @if(($svc->margin ?? 0) >= 30) bg-green-100 text-green-700
                                @elseif(($svc->margin ?? 0) >= 15) bg-yellow-100 text-yellow-700
                                @else bg-red-100 text-red-700 @endif">
                                {{ number_format($svc->margin ?? 0, 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center p-4 text-gray-400">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 2. Performa Mode Pengiriman (Air/Sea/Land) --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="text-lg font-semibold mb-4">🚀 Performa Mode Pengiriman</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($shipmentTypePerformance ?? [] as $type)
            @php $tp = is_array($type) ? (object)$type : $type; @endphp
            <div class="border rounded-lg p-4 hover:shadow-md transition">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">
                        @if($tp->shipment_type == 'air') ✈️
                        @elseif($tp->shipment_type == 'sea') 🚢
                        @else 🚛 @endif
                    </span>
                    <span class="font-bold text-lg uppercase">{{ $tp->shipment_type ?? 'N/A' }}</span>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Shipments</span>
                        <span class="font-medium">{{ number_format($tp->shipment_count ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Revenue</span>
                        <span class="font-medium text-blue-600">Rp {{ number_format(($tp->revenue ?? 0)/1000000, 1) }} jt</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Cost</span>
                        <span class="font-medium">Rp {{ number_format(($tp->cost ?? 0)/1000000, 1) }} jt</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Profit</span>
                        <span class="font-medium {{ ($tp->profit ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Rp {{ number_format(($tp->profit ?? 0)/1000000, 1) }} jt
                        </span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t">
                        <span class="text-gray-500">Margin</span>
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if(($tp->margin ?? 0) >= 30) bg-green-100 text-green-700
                            @elseif(($tp->margin ?? 0) >= 15) bg-yellow-100 text-yellow-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ number_format($tp->margin ?? 0, 1) }}%
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-4 text-gray-400">Tidak ada data</div>
            @endforelse
        </div>
    </div>

    {{-- 3. Container Mode --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="text-lg font-semibold mb-4">📦 Container Mode</h3>
        @php $totalContainer = ($containerAnalysis ?? collect())->sum() ?: 1; @endphp
        <div class="space-y-3">
            @forelse($containerAnalysis ?? [] as $mode => $count)
            <div class="flex items-center gap-4">
                <div class="w-24 text-sm font-medium">{{ strtoupper($mode) }}</div>
                <div class="flex-1 bg-gray-100 rounded-full h-6 relative overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full transition-all" 
                         style="width: {{ ($count / $totalContainer) * 100 }}%"></div>
                    <span class="absolute inset-0 flex items-center justify-center text-xs font-medium">
                        {{ $count }}
                    </span>
                </div>
                <div class="w-16 text-right text-sm text-gray-500">
                    {{ number_format(($count / $totalContainer) * 100, 1) }}%
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-center">Tidak ada data</p>
            @endforelse
        </div>
    </div>

    {{-- 4. Top 10 Komoditas (HS Code) - ENHANCED --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="text-lg font-semibold mb-4">🏷️ Top 10 Komoditas (HS Code)</h3>
        <div class="space-y-3">
            @forelse($topCommodities ?? [] as $index => $item)
            @php $itm = is_array($item) ? (object)$item : $item; @endphp
            <div class="flex gap-4 p-4 rounded-lg hover:bg-gray-50 transition
                {{ $index < 3 ? 'bg-amber-50 border border-amber-200' : 'border' }}">
                
                {{-- Left: Rank Badge --}}
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $index == 0 ? 'bg-yellow-400 text-white' : '' }}
                        {{ $index == 1 ? 'bg-gray-300 text-gray-700' : '' }}
                        {{ $index == 2 ? 'bg-amber-600 text-white' : '' }}
                        {{ $index >= 3 ? 'bg-gray-100 text-gray-600' : '' }}">
                        {{ $index + 1 }}
                    </div>
                </div>
                
                {{-- Middle: HS Code & Description --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-sm font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">
                            {{ $itm->hs_code ?? 'N/A' }}
                        </span>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                            {{ $itm->count ?? 0 }}x shipment
                        </span>
                        @if(($itm->total_pieces ?? 0) > 0)
                        <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">
                            {{ number_format($itm->total_pieces) }} pcs
                        </span>
                        @endif
                    </div>
                    <div class="mt-1">
                        <p class="text-sm text-gray-800 font-medium" title="{{ $itm->description ?? '-' }}">
                            🇮🇩 {{ Str::limit($itm->description ?? '-', 80) }}
                        </p>
                        @if(!empty($itm->description_en))
                        <p class="text-xs text-gray-500" title="{{ $itm->description_en }}">
                            🇬🇧 {{ Str::limit($itm->description_en, 80) }}
                        </p>
                        @endif
                    </div>
                    
                    {{-- Commodity Names --}}
                    @if(!empty($itm->commodities) && count($itm->commodities) > 0)
                    <div class="mt-1">
                        <span class="text-xs text-gray-400">Barang:</span>
                        @foreach($itm->commodities as $comm)
                        <span class="text-xs text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded ml-1">{{ $comm }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                
                {{-- Right: Customer & Origin Info --}}
                <div class="flex-shrink-0 w-48 text-right space-y-1">
                    {{-- Customers --}}
                    @if(!empty($itm->customers) && count($itm->customers) > 0)
                    <div>
                        <span class="text-xs text-gray-400 block">👤 Customer:</span>
                        @foreach($itm->customers as $cust)
                        <span class="text-xs text-gray-700 block truncate" title="{{ $cust }}">{{ Str::limit($cust, 20) }}</span>
                        @endforeach
                    </div>
                    @endif
                    
                    {{-- Origins --}}
                    @if(!empty($itm->origins) && count($itm->origins) > 0)
                    <div class="mt-2">
                        <span class="text-xs text-gray-400 block">🌍 Asal:</span>
                        @foreach($itm->origins as $origin)
                        <span class="text-xs font-medium text-blue-600 block">{{ $origin }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-8">
                <div class="text-4xl mb-2">📭</div>
                <p class="text-gray-400">Tidak ada data HS Code pada periode ini</p>
                <p class="text-xs text-gray-300 mt-1">Pastikan shipment memiliki HS Code yang terisi</p>
            </div>
            @endforelse
        </div>
        
        @if(count($topCommodities ?? []) > 0)
        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
            <div class="flex items-start gap-2">
                <span class="text-blue-500">💡</span>
                <div class="text-xs text-blue-700">
                    <p class="font-medium">Insight Komoditas</p>
                    <p class="mt-1">Data berdasarkan HS Code di shipment. Menampilkan customer, jumlah barang, dan negara asal.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
