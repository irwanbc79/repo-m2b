@extends('layouts.admin')

@section('content')
<div class="p-6">
    {{-- Navigation & Action Buttons --}}
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('finance.simple-invoice.index') }}" 
           class="inline-flex items-center gap-2 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-semibold shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Daftar Invoice</span>
        </a>
        
        <div class="flex gap-3">
            <a href="{{ route('finance.simple-invoice.edit', $invoice->id) }}"
               class="inline-flex items-center gap-2 px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Invoice
            </a>
            
            <a href="{{ route('finance.simple-invoice.download', $invoice->id) }}"
               class="inline-flex items-center gap-2 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
        </div>
    </div>

    {{-- Invoice Info Header --}}
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-blue-900">{{ $invoice->invoice_number }}</h2>
                <p class="text-sm text-blue-700 mt-1">
                    Customer: {{ $invoice->customer_name }} | 
                    Tanggal: {{ $invoice->invoice_date->format('d F Y') }} |
                    Total: <span class="font-semibold">{{ $invoice->formatted_total }}</span>
                </p>
            </div>
            <div>
                {!! $invoice->status_badge !!}
            </div>
        </div>
    </div>

    {{-- PDF Preview in iframe --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-gray-200">
        <iframe src="{{ route('finance.simple-invoice.download', $invoice->id) }}" 
                class="w-full border-0" 
                style="height: calc(100vh - 280px); min-height: 600px;">
        </iframe>
    </div>
    
    {{-- Bottom Actions --}}
    <div class="mt-4 flex justify-center gap-4">
        <button onclick="window.print()" 
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Invoice
        </button>
    </div>
</div>

<style>
@media print {
    body > *:not(iframe) {
        display: none !important;
    }
    iframe {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
}
</style>
@endsection
