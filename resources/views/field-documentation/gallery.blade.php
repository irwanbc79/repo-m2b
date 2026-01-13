@extends('layouts.admin')

@section('header', '📸 Dokumentasi ' . $shipment->shipment_number)

@section('content')
<div class="space-y-6">
    {{-- Shipment Info --}}
    <div class="bg-white rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $shipment->shipment_number }}</h2>
                <p class="text-gray-500">{{ $shipment->customer->name ?? 'N/A' }}</p>
            </div>
            <a href="{{ route('field-documentation.upload', $shipment->shipment_number) }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                + Tambah Foto
            </a>
        </div>
    </div>

    {{-- Photo Grid --}}
    @if($photos->isEmpty())
    <div class="bg-white rounded-xl p-12 shadow-sm text-center">
        <p class="text-gray-500">Belum ada foto untuk shipment ini</p>
    </div>
    @else
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($photos as $photo)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden group relative">
            <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank">
                <img src="{{ asset('storage/' . $photo->thumbnail_path) }}" 
                     alt="{{ $photo->original_filename }}"
                     class="w-full aspect-square object-cover group-hover:opacity-90 transition">
            </a>
            <div class="p-3">
                <p class="text-xs text-gray-500">{{ $photo->created_at->format('d M Y H:i') }}</p>
                <p class="text-xs text-gray-400 truncate">{{ $photo->user->name ?? 'Unknown' }}</p>
                @if($photo->latitude && $photo->longitude)
                <span class="inline-flex items-center text-xs text-green-600 mt-1">
                    📍 GPS
                </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $photos->links() }}
    </div>
    @endif
</div>
@endsection
