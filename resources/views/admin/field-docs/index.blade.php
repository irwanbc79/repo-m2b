@extends('layouts.admin')

@section('header', '📸 Dokumentasi Lapangan')

@section('content')
<div class="space-y-6">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm">
            <p class="text-3xl font-bold text-green-600">{{ $stats['today'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Foto Hari Ini</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm">
            <p class="text-3xl font-bold text-blue-600">{{ $stats['week'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Foto Minggu Ini</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm">
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Foto</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm">
            <p class="text-3xl font-bold text-purple-600">{{ $stats['with_location'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Dengan GPS</p>
        </div>
    </div>

    {{-- Action Button --}}
    <div class="flex justify-end">
        <a href="{{ route('admin.field-docs.upload') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-medium shadow-lg transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Upload Foto Baru
        </a>
    </div>

    {{-- Filter & Search --}}
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('admin.field-docs.index') }}" class="space-y-3">
            {{-- Search --}}
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ $filters['search'] }}"
                    placeholder="Cari No. Shipment, nomor BL, atau nama customer..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Dropdown filters --}}
            <div class="flex flex-wrap gap-2">
                <select name="service_type" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Service</option>
                    <option value="import"   {{ $filters['serviceType'] === 'import'   ? 'selected' : '' }}>📥 Import</option>
                    <option value="export"   {{ $filters['serviceType'] === 'export'   ? 'selected' : '' }}>📤 Export</option>
                    <option value="domestic" {{ $filters['serviceType'] === 'domestic' ? 'selected' : '' }}>🏠 Domestic</option>
                </select>

                <select name="shipment_type" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Moda</option>
                    <option value="sea"  {{ $filters['shipmentType'] === 'sea'  ? 'selected' : '' }}>🚢 Sea</option>
                    <option value="air"  {{ $filters['shipmentType'] === 'air'  ? 'selected' : '' }}>✈️ Air</option>
                    <option value="land" {{ $filters['shipmentType'] === 'land' ? 'selected' : '' }}>🚛 Land</option>
                </select>

                <input type="date" name="date_from" value="{{ $filters['dateFrom'] }}"
                    class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Dari tanggal">

                <input type="date" name="date_to" value="{{ $filters['dateTo'] }}"
                    class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Sampai tanggal">

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition">
                    Cari
                </button>

                @if($filters['search'] || $filters['serviceType'] || $filters['shipmentType'] || $filters['dateFrom'] || $filters['dateTo'])
                <a href="{{ route('admin.field-docs.index') }}"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-xl transition">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Shipment List with Photos --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Shipment dengan Dokumentasi</h3>
            <span class="text-sm text-gray-400">{{ $recentShipments->total() }} shipment</span>
        </div>

        @if($recentShipments->isEmpty())
        <div class="p-12 text-center text-gray-500">
            <svg class="mx-auto w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="mt-4">Tidak ada hasil yang cocok</p>
            @if($filters['search'] || $filters['serviceType'] || $filters['shipmentType'])
            <a href="{{ route('admin.field-docs.index') }}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">Hapus filter</a>
            @endif
        </div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($recentShipments as $shipment)
            <a href="{{ route('admin.field-docs.gallery', $shipment->awb_number ?: $shipment->id) }}"
               class="flex items-center px-6 py-4 hover:bg-gray-50 transition">
                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                    @if($shipment->latestFieldPhoto)
                    <img src="{{ asset('storage/' . ($shipment->latestFieldPhoto->thumbnail_path ?? $shipment->latestFieldPhoto->file_path)) }}"
                         alt="Preview"
                         class="w-full h-full object-cover"
                         onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-gray-400 text-2xl\'>📷</div>'">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @endif
                </div>
                <div class="ml-4 flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 truncate">{{ $shipment->awb_number ?: $shipment->bl_number ?: 'Shipment #'.$shipment->id }}</p>
                    <p class="text-sm text-gray-500 truncate">{{ $shipment->customer->company_name ?? 'N/A' }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @php
                            $stColors = ['import' => 'bg-blue-100 text-blue-700', 'export' => 'bg-emerald-100 text-emerald-700', 'domestic' => 'bg-orange-100 text-orange-700'];
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $stColors[$shipment->service_type ?? ''] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($shipment->service_type ?? 'N/A') }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $shipment->origin }} → {{ $shipment->destination }}</span>
                    </div>
                </div>
                <div class="text-right ml-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $shipment->field_photos_count }} foto
                    </span>
                </div>
            </a>
            @endforeach
        </div>

        @if($recentShipments->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $recentShipments->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
