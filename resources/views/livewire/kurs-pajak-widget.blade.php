<div class="bg-gradient-to-br from-[#0F2C59] to-[#0a1e3f] text-white rounded-xl shadow-lg overflow-hidden relative">
    {{-- Header Widget --}}
    <div class="px-5 py-4 border-b border-white/10 flex justify-between items-center bg-black/10">
        <div class="flex items-center gap-3">
            <div class="bg-yellow-500 p-1.5 rounded text-blue-900 shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm tracking-wide text-white">KURS PAJAK (BEA CUKAI)</h3>
                <p class="text-[10px] text-gray-300">{{ Str::limit($period, 35) }}</p>
            </div>
        </div>
    </div>

    {{-- Body Widget --}}
    <div class="p-5">
        @if($isError || empty($rates))
            <div class="text-center py-4">
                <p class="text-xs text-gray-400 mb-3">Gagal memuat data otomatis.</p>
                <a href="https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak" target="_blank" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-full text-xs font-bold transition border border-white/10">
                    Cek Manual di Kemenkeu &rarr;
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($rates as $rate)
                <div class="flex justify-between items-center group p-2 rounded hover:bg-white/5 transition cursor-default">
                    <div class="flex items-center gap-3">
                        <span class="text-xl filter drop-shadow-md">{{ $rate['flag'] }}</span>
                        <span class="font-bold text-gray-200 text-sm tracking-wider">{{ $rate['code'] }}</span>
                    </div>
                    <div class="font-mono font-bold text-yellow-400 text-sm group-hover:text-white transition">
                        Rp {{ $rate['value'] }}
                    </div>
                </div>
                {{-- Garis pemisah tipis --}}
                @if(!$loop->last) 
                    <div class="border-b border-white/5 mx-2"></div> 
                @endif
                @endforeach
            </div>
            
            <div class="mt-5 pt-3 border-t border-white/10 text-center">
                <a href="https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak" target="_blank" class="text-[10px] text-blue-300 hover:text-white transition flex items-center justify-center gap-1 group">
                    Sumber: Fiskal Kemenkeu RI 
                    <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>
        @endif
    </div>
</div>