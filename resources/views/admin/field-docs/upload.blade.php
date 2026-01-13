@extends('layouts.admin')

@section('title', 'Upload Foto' . ($shipment ? ' - ' . ($shipment->awb_number ?: $shipment->bl_number) : ''))

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ $shipment ? route('admin.field-docs.gallery', $shipment->awb_number ?: $shipment->id) : route('admin.field-docs.index') }}" 
               class="text-gray-500 hover:text-gray-700 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">📷 Upload Foto Dokumentasi</h1>
                @if($shipment)
                <p class="text-gray-500 mt-1">
                    {{ $shipment->awb_number ?: $shipment->bl_number ?: 'Shipment #'.$shipment->id }}
                    • {{ $shipment->customer->company_name ?? 'N/A' }}
                </p>
                @else
                <p class="text-gray-500 mt-1">Pilih shipment dan upload foto</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Upload Form via Livewire --}}
    @livewire('field-photo-upload', ['shipment' => $shipmentNumber])

    {{-- Tips --}}
    <div class="mt-6 bg-blue-50 rounded-xl p-4">
        <h3 class="font-semibold text-blue-800 mb-2">💡 Tips Upload:</h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Maksimal 10 foto per upload, masing-masing max 10MB</li>
            <li>• Format yang didukung: JPG, PNG, HEIC, WebP</li>
            <li>• Aktifkan GPS untuk menyertakan lokasi foto</li>
            <li>• Gunakan kamera HP untuk hasil terbaik di lapangan</li>
        </ul>
    </div>
</div>
@endsection
