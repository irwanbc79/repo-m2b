<style>
.shipment-cancelled {
    background-color: #f3f4f6 !important;
    opacity: 0.7;
}
.shipment-cancelled:hover {
    background-color: #e5e7eb !important;
}
</style>
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">📦 Manage Shipments</h1>
        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            ➕ Create Shipment
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
            <span>✅ {{ session('success') }}</span>
            <button wire:click="$set('success', null)" class="text-green-700 hover:text-green-900">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between">
            <span>❌ {{ session('error') }}</span>
            <button wire:click="$set('error', null)" class="text-red-700 hover:text-red-900">&times;</button>
        </div>
    @endif

    <!-- Filters & Tabs -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <!-- Status Tabs -->
        <div class="flex space-x-2 mb-4 overflow-x-auto">
            <button wire:click="$set('statusFilter', 'all')" 
                class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors
                    {{ $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                📋 Semua ({{ $statusCounts['all'] }})
            </button>
            <button wire:click="$set('statusFilter', 'pending')" 
                class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors
                    {{ $statusFilter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                ⏳ Belum Ada ({{ $statusCounts['pending'] }})
            </button>
            <button wire:click="$set('statusFilter', 'in_progress')" 
                class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors
                    {{ $statusFilter === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                🔄 Progress ({{ $statusCounts['in_progress'] }})
            </button>
            <button wire:click="$set('statusFilter', 'completed')" 
                class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors
                    {{ $statusFilter === 'completed' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                ✅ Completed ({{ $statusCounts['completed'] }})
            </button>
            <button wire:click="$set('statusFilter', 'cancel')" 
                class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors
                    {{ $statusFilter === 'cancel' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                ❌ Dibatalkan ({{ $statusCounts['cancel'] }})
            </button>
        </div>

        <!-- Search & Bulk Actions -->
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="🔍 Cari AWB, BL Number, atau Customer..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            @if(count($selectedShipments) > 0)
                <button wire:click="bulkCancel" 
                    wire:confirm="Apakah Anda yakin ingin membatalkan {{ count($selectedShipments) }} shipment yang dipilih?"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium whitespace-nowrap">
                    🚫 Batalkan ({{ count($selectedShipments) }})
                </button>
            @endif
        </div>
    </div>

    <!-- Shipments Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" 
                                wire:model.live="selectAll"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reference No
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Transport
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Route
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($shipments as $shipment)
                        <tr class="hover:bg-gray-50 transition-colors
                            {{ $shipment->isCancelled() ? 'bg-gray-100 opacity-60' : '' }}">
                            <td class="px-4 py-4">
                                @if(!$shipment->isCancelled())
                                    <input type="checkbox" 
                                        wire:model.live="selectedShipments" 
                                        value="{{ $shipment->id }}"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                @else
                                    <span class="text-gray-400">🚫</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($shipment->isCancelled())
                                        <span class="text-red-500 mr-2">❌</span>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $shipment->awb_number ?? $shipment->bl_number }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $shipment->created_at->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900">{{ $shipment->customer->company_name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                    {{ strtoupper($shipment->shipment_type ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900">
                                    {{ $shipment->origin }} → {{ $shipment->destination }}
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                {!! $shipment->status_badge !!}
                                @if($shipment->isCancelled() && $shipment->cancelled_at)
                                    <div class="text-xs text-gray-500 mt-1">
                                        Dibatalkan: {{ $shipment->cancelled_at->format('d M Y H:i') }}
                                        @if($shipment->cancelledBy)
                                            <br>oleh: {{ $shipment->cancelledBy->name }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    @if($shipment->canBeEdited())
                                        <button class="text-blue-600 hover:text-blue-900 font-medium">
                                            👁️ View
                                        </button>
                                        <button class="text-green-600 hover:text-green-900 font-medium">
                                            ✏️ Edit
                                        </button>
                                        <button wire:click="openCancelModal({{ $shipment->id }})" 
                                            class="text-red-600 hover:text-red-900 font-medium">
                                            🚫 Cancel
                                        </button>
                                    @else
                                        <button disabled class="text-gray-400 cursor-not-allowed font-medium">
                                            👁️ View Only
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="text-4xl mb-2">📭</div>
                                <div class="text-lg font-medium">Tidak ada shipment ditemukan</div>
                                <div class="text-sm">Coba ubah filter atau pencarian Anda</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $shipments->links() }}
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    @if($showCancelModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        🚫 Batalkan Shipment
                    </h3>
                    
                    <p class="text-gray-600 mb-4">
                        Apakah Anda yakin ingin membatalkan shipment ini? 
                        Shipment yang dibatalkan tidak dapat diedit lagi.
                    </p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Alasan Pembatalan (opsional)
                        </label>
                        <textarea 
                            wire:model="cancellationReason"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            placeholder="Tulis alasan pembatalan..."></textarea>
                    </div>

                    <div class="flex space-x-3">
                        <button 
                            wire:click="closeCancelModal"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">
                            Batal
                        </button>
                        <button 
                            wire:click="confirmCancel"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                            Ya, Batalkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
