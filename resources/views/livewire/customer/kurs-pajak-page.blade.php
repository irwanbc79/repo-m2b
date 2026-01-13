<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                <span class="flex items-center justify-center w-10 h-10 bg-blue-600 text-white rounded-xl shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
                Kurs Pajak Mingguan
            </h2>
            <p class="text-gray-500 text-sm mt-2 font-medium">Data resmi Kemenkeu RI untuk perhitungan pajak bea cukai.</p>
        </div>

        <div class="bg-white border border-gray-200 px-5 py-3 rounded-2xl shadow-sm flex items-center gap-4">
            <div class="text-right">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Periode Berlaku</span>
                <span class="block text-sm font-black text-blue-900">{{ $period }}</span>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-gray-100 overflow-hidden">
        @if($isError)
            <div class="p-16 text-center">
                <p class="text-red-500 font-bold">Gagal memuat data. Silakan coba lagi.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-400 font-bold uppercase text-[11px] tracking-widest border-b border-gray-100">
                            <th class="px-8 py-5 w-20 text-center">#</th>
                            <th class="px-6 py-5">Mata Uang</th>
                            <th class="px-6 py-5">Kode</th>
                            <th class="px-8 py-5 text-right">Nilai Tukar (IDR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($rates as $index => $rate)
                        <tr class="hover:bg-blue-50/40 transition-all group">
                            <td class="px-8 py-5 text-center text-gray-300 font-mono text-xs">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    {{-- FLAG ICON DARI CDN (Lebih stabil & Premium) --}}
                                    <div class="w-8 h-6 overflow-hidden rounded shadow-sm border border-gray-100 flex-shrink-0">
                                        <img src="https://flagcdn.com/w40/{{ $rate['country_code'] }}.png" 
                                             srcset="https://flagcdn.com/w80/{{ $rate['country_code'] }}.png 2x"
                                             class="w-full h-full object-cover"
                                             alt="{{ $rate['name'] }}">
                                    </div>
                                    <div>
                                        <span class="block font-bold text-gray-800 group-hover:text-blue-900">{{ $rate['name'] }}</span>
                                        <span class="block text-[10px] text-gray-400 font-medium">Official Tax Rate</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 bg-gray-100 rounded-lg text-xs font-black text-gray-600 border border-gray-200 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    {{ $rate['code'] }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right font-mono font-bold text-lg {{ $index === 0 ? 'text-blue-700 scale-105' : 'text-gray-900' }}">
                                <span class="text-gray-300 text-xs mr-1 font-sans">Rp</span>{{ $rate['value'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50/80 px-8 py-4 border-t border-gray-100 flex justify-between items-center text-[10px] text-gray-400">
                <span class="flex items-center gap-1 font-bold"><span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Auto-update Active</span>
                <span>Last Sync: {{ $lastUpdated }}</span>
            </div>
        @endif
    </div>
</div>