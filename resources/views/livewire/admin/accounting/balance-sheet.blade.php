<div class="space-y-6">
    @section('header', 'Balance Sheet (Laporan Neraca)')

    <!-- FILTER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-end gap-4 no-print">
        <div class="w-64">
            <label class="block text-sm font-bold text-gray-700 mb-1">Per Tanggal</label>
            <input type="date" wire:model.live="end_date" class="w-full border rounded-lg p-2 text-sm">
        </div>
        <button onclick="window.print()" class="ml-auto bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak / PDF
        </button>
    </div>

    <!-- LAPORAN -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 max-w-6xl mx-auto print-area">
        
        <!-- KOP -->
        <div class="text-center mb-8 pb-4 border-b-2 border-gray-800">
            <h2 class="text-2xl font-black text-gray-900 uppercase">PT. MORA MULTI BERKAH</h2>
            <h3 class="text-lg font-bold text-gray-600 uppercase mt-1">Laporan Neraca (Balance Sheet)</h3>
            <p class="text-sm text-gray-500 mt-2">Per Tanggal: {{ \Carbon\Carbon::parse($end_date)->format('d F Y') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-8">
            
            <!-- KOLOM KIRI: ASET -->
            <div>
                <h4 class="font-black text-lg text-m2b-primary uppercase mb-4 border-b-2 border-blue-900 pb-2">ASET (ASSETS)</h4>
                
                <div class="space-y-2">
                    @foreach($assets as $acc)
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-1">
                        <span>{{ $acc->name }}</span>
                        <span class="font-mono">{{ number_format($acc->balance, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-2 border-t-2 border-gray-300 flex justify-between items-center bg-blue-50 p-2 rounded">
                    <span class="font-bold text-blue-900 uppercase">Total Aset</span>
                    <span class="font-black text-lg text-blue-900">{{ number_format($totalAssets, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- KOLOM KANAN: KEWAJIBAN & MODAL -->
            <div>
                <!-- KEWAJIBAN -->
                <h4 class="font-black text-lg text-red-800 uppercase mb-4 border-b-2 border-red-900 pb-2">KEWAJIBAN (LIABILITIES)</h4>
                <div class="space-y-2 mb-8">
                    @foreach($liabilities as $acc)
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-1">
                        <span>{{ $acc->name }}</span>
                        <span class="font-mono">{{ number_format($acc->balance, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between font-bold text-gray-700 pt-2">
                        <span>Total Kewajiban</span>
                        <span>{{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- MODAL -->
                <h4 class="font-black text-lg text-green-800 uppercase mb-4 border-b-2 border-green-900 pb-2">MODAL (EQUITY)</h4>
                <div class="space-y-2">
                    @foreach($equity as $acc)
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-1">
                        <span>{{ $acc->name }}</span>
                        <span class="font-mono">{{ number_format($acc->balance, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    
                    <!-- LABA TAHUN BERJALAN (OTOMATIS) -->
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-1 bg-green-50 font-bold text-green-800 px-1">
                        <span>Laba Tahun Berjalan (Current Earnings)</span>
                        <span class="font-mono">{{ number_format($currentEarnings, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between font-bold text-gray-700 pt-2">
                        <span>Total Modal</span>
                        <span>{{ number_format($totalEquity, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-6 pt-2 border-t-2 border-gray-300 flex justify-between items-center bg-gray-100 p-2 rounded">
                    <span class="font-bold text-gray-900 uppercase">Total Kewajiban & Modal</span>
                    <span class="font-black text-lg text-gray-900">{{ number_format($totalLiabilities + $totalEquity, 0, ',', '.') }}</span>
                </div>
            </div>

        </div>

        <!-- BALANCE CHECK -->
        <div class="mt-8 text-center">
            @if($totalAssets == ($totalLiabilities + $totalEquity))
                <span class="px-4 py-2 bg-green-600 text-white rounded-full font-bold text-xs">✅ BALANCE (SEIMBANG)</span>
            @else
                <span class="px-4 py-2 bg-red-600 text-white rounded-full font-bold text-xs animate-pulse">❌ TIDAK BALANCE (Cek Jurnal!)</span>
            @endif
        </div>

        <div class="mt-12 text-center text-xs text-gray-400">
            Dicetak otomatis oleh M2B Portal System pada {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
    
    <style>
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; box-shadow: none; border: none; }
            .no-print { display: none; }
        }
    </style>
</div>