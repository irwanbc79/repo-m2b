<div class="space-y-6">
    @section('header', 'Profit & Loss (Laba Rugi)')

    <!-- FILTER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-end gap-4 no-print">
        <div class="w-48">
            <label class="block text-sm font-bold text-gray-700 mb-1">Dari Tanggal</label>
            <input type="date" wire:model.live="start_date" class="w-full border rounded-lg p-2 text-sm">
        </div>
        <div class="w-48">
            <label class="block text-sm font-bold text-gray-700 mb-1">Sampai Tanggal</label>
            <input type="date" wire:model.live="end_date" class="w-full border rounded-lg p-2 text-sm">
        </div>
        <button onclick="window.print()" class="ml-auto bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak / PDF
        </button>
    </div>

    <!-- LAPORAN -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 max-w-4xl mx-auto print-area">
        
        <!-- KOP LAPORAN -->
        <div class="text-center mb-8 pb-4 border-b-2 border-gray-800">
            <h2 class="text-2xl font-black text-gray-900 uppercase">PT. MORA MULTI BERKAH</h2>
            <h3 class="text-lg font-bold text-gray-600 uppercase mt-1">Laporan Laba Rugi (Profit & Loss)</h3>
            <p class="text-sm text-gray-500 mt-2">Periode: {{ \Carbon\Carbon::parse($start_date)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->format('d F Y') }}</p>
        </div>

        <div class="space-y-6 text-sm text-gray-800">
            
            <!-- 1. PENDAPATAN -->
            <div>
                <h4 class="font-bold text-m2b-primary uppercase border-b mb-2 pb-1">Pendapatan Usaha</h4>
                <table class="w-full">
                    @foreach($revenues as $r)
                    <tr>
                        <td class="py-1 pl-4">{{ $r->name }}</td>
                        <td class="text-right font-mono">{{ number_format($r->net_movement, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-bold bg-blue-50">
                        <td class="py-2 pl-4">Total Pendapatan</td>
                        <td class="text-right text-blue-700">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <!-- 2. HPP -->
            <div>
                <h4 class="font-bold text-m2b-primary uppercase border-b mb-2 pb-1">Beban Pokok Penjualan (HPP)</h4>
                <table class="w-full">
                    @foreach($cogs as $c)
                    <tr>
                        <td class="py-1 pl-4">{{ $c->name }}</td>
                        <td class="text-right font-mono">{{ number_format($c->net_movement, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-bold bg-red-50">
                        <td class="py-2 pl-4">Total Beban Pokok</td>
                        <td class="text-right text-red-700">({{ number_format($totalCOGS, 0, ',', '.') }})</td>
                    </tr>
                </table>
            </div>

            <!-- LABA KOTOR -->
            <div class="flex justify-between items-center py-3 border-t-2 border-b-2 border-gray-200 bg-gray-50">
                <span class="font-black text-lg uppercase pl-4">Laba Kotor</span>
                <span class="font-black text-lg {{ $grossProfit >= 0 ? 'text-blue-900' : 'text-red-600' }}">
                    Rp {{ number_format($grossProfit, 0, ',', '.') }}
                </span>
            </div>

            <!-- 3. BEBAN OPERASIONAL -->
            <div>
                <h4 class="font-bold text-m2b-primary uppercase border-b mb-2 pb-1">Beban Operasional</h4>
                <table class="w-full">
                    @foreach($expenses as $e)
                    <tr>
                        <td class="py-1 pl-4">{{ $e->name }}</td>
                        <td class="text-right font-mono">{{ number_format($e->net_movement, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-bold bg-red-50">
                        <td class="py-2 pl-4">Total Beban Operasional</td>
                        <td class="text-right text-red-700">({{ number_format($totalExpense, 0, ',', '.') }})</td>
                    </tr>
                </table>
            </div>

            <!-- LABA BERSIH -->
            <div class="flex justify-between items-center py-4 border-t-4 border-gray-800 mt-6">
                <span class="font-black text-xl uppercase pl-4">LABA / (RUGI) BERSIH</span>
                <span class="font-black text-2xl {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </span>
            </div>

        </div>
        
        <div class="mt-12 text-center text-xs text-gray-400">
            Dicetak otomatis oleh M2B Portal System pada {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
    
    <!-- STYLE PRINT -->
    <style>
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; box-shadow: none; border: none; }
            .no-print { display: none; }
        }
    </style>
</div>