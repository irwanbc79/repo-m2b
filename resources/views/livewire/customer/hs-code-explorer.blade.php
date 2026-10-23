<div class="max-w-7xl mx-auto space-y-6 pb-12" x-data="{ copyTooltip: null }">
    @section('header', 'HS Code Explorer BTKI 2022')

    {{-- TOP BANNER / HERO SECTION --}}
    <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-blue-950 rounded-3xl p-6 md:p-8 text-white shadow-xl border border-slate-700/60 relative overflow-hidden">
        {{-- Decorative background glow --}}
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        BTKI 2022 Official Database
                    </span>
                    <span class="text-xs text-slate-400 font-medium">
                        • {{ number_format($totalHsCodes) }} Pos Tarif Aktif
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    <span>🔍 HS Code Explorer</span>
                </h1>
                <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                    Pencarian kode HS dan tarif kepabeanan resmi (Bea Masuk MFN, PPN, PPh Impor). Ketik 8-digit tanpa titik atau nama barang untuk menemukan pos tarif komoditas Anda.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0 flex-wrap">
                <button type="button" wire:click="toggleKumModal" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-900/30 border border-emerald-400/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Panduan KUM HS</span>
                </button>
            </div>
        </div>
    </div>

    {{-- SEARCH & FILTER CARD --}}
    <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200 space-y-4">
        {{-- Search Input Row --}}
        <div class="flex flex-col md:flex-row items-stretch gap-3">
            <div class="flex-1 relative min-w-0">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Ketik 8-digit (cth: 84713020 / 8471.30.20) atau nama barang (cth: laptop, kopi, genset)..." 
                    class="w-full pl-11 pr-24 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:border-blue-600 focus:ring-0 transition shadow-inner"
                    style="width: 100%; min-width: 0;"
                />
                @if(!empty($search))
                <button 
                    type="button" 
                    wire:click="$set('search', '')" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-semibold text-slate-400 hover:text-red-500 transition"
                >
                    <span class="bg-slate-200 hover:bg-red-100 hover:text-red-600 px-2.5 py-1 rounded-xl">✕ Hapus</span>
                </button>
                @endif
            </div>

            {{-- Chapter Dropdown --}}
            <div class="w-full md:w-80 flex-shrink-0">
                <select 
                    wire:model.live="selectedChapter" 
                    class="w-full py-3.5 px-3.5 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-medium text-slate-700 focus:bg-white focus:border-blue-600 focus:ring-0 transition"
                    style="width: 100%;"
                >
                    <option value="">Pilih Bab BTKI (01 - 99)</option>
                    @foreach($chapters as $chap)
                        <option value="{{ $chap->chapter_number }}">
                            Bab {{ $chap->chapter_number }} — {{ Str::limit($chap->title_id, 40) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Level Filter Tabs & Reset --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-2xl border border-slate-200/80 text-xs font-semibold">
                <button 
                    type="button" 
                    wire:click="filterByLevel('')" 
                    class="px-3.5 py-2 rounded-xl transition {{ empty($selectedLevel) ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Semua Level
                </button>
                <button 
                    type="button" 
                    wire:click="filterByLevel('4')" 
                    class="px-3.5 py-2 rounded-xl transition {{ $selectedLevel === '4' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-blue-600' }}"
                >
                    Pos 4-Digit
                </button>
                <button 
                    type="button" 
                    wire:click="filterByLevel('6')" 
                    class="px-3.5 py-2 rounded-xl transition {{ $selectedLevel === '6' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-blue-600' }}"
                >
                    Subpos 6-Digit
                </button>
                <button 
                    type="button" 
                    wire:click="filterByLevel('8')" 
                    class="px-3.5 py-2 rounded-xl transition {{ $selectedLevel === '8' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-emerald-600' }}"
                >
                    Tarif 8-Digit (Aktif)
                </button>
            </div>

            @if(!empty($search) || !empty($selectedChapter) || !empty($selectedLevel))
            <button 
                type="button" 
                wire:click="resetFilters" 
                class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3.5 py-2 rounded-xl transition self-start sm:self-center"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Reset Semua Filter</span>
            </button>
            @endif
        </div>

        {{-- Popular Chapters Chips --}}
        <div class="pt-2 flex items-center gap-2 flex-wrap">
            <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400">Bab Populer:</span>
            <button type="button" wire:click="filterByChapter('84')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $selectedChapter === '84' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-blue-50 text-blue-900 border-blue-200 hover:bg-blue-100' }}">
                ⚙️ Bab 84: Mesin
            </button>
            <button type="button" wire:click="filterByChapter('85')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $selectedChapter === '85' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-purple-50 text-purple-900 border-purple-200 hover:bg-purple-100' }}">
                📱 Bab 85: Elektronik
            </button>
            <button type="button" wire:click="filterByChapter('39')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $selectedChapter === '39' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-emerald-50 text-emerald-900 border-emerald-200 hover:bg-emerald-100' }}">
                🧪 Bab 39: Plastik
            </button>
            <button type="button" wire:click="filterByChapter('73')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $selectedChapter === '73' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-amber-50 text-amber-900 border-amber-200 hover:bg-amber-100' }}">
                🔩 Bab 73: Besi Baja
            </button>
            <button type="button" wire:click="filterByChapter('87')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $selectedChapter === '87' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-indigo-50 text-indigo-900 border-indigo-200 hover:bg-indigo-100' }}">
                🚗 Bab 87: Otomotif
            </button>
            <button type="button" wire:click="filterByChapter('09')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $selectedChapter === '09' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-rose-50 text-rose-900 border-rose-200 hover:bg-rose-100' }}">
                ☕ Bab 09: Kopi & Rempah
            </button>
        </div>
    </div>

    {{-- HIERARCHY DETAIL PANEL (WHEN SELECTED) --}}
    @if($selectedCode && is_array($hierarchy) && count($hierarchy) > 0)
    <div class="bg-white rounded-3xl shadow-xl border-2 border-emerald-500 overflow-hidden transition-all duration-300">
        <div class="bg-gradient-to-r from-emerald-800 to-teal-900 text-white p-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">📊</span>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-emerald-200">Hierarki Klasifikasi Kepabeanan</div>
                    <div class="text-lg font-black flex items-center gap-2">
                        <span class="font-mono bg-emerald-950/60 px-2.5 py-0.5 rounded border border-emerald-400/30">{{ \App\Models\HsCode::formatDisplay($selectedCode) }}</span>
                        @if(isset($hierarchy['current']->description_id))
                            <span class="text-sm font-medium text-emerald-100 truncate max-w-md hidden sm:inline">• {{ $hierarchy['current']->description_id }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <button type="button" wire:click="closeHierarchy" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 px-3.5 py-1.5 rounded-xl text-xs font-bold transition">
                ✕ Tutup
            </button>
        </div>

        <div class="p-6 space-y-5 bg-slate-50/70">
            {{-- Section & Chapter --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(isset($hierarchy['section']))
                <div class="bg-amber-50/80 border border-amber-200 rounded-2xl p-4 space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-900">📦 Bagian {{ $hierarchy['section']['number'] }}</span>
                    <div class="text-sm font-bold text-amber-950">{{ $hierarchy['section']['title_id'] }}</div>
                    @if(!empty($hierarchy['section']['title_en']))
                        <div class="text-xs italic text-amber-700">{{ $hierarchy['section']['title_en'] }}</div>
                    @endif
                </div>
                @endif

                @if(isset($hierarchy['chapter']))
                <div class="bg-blue-50/80 border border-blue-200 rounded-2xl p-4 space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-900">📁 Bab {{ $hierarchy['chapter']['number'] }}</span>
                    <div class="text-sm font-bold text-blue-950">{{ $hierarchy['chapter']['title_id'] }}</div>
                    @if(!empty($hierarchy['chapter']['title_en']))
                        <div class="text-xs italic text-blue-700">{{ $hierarchy['chapter']['title_en'] }}</div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Tree Path --}}
            @if(isset($hierarchy['levels']) && count($hierarchy['levels']) > 0)
            <div class="space-y-2">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tingkatan Klasifikasi Pos:</div>
                <div class="space-y-2">
                    @foreach($hierarchy['levels'] as $level)
                    <div class="p-4 rounded-2xl border transition {{ $level['is_selected'] ? 'bg-emerald-50 border-emerald-500 shadow-sm ring-1 ring-emerald-400' : 'bg-white border-slate-200' }}" style="margin-left: {{ ($level['level'] - 4) * 16 }}px">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-bold {{ $level['is_selected'] ? 'text-emerald-800' : 'text-slate-800' }}">{{ $level['code'] }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $level['level'] == 8 ? 'bg-emerald-100 text-emerald-800' : ($level['level'] == 6 ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $level['level_label'] }}
                                </span>
                                @if($level['is_selected'])
                                    <span class="text-[10px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">← Pos Terpilih</span>
                                @endif
                            </div>

                            @if(isset($level['import_duty']) && $level['level'] == 8)
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-600 text-white">
                                    BM: {{ $level['import_duty'] }}{{ is_numeric($level['import_duty']) ? '%' : '' }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-200 text-slate-800">
                                    PPN: {{ $level['ppn'] ?? '11' }}{{ is_numeric($level['ppn'] ?? '11') ? '%' : '' }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-100 text-teal-800">
                                    PPh: {{ $level['pph_api'] ?? '2.5' }}%
                                </span>
                            </div>
                            @endif
                        </div>
                        <div class="mt-2 text-xs text-slate-800 font-medium leading-relaxed">{{ $level['description_id'] }}</div>
                        @if(!empty($level['description_en']))
                            <div class="mt-0.5 text-xs text-slate-500 italic">{{ $level['description_en'] }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Siblings --}}
            @if(isset($hierarchy['siblings']) && count($hierarchy['siblings']) > 0)
            <div class="pt-3 border-t border-slate-200 space-y-2">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pos Terkait Dalam Subpos yang Sama:</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($hierarchy['siblings'] as $sib)
                    <button 
                        type="button" 
                        wire:click="showHierarchy('{{ $sib->hs_code }}')" 
                        class="px-3 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-mono text-xs rounded-xl transition"
                        title="{{ $sib->description_id }}"
                    >
                        {{ \App\Models\HsCode::formatDisplay($sib->hs_code) }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Explanatory Notes --}}
            @php $enote = $this->getExplanatoryNote($selectedCode); @endphp
            @if($enote)
            <div class="pt-3 border-t border-slate-200" x-data="{ expandedNote: false }">
                <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-900 flex items-center gap-1.5">
                            <span>📖</span> Catatan Penjelasan Resmi (Explanatory Notes)
                        </span>
                        <span class="text-[10px] text-purple-700 bg-purple-100 px-2 py-0.5 rounded">Ref: {{ $enote->hs_code }}</span>
                    </div>
                    @if($enote->note_title)
                        <div class="text-xs font-bold text-purple-950">{{ $enote->note_title }}</div>
                    @endif
                    <div class="text-xs text-purple-950 leading-relaxed">
                        <div x-show="!expandedNote">{{ Str::limit($enote->note_content, 350) }}</div>
                        <div x-show="expandedNote" x-cloak class="whitespace-pre-wrap">{{ $enote->note_content }}</div>
                    </div>
                    @if(strlen($enote->note_content) > 350)
                    <button type="button" @click="expandedNote = !expandedNote" class="text-xs font-bold text-purple-700 hover:text-purple-900 transition">
                        <span x-show="!expandedNote">Baca Selengkapnya ➔</span>
                        <span x-show="expandedNote">Tutup Catatan Penjelasan</span>
                    </button>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- LOADING STATE --}}
    <div wire:loading class="w-full py-12 text-center">
        <div class="inline-flex items-center gap-3 px-6 py-3 rounded-2xl bg-white shadow-md border border-slate-200 text-slate-700 text-sm font-semibold">
            <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            <span>Memuat data klasifikasi...</span>
        </div>
    </div>

    {{-- RESULTS TABLE / LIST --}}
    <div wire:loading.remove>
        @if($results->count() > 0)
        <div class="space-y-3">
            <div class="flex items-center justify-between text-xs text-slate-500 font-semibold px-1">
                <span>Menampilkan {{ number_format($results->firstItem()) }} - {{ number_format($results->lastItem()) }} dari {{ number_format($results->total()) }} pos tarif</span>
                <span>Halaman {{ $results->currentPage() }} dari {{ $results->lastPage() }}</span>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                <th class="py-4 px-5 w-44">KODE HS & LEVEL</th>
                                <th class="py-4 px-4">URAIAN BARANG (ID & EN)</th>
                                <th class="py-4 px-3 text-center w-36">BEA MASUK (MFN)</th>
                                <th class="py-4 px-3 text-center w-36">PPN & PPH</th>
                                <th class="py-4 px-3 text-center w-28">BEA KELUAR</th>
                                <th class="py-4 px-4 text-center w-24">HIERARKI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($results as $item)
                            <tr class="hover:bg-slate-50/60 transition {{ $selectedCode === $item->hs_code ? 'bg-emerald-50/40' : '' }}">
                                {{-- Code --}}
                                <td class="py-4 px-5 align-top">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono font-black text-slate-900 tracking-tight text-sm">{{ \App\Models\HsCode::formatDisplay($item->hs_code) }}</span>
                                            <button 
                                                type="button" 
                                                @click="navigator.clipboard.writeText('{{ \App\Models\HsCode::formatDisplay($item->hs_code) }}'); copyTooltip = '{{ $item->hs_code }}'; setTimeout(() => copyTooltip = null, 1500)"
                                                class="text-slate-400 hover:text-blue-600 transition p-1 rounded" 
                                                title="Salin Kode HS"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            </button>
                                        </div>
                                        <div x-show="copyTooltip === '{{ $item->hs_code }}'" x-cloak class="text-[10px] text-emerald-600 font-bold">
                                            ✓ Tersalin!
                                        </div>

                                        <div class="flex items-center gap-1 flex-wrap">
                                            @if($item->hs_level == 8)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    8-Digit
                                                </span>
                                            @elseif($item->hs_level == 6)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800">
                                                    Subpos 6
                                                </span>
                                            @elseif($item->hs_level == 4)
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    Pos 4
                                                </span>
                                            @endif

                                            @if($item->chapter_number)
                                                <span class="px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600">
                                                    Bab {{ $item->chapter_number }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Description --}}
                                <td class="py-4 px-4 align-top">
                                    <div class="space-y-1">
                                        <div class="font-bold text-slate-900 leading-snug">
                                            {{ $item->description_id ?: '-' }}
                                        </div>
                                        @if(!empty($item->description_en) && $item->description_en !== $item->description_id)
                                            <div class="text-xs text-slate-500 italic leading-snug">
                                                {{ $item->description_en }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Bea Masuk --}}
                                <td class="py-4 px-3 align-top text-center">
                                    @if($item->hs_level == 8 && $item->import_duty !== null && $item->import_duty !== '')
                                        @php
                                            $bmVal = trim($item->import_duty);
                                            $isZero = ($bmVal === '0' || $bmVal === '0%');
                                            $isNumeric = is_numeric(str_replace(['%', ','], ['', '.'], $bmVal));
                                        @endphp
                                        <div class="inline-flex flex-col items-center">
                                            @if($isZero)
                                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    0% (Bebas)
                                                </span>
                                            @elseif($bmVal === '5' || $bmVal === '5%')
                                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-100 text-blue-800 border border-blue-200">
                                                    5%
                                                </span>
                                            @elseif($isNumeric)
                                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-amber-100 text-amber-900 border border-amber-200">
                                                    {{ $bmVal }}{{ !str_contains($bmVal, '%') ? '%' : '' }}
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-200">
                                                    {{ $bmVal }}
                                                </span>
                                            @endif
                                            <span class="text-[9px] text-slate-400 mt-0.5">MFN Rate</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Pos Induk</span>
                                    @endif
                                </td>

                                {{-- PPN & PPh --}}
                                <td class="py-4 px-3 align-top text-center">
                                    @if($item->hs_level == 8)
                                        <div class="space-y-1 inline-flex flex-col items-center">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                                PPN: {{ $item->ppn ?? '11' }}{{ is_numeric($item->ppn ?? '11') ? '%' : '' }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-teal-50 text-teal-800 border border-teal-200" title="PPh Pasal 22 Impor: 2.5% dengan API / 7.5% tanpa API">
                                                PPh: {{ $item->pph_api ?? '2.5' }}% / {{ $item->pph_non_api ?? '7.5' }}%
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-300">-</span>
                                    @endif
                                </td>

                                {{-- Bea Keluar --}}
                                <td class="py-4 px-3 align-top text-center" x-data="{ bkTooltip: false }">
                                    @if($item->hs_level == 8 && !empty($item->export_duty) && $item->export_duty !== '-')
                                        <div class="relative inline-block">
                                            <span 
                                                @mouseenter="bkTooltip = true" 
                                                @mouseleave="bkTooltip = false" 
                                                class="px-2 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 cursor-help"
                                            >
                                                {{ $item->export_duty }}
                                            </span>
                                            <div 
                                                x-show="bkTooltip" 
                                                x-cloak 
                                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-52 p-2.5 bg-slate-900 text-white text-[11px] rounded-xl shadow-xl z-50 text-left leading-normal"
                                            >
                                                <div class="font-bold text-amber-300 mb-1">Tarif BK Khusus/HPE</div>
                                                <div>Mengikuti ketentuan PMK berdasarkan Harga Patokan Ekspor (HPE) berkala.</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Bebas (-)</span>
                                    @endif
                                </td>

                                {{-- Action --}}
                                <td class="py-4 px-4 align-top text-center">
                                    <button 
                                        type="button" 
                                        wire:click="showHierarchy('{{ $item->hs_code }}')" 
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-600 text-slate-700 hover:text-white text-xs font-bold transition shadow-sm"
                                        title="Buka Hierarki & Catatan Bab"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        <span>Detail</span>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-5 border-t border-slate-200 bg-slate-50/50">
                    {{ $results->links() }}
                </div>
            </div>
        </div>
        @else
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm space-y-3">
            <div class="text-5xl">🔍</div>
            <h3 class="text-base font-bold text-slate-800">Tidak ada kode HS yang cocok</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto">
                Coba gunakan kata kunci umum (contoh: <span class="font-medium text-slate-700">mesin, pompa, kabel, beras</span>) atau cari berdasarkan nomor pos 4-digit.
            </p>
            <button type="button" wire:click="resetFilters" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-md">
                Reset Pencarian
            </button>
        </div>
        @endif
    </div>

    {{-- KUM HS MODAL OVERLAY --}}
    @if($showKumModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" wire:click="toggleKumModal"></div>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-100">
                <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 p-6 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">📚</span>
                        <div>
                            <h3 class="text-lg font-black tracking-tight text-white">KUM HS - Ketentuan Umum Menginterpretasikan HS</h3>
                            <p class="text-xs text-emerald-200/90">Panduan resmi Bea Cukai untuk mengklasifikasikan barang secara sah</p>
                        </div>
                    </div>
                    <button type="button" wire:click="toggleKumModal" class="text-white/70 hover:text-white text-2xl font-bold leading-none p-2">&times;</button>
                </div>

                <div class="p-6 max-h-[70vh] overflow-y-auto space-y-4 bg-slate-50">
                    @foreach($kumRules as $rule)
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-2">
                        <div class="text-sm font-black text-emerald-800 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 inline-flex items-center justify-center text-xs font-bold">{{ $rule->rule_number }}</span>
                            <span>{{ $rule->title }}</span>
                        </div>
                        <div class="text-xs text-slate-700 leading-relaxed pl-8">
                            {{ $rule->content }}
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="bg-slate-100 px-6 py-4 flex justify-between items-center border-t border-slate-200">
                    <span class="text-xs text-slate-500 font-medium">Sumber: PMK 26/PMK.010/2022 (BTKI 2022)</span>
                    <button type="button" wire:click="toggleKumModal" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-2xl text-xs font-bold transition">
                        Tutup Panduan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- DISCLAIMER --}}
    <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200/80 text-xs text-slate-600 space-y-2 leading-relaxed">
        <div class="flex items-center gap-2 font-bold text-slate-800">
            <span class="text-amber-500">⚠️</span>
            <span>Disclaimer - Penyangkalan Hukum Tarif</span>
        </div>
        <p>
            Data tarif Bea Masuk (MFN), Bea Keluar (BK), PPN, dan PPh Impor yang ditampilkan bersumber dari <b>Buku Tarif Kepabeanan Indonesia (BTKI 2022)</b> dan bersifat informatif untuk estimasi landed cost Anda.
        </p>
        <p>
            Untuk verifikasi tarif resmi, ketentuan LARTAS, serta pengajuan Certificate of Origin (Form D / Form E / Form AK), silakan menghubungi tim operasional M2B atau kunjungi portal resmi:
            <a href="https://insw.go.id/intr" target="_blank" rel="noopener noreferrer" class="text-blue-600 font-bold hover:underline">INSW Portal</a> |
            <a href="https://www.beacukai.go.id" target="_blank" rel="noopener noreferrer" class="text-blue-600 font-bold hover:underline">Bea Cukai RI</a>.
        </p>
    </div>
</div>
