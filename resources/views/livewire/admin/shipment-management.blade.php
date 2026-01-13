<div class="space-y-6">
    @section("header", "Manage Shipments")

    {{-- Toast Notifications --}}
    @if (session()->has("message"))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-2" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        {{ session("message") }}
        <button @click="show = false" class="ml-auto">&times;</button>
    </div>
    @endif

    @if (session()->has("error"))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-2" x-data="{ show: true }" x-show="show">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        {{ session("error") }}
        <button @click="show = false" class="ml-auto">&times;</button>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-black text-gray-800">{{ $stats["total"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 shadow-sm border border-yellow-100 text-center">
            <p class="text-2xl font-black text-yellow-600">{{ $stats["pending"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Pending</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 shadow-sm border border-blue-100 text-center">
            <p class="text-2xl font-black text-blue-600">{{ $stats["in_progress"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">In Progress</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 shadow-sm border border-purple-100 text-center">
            <p class="text-2xl font-black text-purple-600">{{ $stats["in_transit"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">In Transit</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 shadow-sm border border-green-100 text-center">
            <p class="text-2xl font-black text-green-600">{{ $stats["completed"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Completed</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4 shadow-sm border border-indigo-100 text-center">
            <p class="text-2xl font-black text-indigo-600">{{ $stats["this_month"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">This Month</p>
        </div>
        <div class="bg-orange-50 rounded-xl p-4 shadow-sm border border-orange-100 text-center">
            <p class="text-2xl font-black text-orange-600">{{ number_format(($stats["total_weight"] ?? 0) / 1000, 2) }}</p>
            <p class="text-xs text-gray-500">Total Ton</p>
        </div>
        <div class="bg-teal-50 rounded-xl p-4 shadow-sm border border-teal-100 text-center">
            <p class="text-2xl font-black text-teal-600">{{ number_format($stats["total_volume"] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500">Total CBM</p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Header with Search & Filters --}}
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                {{-- Search --}}
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari AWB, referensi, customer..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-3">
                    <select wire:model.live="filterStatus" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="in_transit">In Transit</option>
                        <option value="completed">Completed</option>
                        <option value="cancel">Cancelled</option>
                    </select>

                    <select wire:model.live="filterShipmentType" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Shipment Type</option>
                        <option value="air">✈️ Air</option>
                        <option value="sea">🚢 Sea</option>
                        <option value="land">🚛 Land</option>
                    </select>


                    <select wire:model.live="filterServiceType" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Service Type</option>
                        <option value="import">📥 Import</option>
                        <option value="export">📤 Export</option>
                        <option value="domestic">🏠 Domestic</option>
                    </select>
                    <select wire:model.live="perPage" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>

                    @if(count($selectedShipments ?? []) > 0)
                    <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-xl">
                        <span class="text-sm text-blue-700 font-medium">{{ count($selectedShipments) }} dipilih</span>
                        <select wire:model="bulkStatus" class="text-sm border-0 bg-transparent focus:ring-0 text-blue-700">
                            <option value="">Ubah Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="in_transit">In Transit</option>
                            <option value="completed">Completed</option>
                        </select>
                        <button wire:click="bulkUpdateStatus" class="text-xs bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700">Apply</button>
                    </div>
                    @endif

                    <button wire:click="exportExcel" class="flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>

                    <button wire:click="create" class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Shipment
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th wire:click="sortBy('created_at')" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">Reference @if($sortField === 'created_at')<svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>@endif</div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Route</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Cargo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Docs</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shipments as $shipment)
                    <tr class="hover:bg-gray-50 transition-colors {{ in_array($shipment->id, $selectedShipments ?? []) ? 'bg-blue-50' : '' }}" {{ $shipment->status === 'cancel' ? 'bg-gray-100 opacity-60' : '' }}>
                        <td class="px-4 py-3">
                            <input type="checkbox" wire:model.live="selectedShipments" value="{{ $shipment->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">{{ $shipment->awb_number ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-400">{{ $shipment->created_at->format('d M Y') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">{{ $shipment->customer->company_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $shipment->customer->customer_code ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-lg {{ $shipment->shipment_type === 'air' ? 'bg-sky-100 text-sky-700' : ($shipment->shipment_type === 'sea' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">{{ strtoupper($shipment->shipment_type ?? 'N/A') }}</span>
                            <p class="text-xs text-gray-600 mt-1">{{ $shipment->origin ?? '-' }} → {{ $shipment->destination ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-800">{{ number_format($shipment->weight ?? 0, 0) }} Kg</p>
                            <p class="text-xs text-gray-500">{{ number_format($shipment->volume ?? 0, 3) }} CBM</p>
                            <p class="text-xs text-gray-400">{{ $shipment->pieces ?? 0 }} pcs</p>
                        </td>
                        <td class="px-4 py-3">
                            @php $docCount = $shipment->documents->count() ?? 0; @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg {{ $docCount > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $docCount }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'in_progress' => 'bg-blue-100 text-blue-700', 'in_transit' => 'bg-purple-100 text-purple-700', 'completed' => 'bg-green-100 text-green-700', 'cancel' => 'bg-red-100 text-red-700'];
                            @endphp
                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$shipment->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst(str_replace('_', ' ', $shipment->status ?? 'N/A')) }}</span>
                            @if($shipment->status !== 'pending' && $shipment->lane_status)
                            <div class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold mt-1 w-fit {{ $shipment->lane_status == 'green' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                <span>{{ $shipment->lane_status == 'green' ? '🟩' : '🟥' }}</span>
                                <span>{{ $shipment->lane_status == 'green' ? 'Jalur Hijau' : 'Jalur Merah' }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="quickView({{ $shipment->id }})" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Quick View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <a href="{{ url('/admin/shipments/' . $shipment->id) }}" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </a>
                                <button wire:click="edit({{ $shipment->id }})" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="openPrintDoModal({{ $shipment->id }})" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg" title="Print DO">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </button>
                                <button wire:click="confirmDelete({{ $shipment->id }})" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            <button wire:click="openCancelModal({{ $shipment->id }})" 
                                class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" 
                                title="Batalkan Shipment"
                                @if($shipment->status === 'cancel') disabled class="opacity-50 cursor-not-allowed" @endif>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <p class="text-gray-500 font-medium">Tidak ada data shipment</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <p class="text-sm text-gray-600">Menampilkan {{ $shipments->firstItem() ?? 0 }} - {{ $shipments->lastItem() ?? 0 }} dari {{ $shipments->total() ?? 0 }} shipment</p>
                {{ $shipments->links() }}
            </div>
        </div>
    </div>

    {{-- Quick View Modal --}}
    @if($showQuickView && $quickViewShipment)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showQuickView', false)">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Quick View Shipment</h3>
                    <p class="text-sm text-gray-500">{{ $quickViewShipment->awb_number ?? 'N/A' }}</p>
                </div>
                <button wire:click="$set('showQuickView', false)" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-6">
                {{-- Status Badge --}}
                <div class="flex items-center gap-3">
                    @php
                        $statusColors = ['pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200', 'in_progress' => 'bg-blue-100 text-blue-700 border-blue-200', 'in_transit' => 'bg-purple-100 text-purple-700 border-purple-200', 'completed' => 'bg-green-100 text-green-700 border-green-200', 'cancel' => 'bg-red-100 text-red-700 border-red-200'];
                    @endphp
                    <span class="px-4 py-2 text-sm font-semibold rounded-full border {{ $statusColors[$quickViewShipment->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst(str_replace('_', ' ', $quickViewShipment->status ?? 'N/A')) }}</span>
                    <span class="px-3 py-1 text-xs font-semibold rounded-lg {{ $quickViewShipment->shipment_type === 'air' ? 'bg-sky-100 text-sky-700' : ($quickViewShipment->shipment_type === 'sea' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">{{ strtoupper($quickViewShipment->shipment_type ?? 'N/A') }}</span>
                </div>

                {{-- Info Grid --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Customer</p>
                        <p class="font-semibold text-gray-800">{{ $quickViewShipment->customer->company_name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-500">{{ $quickViewShipment->customer->customer_code ?? '' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">AWB Number</p>
                        <p class="font-semibold text-gray-800">{{ $quickViewShipment->awb_number ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Origin</p>
                        <p class="font-semibold text-gray-800">{{ $quickViewShipment->origin ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Destination</p>
                        <p class="font-semibold text-gray-800">{{ $quickViewShipment->destination ?? '-' }}</p>
                    </div>
                </div>

                {{-- Cargo Info --}}
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-3">Cargo Information</p>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($quickViewShipment->weight ?? 0, 0) }}</p>
                            <p class="text-xs text-gray-500">Kg</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-indigo-600">{{ number_format($quickViewShipment->volume ?? 0, 3) }}</p>
                            <p class="text-xs text-gray-500">CBM</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-purple-600">{{ $quickViewShipment->pieces ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Pieces</p>
                        </div>
                    </div>
                </div>

                {{-- Additional Info --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><p class="text-gray-500">Commodity</p><p class="font-medium text-gray-800">{{ $quickViewShipment->commodity ?? '-' }}</p></div>
                    <div><p class="text-gray-500">Package Type</p><p class="font-medium text-gray-800">{{ $quickViewShipment->package_type ?? '-' }}</p></div>
                    <div><p class="text-gray-500">Created</p><p class="font-medium text-gray-800">{{ $quickViewShipment->created_at->format('d M Y, H:i') }}</p></div>
                    <div><p class="text-gray-500">Documents</p><p class="font-medium text-gray-800">{{ $quickViewShipment->documents?->count() ?? 0 ?? 0 }} files</p></div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('admin.shipments.show', $quickViewShipment->id) }}" class="flex-1 py-2.5 bg-blue-600 text-white text-center rounded-xl font-semibold hover:bg-blue-700">Lihat Detail</a>
                    <button wire:click="edit({{ $quickViewShipment->id }})" class="flex-1 py-2.5 border border-gray-300 text-gray-700 text-center rounded-xl font-semibold hover:bg-gray-50">Edit</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Shipment?</h3>
            <p class="text-gray-600 mb-6">Data shipment dan dokumen terkait akan dihapus permanen.</p>
            <div class="flex gap-3 justify-center">
                <button wire:click="cancelDelete" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold">Batal</button>
                <button wire:click="deleteShipment" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col animate-fade-in-up">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-lg font-bold text-slate-800">
                    {{ $isEditing ? 'Edit Shipment Details' : 'Create New Shipment' }}
                </h3>
                <button wire:click="closeModal" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Left Column --}}
                    <div class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Reference No</label>
                            <input type="text" wire:model="form.awb_number" class="w-full border-slate-300 rounded-lg text-sm bg-slate-50" placeholder="(Auto / Dikosongkan)">
                            @error('form.awb_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Customer</label>
                            <!-- Searchable Dropdown with Alpine.js -->
                            <div x-data="{
                                open: false,
                                search: '',
                                selected: @entangle('form.customer_id'),
                                selectedName: '',
                                customers: {{ Js::from($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->company_name])) }},
                                get filteredCustomers() {
                                    if (!this.search) return this.customers;
                                    return this.customers.filter(c => 
                                        c.name.toLowerCase().includes(this.search.toLowerCase())
                                    );
                                },
                                selectCustomer(customer) {
                                    this.selected = customer.id;
                                    this.selectedName = customer.name;
                                    this.search = '';
                                    this.open = false;
                                },
                                init() {
                                    if (this.selected) {
                                        const found = this.customers.find(c => c.id == this.selected);
                                        if (found) this.selectedName = found.name;
                                    }
                                    this.$watch('selected', (value) => {
                                        const found = this.customers.find(c => c.id == value);
                                        this.selectedName = found ? found.name : '';
                                    });
                                }
                            }" class="relative">
                                <!-- Display Selected / Search Input -->
                                <div @click="open = !open" 
                                     class="w-full border border-slate-300 rounded-lg text-sm bg-white cursor-pointer flex items-center justify-between px-3 py-2 hover:border-blue-400 transition">
                                    <span x-text="selectedName || 'Select Customer'" :class="selectedName ? 'text-slate-800' : 'text-slate-400'"></span>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                                
                                <!-- Dropdown Panel -->
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     @click.away="open = false"
                                     class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-64 overflow-hidden">
                                    
                                    <!-- Search Input -->
                                    <div class="p-2 border-b border-slate-100 sticky top-0 bg-white">
                                        <div class="relative">
                                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                            <input x-model="search" 
                                                   @click.stop
                                                   type="text" 
                                                   placeholder="🔍 Ketik untuk mencari..." 
                                                   class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   autofocus>
                                        </div>
                                    </div>
                                    
                                    <!-- Options List -->
                                    <div class="overflow-y-auto max-h-48">
                                        <template x-for="customer in filteredCustomers" :key="customer.id">
                                            <div @click="selectCustomer(customer)"
                                                 :class="selected == customer.id ? 'bg-blue-50 text-blue-700' : 'hover:bg-slate-50'"
                                                 class="px-3 py-2 cursor-pointer text-sm flex items-center gap-2 transition">
                                                <span x-show="selected == customer.id" class="text-blue-500">✓</span>
                                                <span x-text="customer.name"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredCustomers.length === 0" class="px-3 py-4 text-sm text-slate-400 text-center">
                                            Tidak ada customer yang cocok
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden input for Livewire -->
                                <input type="hidden" x-model="selected">
                            </div>
                            @error('form.customer_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Origin</label><input type="text" wire:model="form.origin" class="w-full border-slate-300 rounded-lg text-sm"></div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Destination</label><input type="text" wire:model="form.destination" class="w-full border-slate-300 rounded-lg text-sm"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Service Type</label>
                                <select wire:model="form.service_type" class="w-full border-slate-300 rounded-lg text-sm">
                                    <option value="import">Import</option>
                                    <option value="export">Export</option>
                                    <option value="domestic">Domestic</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Transport</label>
                                <select wire:model="form.shipment_type" class="w-full border-slate-300 rounded-lg text-sm">
                                    <option value="sea">🚢 Sea</option>
                                    <option value="air">✈️ Air</option>
                                    <option value="land">🚛 Land</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column (Cargo) --}}
                    <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 space-y-4">
                        <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-2">CARGO DETAILS</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="text-xs font-bold text-slate-500 block mb-1">Mode</label><select wire:model="form.container_mode" class="w-full border-slate-300 rounded-lg text-sm"><option value="LCL">LCL</option><option value="FCL">FCL</option></select></div>
                            <div><label class="text-xs font-bold text-slate-500 block mb-1">Details</label><input type="text" wire:model="form.container_info" class="w-full border-slate-300 rounded-lg text-sm"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="text-xs font-bold text-slate-500 block mb-1">Qty</label><input type="number" wire:model="form.pieces" class="w-full border-slate-300 rounded-lg text-sm"></div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 block mb-1">Unit</label>
                                <select wire:model="form.package_type" class="w-full border-slate-300 rounded-lg text-sm">
                                    <option value="">-- Pilih Unit --</option>
                                    
                                    {{-- UNIT KOTAK / PACKAGING --}}
                                    <option value="Ctn">Ctn (Cartons)</option>
                                    <option value="Box">Box (Kotak)</option>
                                    <option value="Pkgs">Pkgs (Packages/Kemasan)</option>
                                    <option value="Plt">Plt (Pallet)</option>
                                    <option value="Crate">Crate (Krat Kayu/Plastik)</option>
                                    <option value="Bdl">Bdl (Bundle/Ikatan)</option>
                                    
                                    {{-- UNIT SATUAN/BERAT --}}
                                    <option value="Pcs">Pcs (Pieces/Biji)</option>
                                    <option value="Kg">Kg (Kilogram)</option>
                                    <option value="Ton">Ton (Tonnase)</option>
                                    <option value="M3">M3 (Cubic Meter)</option>
                                    
                                    {{-- UNIT LAIN-LAIN --}}
                                    <option value="Bag">Bag (Tas/Karung)</option>
                                    <option value="Sack">Sack (Karung)</option>
                                    <option value="Drum">Drum (Barel)</option>
                                    <option value="Roll">Roll (Gulungan)</option>
                                    <option value="Tubes">Tubes (Tabung)</option>
                                    <option value="Other">Other (Lainnya)</option>
                                </select>
                            </div>
                        </div>
                        <div><label class="text-xs font-bold text-slate-500 block mb-1">Weight (Kg)</label><input type="number" wire:model="form.weight" class="w-full border-slate-300 rounded-lg text-sm"></div>
                        <div><label class="text-xs font-bold text-slate-500 block mb-1">Volume (CBM)</label><input type="number" step="0.001" wire:model="form.volume" class="w-full border-slate-300 rounded-lg text-sm" placeholder="0.000"></div>
                        
                        <hr class="border-slate-200 my-2">
                        
                        {{-- STATUS CHECKBOX --}}
                        <div class="flex items-center gap-3 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <input type="checkbox" wire:model="mark_as_completed" id="markCompleted" class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                            <label for="markCompleted" class="text-sm font-bold text-gray-700 cursor-pointer select-none">
                                Tandai Shipment Selesai (Completed)
                            </label>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-500 block mb-1">Est. Arrival</label>
                            <input type="date" wire:model="form.estimated_arrival" class="w-full border-slate-300 rounded-lg text-sm bg-white">
                        </div>

                        {{-- MANUAL LANE STATUS --}}
                        <div class="mt-2 p-3 rounded-lg border bg-white border-blue-200 shadow-sm">
                            <label class="text-xs font-bold text-blue-800 uppercase flex items-center gap-1 mb-1">Status Jalur Manual</label>
                            <select wire:model="form.lane_status" class="w-full rounded-lg text-sm font-bold">
                                <option value="">-- Otomatis / Belum Ada --</option>
                                <option value="green">🟩 JALUR HIJAU</option>
                                <option value="red">🟥 JALUR MERAH</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-600 font-medium hover:bg-slate-50">Cancel</button>
                <button wire:click="save" class="px-6 py-2 bg-m2b-primary text-white rounded-lg text-sm font-bold hover:bg-blue-900 transition shadow-md flex items-center">
                    <span wire:loading.remove wire:target="save">Save Shipment</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
    
    {{-- UPLOAD EVIDENCE MODAL (Dibiarkan sama) --}}
    @if($uploadingShipmentId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-xl w-full max-w-md shadow-2xl animate-fade-in-up border-t-4 border-purple-600">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="font-bold text-lg text-purple-900">Upload Internal Evidence </h3>
                <button wire:click="$set('uploadingShipmentId', null)" class="text-gray-400 hover:text-red-500">&times;</button>
            </div>
            <div class="space-y-4">
                <div class="border-2 border-dashed border-purple-200 rounded-lg p-4 bg-purple-50 text-center relative hover:bg-purple-50 transition">
                    <input type="file" wire:model="internal_photo" class="w-full text-xs text-slate-500 mx-auto">
                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, PDF (Max 10MB)</p>
                </div>
                <div><input type="text" wire:model="internal_note" placeholder="Keterangan..." class="w-full border-purple-200 rounded-lg px-3 py-2 text-sm"></div>
            </div>
            <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                <button wire:click="$set('uploadingShipmentId', null)" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 text-sm hover:bg-gray-50 transition">Batal</button>
                <button wire:click="uploadInternalEvidence" class="px-4 py-2 bg-purple-700 text-white rounded-lg text-sm font-bold hover:bg-purple-800 transition shadow-md flex items-center disabled:opacity-50 disabled:cursor-not-allowed" @if(!$internal_photo) disabled @endif>
                    <span wire:loading.remove wire:target="uploadInternalEvidence">Upload</span>
                    <span wire:loading wire:target="uploadInternalEvidence">Uploading...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL CETAK SURAT JALAN --}}
    @if($showPrintDoModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl border-t-4 border-orange-500 max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center px-6 py-4 border-b bg-orange-50">
                <div>
                    <h3 class="font-bold text-lg text-orange-900">Cetak Surat Jalan / DO</h3>
                    @if($printDoShipment)
                    <p class="text-sm text-gray-600">{{ $printDoShipment->awb_number }} - {{ $printDoShipment->customer->company_name ?? '-' }}</p>
                    @endif
                </div>
                <button wire:click="closePrintDoModal" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <label class="block text-sm font-bold text-blue-800 mb-2">Jumlah Container / Kendaraan</label>
                    <div class="flex items-center gap-3">
                        <input type="number" wire:model.live.debounce.500ms="printDoContainerCount" 
                               min="1" max="100" 
                               class="w-24 border-blue-300 rounded-lg text-lg font-bold text-center focus:border-blue-500 focus:ring-blue-500" 
                               placeholder="1">
                        <span class="text-sm text-blue-800">Surat Jalan</span>
                    </div>
                    <p class="text-xs text-blue-600 mt-2 text-center">Akan mencetak <strong>{{ $printDoContainerCount }}</strong> lembar surat jalan</p>
                </div>
                
                <div class="space-y-4">
                    @foreach($printDoContainers as $index => $container)
                    <div class="p-4 border-2 border-orange-200 rounded-xl bg-orange-50/50">
                        <h4 class="font-bold text-sm text-orange-800 mb-4 flex items-center gap-2">
                            <span class="bg-orange-500 text-white w-7 h-7 rounded-full flex items-center justify-center text-sm">{{ $index + 1 }}</span>
                            Surat Jalan ke-{{ $index + 1 }}
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">No. Container</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.no_container" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="TINU1234567 (opsional)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">No. Polisi Truk</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.no_polisi" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="BK 1234 XX (opsional)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nama Supir</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.nama_supir" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="Nama lengkap (opsional)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">No. Segel / Seal</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.no_seal" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="SEAL123456 (opsional)">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-100 border-t flex justify-between items-center">
                <p class="text-xs text-gray-500">* Semua field opsional, bisa dikosongkan</p>
                <div class="flex gap-3">
                    <button wire:click="closePrintDoModal" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 font-medium hover:bg-white">Batal</button>
                    <button wire:click="printDo" class="px-6 py-2 bg-orange-600 text-white rounded-lg font-bold hover:bg-orange-700 transition shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak {{ $printDoContainerCount }} Surat Jalan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    @script
    <script>
        $wire.on('openPrintWindow', (event) => {
            window.open(event.url, '_blank');
        });
    </script>
    @endscript

    {{-- Cancel Shipment Modal --}}
    @if($showCancelModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-cancel-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="closeCancelModal" 
                 aria-hidden="true"></div>

            {{-- Center modal --}}
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-cancel-title">
                                🚫 Batalkan Shipment
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Apakah Anda yakin ingin membatalkan shipment ini? 
                                    Shipment yang dibatalkan tidak dapat diedit lagi.
                                </p>
                                
                                <div class="mt-4">
                                    <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                        Alasan Pembatalan <span class="text-gray-400">(opsional)</span>
                                    </label>
                                    <textarea 
                                        wire:model="cancellationReason" 
                                        id="cancellation_reason"
                                        rows="3" 
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                        placeholder="Contoh: Permintaan customer, kesalahan input, dll..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button 
                        wire:click="confirmCancel" 
                        type="button" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                        Ya, Batalkan Shipment
                    </button>
                    <button 
                        wire:click="closeCancelModal" 
                        type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
