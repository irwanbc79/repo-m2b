@props(['previewDoc'])

@php
    $ext = strtolower(pathinfo($previewDoc->filename, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico']);
    $isPdf = $ext === 'pdf';
    $isDocx = in_array($ext, ['docx']);
    $isXlsx = in_array($ext, ['xlsx', 'xls', 'csv']);
    $isTxt = in_array($ext, ['txt', 'log', 'json', 'xml']);
    $docUrl = route('document.view', $previewDoc->id);
    $assetsBase = asset('vendor/doc-viewer');
@endphp

<div class="w-full h-full flex flex-col items-center justify-center overflow-hidden relative" wire:key="viewer-container-{{ $previewDoc->id }}-{{ $ext }}">
    @if($isImage)
        {{-- IMAGE PREVIEW --}}
        <div class="w-full h-full overflow-auto flex items-center justify-center p-4 select-none">
            <div class="flex items-center justify-center min-w-full min-h-full">
                <img 
                    src="{{ $docUrl }}" 
                    alt="{{ $previewDoc->filename }}"
                    :style="`transform: scale(${zoom/100}) rotate(${rotation}deg); transition: transform 0.2s ease;`"
                    class="max-h-[75vh] max-w-[85vw] object-contain rounded-lg shadow-2xl transition-transform"
                >
            </div>
        </div>

    @elseif($isPdf)
        {{-- PDF PREVIEW --}}
        <div class="w-full h-full overflow-hidden flex items-center justify-center bg-slate-900">
            <iframe 
                src="{{ $docUrl }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH" 
                class="w-full h-full border-0 bg-white rounded-lg shadow-xl"
                :style="`transform: scale(${zoom/100}); transform-origin: top center; transition: transform 0.2s ease;`"
            ></iframe>
        </div>

    @elseif($isDocx)
        {{-- DOCX PREVIEW --}}
        <div 
            x-data="docxViewerComponent('{{ $docUrl }}', 'docx-render-{{ $previewDoc->id }}', '{{ $assetsBase }}')" 
            x-init="load()"
            class="w-full h-full flex flex-col items-center overflow-auto p-4 md:p-6 bg-slate-900/60"
        >
            {{-- Loading State --}}
            <div x-show="loading" class="flex flex-col items-center justify-center my-auto py-12 text-slate-300">
                <div class="relative w-12 h-12 mb-3">
                    <div class="w-12 h-12 rounded-full border-4 border-blue-500/20 border-t-blue-500 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-blue-400">W</span>
                    </div>
                </div>
                <p class="text-sm font-semibold tracking-wide text-slate-200">Memuat Dokumen Word (.docx)...</p>
                <p class="text-xs text-slate-400 mt-1">Merender tata letak dokumen di browser...</p>
            </div>

            {{-- Error State --}}
            <div x-show="error" x-cloak class="flex flex-col items-center justify-center my-auto text-center p-6 bg-slate-800/90 rounded-2xl border border-slate-700 max-w-md shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-amber-500/10 text-amber-400 flex items-center justify-center mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h4 class="text-white font-bold text-base mb-1">Gagal Menampilkan Preview Word</h4>
                <p class="text-slate-400 text-xs mb-4" x-text="errorMsg"></p>
                <a href="{{ route('document.download', $previewDoc->id) }}" download class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 font-bold text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download File DOCX
                </a>
            </div>

            {{-- Render Target Host --}}
            <div 
                x-show="!loading && !error"
                id="docx-render-{{ $previewDoc->id }}"
                :style="`transform: scale(${zoom/100}); transform-origin: top center; transition: transform 0.2s ease;`"
                class="docx-render-host w-full flex flex-col items-center transition-transform py-4"
            ></div>
        </div>

    @elseif($isXlsx)
        {{-- XLSX / SPREADSHEET PREVIEW --}}
        <div 
            x-data="xlsxViewerComponent('{{ $docUrl }}', '{{ $assetsBase }}')" 
            x-init="load()"
            class="w-full h-full flex flex-col bg-slate-950 rounded-lg overflow-hidden border border-slate-800"
        >
            {{-- Loading State --}}
            <div x-show="loading" class="flex flex-col items-center justify-center my-auto py-12 text-slate-300">
                <div class="relative w-12 h-12 mb-3">
                    <div class="w-12 h-12 rounded-full border-4 border-emerald-500/20 border-t-emerald-500 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-emerald-400">X</span>
                    </div>
                </div>
                <p class="text-sm font-semibold tracking-wide text-slate-200">Memuat File Excel ({{ strtoupper($ext) }})...</p>
                <p class="text-xs text-slate-400 mt-1">Membaca lembar kerja dan data tabel...</p>
            </div>

            {{-- Error State --}}
            <div x-show="error" x-cloak class="flex flex-col items-center justify-center my-auto text-center p-6 bg-slate-800/90 rounded-2xl border border-slate-700 max-w-md shadow-2xl mx-auto">
                <div class="w-14 h-14 rounded-full bg-amber-500/10 text-amber-400 flex items-center justify-center mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h4 class="text-white font-bold text-base mb-1">Gagal Membaca Spreadsheet</h4>
                <p class="text-slate-400 text-xs mb-4" x-text="errorMsg"></p>
                <a href="{{ route('document.download', $previewDoc->id) }}" download class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 font-bold text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download File Excel
                </a>
            </div>

            {{-- Loaded Spreadsheet Content --}}
            <div x-show="!loading && !error" class="w-full h-full flex flex-col min-h-0">
                {{-- Spreadsheet Toolbar: Sheets + Search + Stats --}}
                <div class="bg-slate-900 border-b border-slate-800 px-4 py-2 flex flex-wrap items-center justify-between gap-3 shrink-0">
                    {{-- Sheet Tabs --}}
                    <div class="flex items-center gap-1.5 overflow-x-auto max-w-full py-1">
                        <template x-for="name in sheetNames" :key="name">
                            <button 
                                type="button"
                                @click="selectSheet(name)"
                                :class="activeSheet === name ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-900/40' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white'"
                                class="px-3 py-1.5 rounded-md text-xs transition flex items-center gap-1.5 shrink-0 border border-slate-700/60"
                            >
                                <svg class="w-3.5 h-3.5 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm3 2h6v2H7V5zm0 4h6v2H7V9zm0 4h6v2H7v-2z"/></svg>
                                <span x-text="name"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Search Input & Info --}}
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <input 
                                type="text" 
                                x-model="searchQuery" 
                                placeholder="Cari data di sheet..." 
                                class="bg-slate-950 text-white text-xs rounded-lg pl-8 pr-3 py-1.5 border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none w-44 sm:w-56"
                            >
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono bg-slate-800 px-2 py-1 rounded" x-text="`${filteredRows.length} baris`"></span>
                    </div>
                </div>

                {{-- Table Container with Zoom --}}
                <div class="flex-1 overflow-auto bg-slate-950 p-3 min-h-0 relative">
                    <div :style="`transform: scale(${zoom/100}); transform-origin: top left; transition: transform 0.2s ease;`" class="inline-block min-w-full">
                        <table class="min-w-full border-collapse text-xs text-slate-200 border border-slate-800 shadow-xl">
                            <thead class="sticky top-0 z-10 bg-slate-900">
                                <tr class="border-b border-slate-700">
                                    <th class="p-2 border border-slate-800 bg-slate-900 text-slate-400 font-mono text-[10px] w-12 text-center select-none">#</th>
                                    <template x-for="cIdx in maxCols" :key="cIdx">
                                        <th class="p-2 border border-slate-700/80 text-center font-bold text-slate-300 bg-slate-800 text-[11px] select-none min-w-[100px]" x-text="getColHeader(cIdx - 1)"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, rIdx) in filteredRows" :key="rIdx">
                                    <tr class="border-b border-slate-800/60 hover:bg-blue-600/10 transition-colors" :class="rIdx % 2 === 0 ? 'bg-slate-950' : 'bg-slate-900/30'">
                                        <td class="p-1.5 border border-slate-800/60 text-slate-500 font-mono text-[10px] text-center bg-slate-900/40 select-none" x-text="rIdx + 1"></td>
                                        <template x-for="cIdx in maxCols" :key="cIdx">
                                            <td 
                                                class="p-2 border border-slate-800/60 whitespace-nowrap max-w-md truncate text-xs"
                                                :class="typeof row[cIdx - 1] === 'number' ? 'text-right font-mono' : 'text-left'"
                                                :title="row[cIdx - 1] !== undefined ? row[cIdx - 1] : ''"
                                                x-text="row[cIdx - 1] !== undefined ? row[cIdx - 1] : ''"
                                            ></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    @elseif($isTxt)
        {{-- TEXT / CODE PREVIEW --}}
        <div 
            x-data="txtViewerComponent('{{ $docUrl }}')" 
            x-init="load()"
            class="w-full h-full flex flex-col bg-slate-950 rounded-lg overflow-hidden border border-slate-800 p-4"
        >
            <div x-show="loading" class="flex flex-col items-center justify-center my-auto py-12 text-slate-300">
                <div class="w-8 h-8 rounded-full border-4 border-blue-500/20 border-t-blue-500 animate-spin mb-3"></div>
                <p class="text-xs text-slate-300">Memuat teks...</p>
            </div>
            <div x-show="!loading && !error" class="flex-1 overflow-auto">
                <pre class="font-mono text-xs text-slate-200 whitespace-pre-wrap leading-relaxed select-text" x-text="content"></pre>
            </div>
        </div>

    @else
        {{-- OTHER FILE TYPES (FALLBACK) --}}
        <div class="text-center text-white p-8 max-w-md mx-auto bg-slate-900/60 rounded-2xl border border-slate-800 shadow-2xl">
            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-400">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold mb-1 text-white">Preview Tidak Tersedia</h3>
            <p class="text-gray-400 text-xs mb-2">
                File dengan format <span class="text-amber-400 font-mono font-bold">.{{ $ext }}</span> tidak dapat di-preview langsung di browser.
            </p>
            @if($ext === 'doc')
                <p class="text-[11px] text-slate-400 mb-6 bg-slate-800/60 p-2.5 rounded-lg border border-slate-700/60 text-left">
                    💡 <strong>Tips:</strong> Format <code>.doc</code> adalah Word versi lama (97-2003). Silakan unduh dokumen untuk membukanya di Microsoft Word atau simpan ulang sebagai <code>.docx</code> untuk pratinjau browser langsung.
                </p>
            @else
                <p class="text-[11px] text-slate-400 mb-6">
                    Silakan unduh dokumen untuk membukanya menggunakan aplikasi di perangkat Anda.
                </p>
            @endif
            <a href="{{ route('document.download', $previewDoc->id) }}" download class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-6 py-3 font-bold text-sm transition shadow-lg shadow-blue-900/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download File (.{{ strtoupper($ext) }})
            </a>
        </div>
    @endif
</div>

<style>
.docx-render-host .docx-wrapper {
    background: transparent !important;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.docx-render-host .docx-wrapper > section.docx {
    background: #ffffff !important;
    color: #0f172a !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5) !important;
    margin-bottom: 24px !important;
    border-radius: 6px !important;
    box-sizing: border-box !important;
}
.docx-render-host .docx-wrapper > section.docx table {
    border-collapse: collapse !important;
    width: auto !important;
}
.docx-render-host .docx-wrapper > section.docx table td,
.docx-render-host .docx-wrapper > section.docx table th {
    border: 1px solid #cbd5e1 !important;
    padding: 4px 8px !important;
}
</style>
