<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 border rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs font-bold">📦 TOTAL</p>
            <p class="text-3xl font-black text-gray-800">{{ $summary['total_count'] ?? 0 }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-green-600 text-xs font-bold">✅ COMPLETED</p>
            <p class="text-3xl font-black text-green-700">{{ $summary['completed_count'] ?? 0 }}</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
            <p class="text-blue-600 text-xs font-bold">🔄 ACTIVE</p>
            <p class="text-3xl font-black text-blue-700">{{ $summary['active_count'] ?? 0 }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <p class="text-red-600 text-xs font-bold">❌ CANCELLED</p>
            <p class="text-3xl font-black text-red-700">{{ $summary['cancelled_count'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white border rounded-xl p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">STATUS</label>
                <select wire:model.live="status" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="">Semua</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="on_board">On Board</option>
                    <option value="customs_released">Customs Released</option>
                    <option value="completed">Completed</option>
                    <option value="cancel">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">SERVICE</label>
                <select wire:model.live="serviceType" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="">Semua</option>
                    <option value="import">Import</option>
                    <option value="export">Export</option>
                    <option value="domestic">Domestic</option>
                    <option value="cross_border">Cross Border</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">MODE</label>
                <select wire:model.live="shipmentType" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="">Semua</option>
                    <option value="air">Air</option>
                    <option value="sea">Sea</option>
                    <option value="land">Land</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">CUSTOMER</label>
                <select wire:model.live="customerId" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="">Semua</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        {{-- By Status --}}
        <div class="bg-white border rounded-xl p-4">
            <h4 class="font-bold text-gray-700 mb-3 text-sm">📊 By Status</h4>
            @foreach($byStatus as $status => $count)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm capitalize">{{ str_replace('_', ' ', $status) }}</span>
                <span class="font-bold">{{ $count }}</span>
            </div>
            @endforeach
        </div>

        {{-- By Service Type --}}
        <div class="bg-white border rounded-xl p-4">
            <h4 class="font-bold text-gray-700 mb-3 text-sm">🚢 By Service</h4>
            @foreach($byService as $service => $count)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm capitalize">{{ $service }}</span>
                <span class="font-bold">{{ $count }}</span>
            </div>
            @endforeach
        </div>

        {{-- By Shipment Type --}}
        <div class="bg-white border rounded-xl p-4">
            <h4 class="font-bold text-gray-700 mb-3 text-sm">✈️ By Mode</h4>
            @foreach($byShipmentType as $type => $count)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm capitalize flex items-center gap-2">
                    @if($type == 'air') ✈️ @elseif($type == 'sea') 🚢 @else 🚛 @endif
                    {{ ucfirst($type) }}
                </span>
                <span class="font-bold">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top Routes --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="font-bold text-gray-800 mb-4">🗺️ Top 10 Rute Pengiriman</h3>
        <div class="grid md:grid-cols-2 gap-4">
            @forelse($topRoutes as $index => $route)
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <span class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold {{ $index < 3 ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                    {{ $index + 1 }}
                </span>
                <div class="flex-1">
                    <p class="font-medium text-gray-800">{{ $route->origin }} → {{ $route->destination }}</p>
                </div>
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-bold">{{ $route->count }}x</span>
            </div>
            @empty
            <p class="text-gray-500 col-span-2 text-center py-4">Tidak ada data</p>
            @endforelse
        </div>
    </div>

    {{-- Shipment List --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="font-bold text-gray-800 mb-4">📋 Daftar Shipment ({{ $shipments->count() }})</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-2">Tanggal</th>
                        <th class="text-left py-2">AWB/Ref</th>
                        <th class="text-left py-2">Customer</th>
                        <th class="text-left py-2">Rute</th>
                        <th class="text-center py-2">Mode</th>
                        <th class="text-center py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipments->take(20) as $shipment)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-2">{{ $shipment->created_at->format('d/m/y') }}</td>
                        <td class="py-2 font-mono font-bold text-blue-600">{{ $shipment->awb_number }}</td>
                        <td class="py-2">{{ $shipment->customer->company_name ?? '-' }}</td>
                        <td class="py-2">{{ $shipment->origin }} → {{ $shipment->destination }}</td>
                        <td class="py-2 text-center">
                            @if($shipment->shipment_type == 'air') ✈️
                            @elseif($shipment->shipment_type == 'sea') 🚢
                            @else 🚛 @endif
                        </td>
                        <td class="py-2 text-center">
                            <span class="px-2 py-1 rounded text-xs font-bold
                                @if($shipment->status == 'completed') bg-green-100 text-green-700
                                @elseif($shipment->status == 'cancel') bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ strtoupper(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($shipments->count() > 20)
            <p class="text-center text-gray-500 text-xs mt-4">Menampilkan 20 dari {{ $shipments->count() }} data</p>
            @endif
        </div>
    </div>
</div>
