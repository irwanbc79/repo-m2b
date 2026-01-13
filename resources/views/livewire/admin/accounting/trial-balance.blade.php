<div class="space-y-6">
    @section('header', 'Trial Balance (Neraca Saldo)')

    <!-- FILTER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-end gap-4">
        <div class="w-48">
            <label class="block text-sm font-bold text-gray-700 mb-1">Dari Tanggal</label>
            <input type="date" wire:model.live="start_date" class="w-full border rounded-lg p-2 text-sm">
        </div>
        <div class="w-48">
            <label class="block text-sm font-bold text-gray-700 mb-1">Sampai Tanggal</label>
            <input type="date" wire:model.live="end_date" class="w-full border rounded-lg p-2 text-sm">
        </div>
        <div class="pb-2 text-sm text-gray-500">
            Menampilkan data periode terpilih.
        </div>
    </div>

    <!-- REPORT TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-lg text-blue-900">Laporan Neraca Saldo</h3>
            
            <!-- STATUS BALANCE -->
            @if($totalDebit == $totalCredit)
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-bold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    BALANCE
                </span>
            @else
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold flex items-center gap-2 animate-pulse">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    NOT BALANCE
                </span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-800 text-white font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Nama Akun</th>
                        <th class="px-6 py-3 text-right">Saldo Awal</th>
                        <th class="px-6 py-3 text-right bg-blue-900">Mutasi Debit</th>
                        <th class="px-6 py-3 text-right bg-blue-900">Mutasi Kredit</th>
                        <th class="px-6 py-3 text-right">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($reportData as $row)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 font-mono text-blue-700">{{ $row['code'] }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                        
                        <!-- SALDO AWAL -->
                        <td class="px-6 py-3 text-right text-gray-500">
                            {{ number_format($row['opening'], 0, ',', '.') }}
                        </td>

                        <!-- MUTASI -->
                        <td class="px-6 py-3 text-right font-mono bg-blue-50">
                            {{ $row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-6 py-3 text-right font-mono bg-blue-50">
                            {{ $row['credit'] > 0 ? number_format($row['credit'], 0, ',', '.') : '-' }}
                        </td>

                        <!-- SALDO AKHIR -->
                        <td class="px-6 py-3 text-right font-bold text-gray-900">
                            {{ number_format($row['ending'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 font-black text-gray-800 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right uppercase tracking-wider">Total Mutasi</td>
                        <td class="px-6 py-4 text-right text-blue-900">{{ number_format($totalDebit, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-blue-900">{{ number_format($totalCredit, 0, ',', '.') }}</td>
                        <td class="px-6 py-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>