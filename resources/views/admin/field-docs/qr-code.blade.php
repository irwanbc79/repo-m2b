@extends('layouts.admin')

@section('title', 'QR Code - ' . ($shipment->awb_number ?: $shipment->bl_number ?: 'Shipment #'.$shipment->id))

@push('styles')
<style>
    .qr-container {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .qr-code-wrapper {
        display: inline-block;
        padding: 1.5rem;
        background: white;
        border: 3px solid #e5e7eb;
        border-radius: 1rem;
    }
    .qr-code-wrapper svg {
        width: 250px;
        height: 250px;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-6">
    {{-- Back Button --}}
    <div class="mb-6 no-print">
        <a href="{{ route('admin.field-docs.gallery', $shipment->awb_number ?: $shipment->id) }}" 
           class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Gallery
        </a>
    </div>

    {{-- QR Code Card --}}
    <div class="qr-container print-area">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">📦 QR Code Shipment</h1>
            <p class="text-gray-500 mt-1">Scan untuk upload foto dokumentasi</p>
        </div>

        {{-- Shipment Info --}}
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-gray-500">AWB/BL Number:</span>
                    <p class="font-bold text-gray-800">{{ $shipment->awb_number ?: $shipment->bl_number ?: 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Customer:</span>
                    <p class="font-bold text-gray-800">{{ $shipment->customer->company_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Type:</span>
                    <p class="font-semibold">
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $shipment->shipment_type === 'import' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ ucfirst($shipment->shipment_type ?? 'N/A') }}
                        </span>
                    </p>
                </div>
                <div>
                    <span class="text-gray-500">Route:</span>
                    <p class="font-semibold text-gray-800">{{ $shipment->origin }} → {{ $shipment->destination }}</p>
                </div>
            </div>
        </div>

        {{-- QR Code --}}
        <div class="qr-code-wrapper mb-6">
            @php
                // Generate URL untuk upload foto
                $uploadUrl = route('admin.field-docs.upload', $shipment->awb_number ?: $shipment->id);
                // Atau bisa juga pakai mobile URL jika ada
                // $uploadUrl = url('/mobile/upload/' . ($shipment->awb_number ?: $shipment->id));
            @endphp
            
            {!! QrCode::size(250)->generate($uploadUrl) !!}
        </div>

        {{-- URL Info --}}
        <div class="bg-blue-50 rounded-lg p-3 mb-6">
            <p class="text-xs text-blue-600 break-all">{{ $uploadUrl }}</p>
        </div>

        {{-- Instructions --}}
        <div class="text-left bg-yellow-50 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-yellow-800 mb-2">📋 Cara Penggunaan:</h3>
            <ol class="text-sm text-yellow-700 space-y-1 list-decimal list-inside">
                <li>Cetak QR Code ini dan tempel di dokumen/barang shipment</li>
                <li>Field Officer scan QR Code dengan kamera HP</li>
                <li>Login jika diminta, lalu langsung upload foto</li>
                <li>Foto otomatis terhubung ke shipment ini</li>
            </ol>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center no-print">
            <button onclick="window.print()" 
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print QR Code
            </button>
            
            <a href="{{ route('admin.field-docs.qr-download', $shipment->awb_number ?: $shipment->id) }}" 
               class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download SVG
            </a>
            
            <a href="{{ route('admin.field-docs.upload', $shipment->awb_number ?: $shipment->id) }}" 
               class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Upload Foto
            </a>
        </div>
    </div>

    {{-- Additional Info --}}
    <div class="mt-6 text-center text-sm text-gray-500 no-print">
        <p>💡 Tip: QR Code ini bisa di-scan menggunakan aplikasi kamera bawaan HP atau scanner QR apapun</p>
    </div>
</div>
@endsection
