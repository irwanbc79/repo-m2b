<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        HS Code Explorer
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Pencarian & Browse Kode HS BTKI 2022 - Total {{ number_format(DB::table('hs_codes')->count()) }} kode
                    </p>
                </div>
                
                {{-- Mode Switcher --}}
                <div class="flex gap-2">
                    <button 
                        wire:click="switchToSearch"
                        class="px-4 py-2 rounded-lg font-medium transition {{ $viewMode === 'search' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                        🔍 Cari
                    </button>
                    <button 
                        wire:click="switchToBrowse"
                        class="px-4 py-2 rounded-lg font-medium transition {{ $viewMode === 'browse' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                        📚 Browse
                    </button>
                </div>
            </div>
        </div>

        {{-- Search Mode --}}
        @if($viewMode === 'search')
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari kode HS atau deskripsi barang... (contoh: 'kuda', '01.01', 'susu')"
                        class="w-full px-5 py-4 pr-12 text-lg border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                    >
                    <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                @if(!empty($search))
                    <div class="mt-4 flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            Hasil pencarian untuk: <span class="font-semibold">"{{ $search }}"</span>
                        </p>
                        <button wire:click="$set('search', '')" class="text-sm text-blue-600 hover:text-blue-800">
                            Reset
                        </button>
                    </div>
                @endif
            </div>
        @endif

        {{-- Browse Mode --}}
        @if($viewMode === 'browse')
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Pilih Chapter</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($chapters as $chapter)
                        <button 
                            wire:click="$set('selectedChapter', '{{ $chapter->chapter_number }}')"
                            class="px-4 py-3 rounded-lg font-medium transition {{ $selectedChapter === $chapter->chapter_number ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $chapter->chapter_number }}
                        </button>
                    @endforeach
                </div>
                
                @if($selectedChapter)
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm font-medium text-blue-900">
                            📌 Chapter {{ $selectedChapter }} - 
                            {{ $chapters->firstWhere('chapter_number', $selectedChapter)->title_id ?? 'Loading...' }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Detail Mode --}}
        @if($viewMode === 'detail' && $selectedCode)
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <button 
                        wire:click="backToSearch"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        ← Kembali
                    </button>
                </div>

                {{-- Hierarchy Breadcrumb --}}
                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm font-medium text-gray-600 mb-2">Hierarki:</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($codeHierarchy as $index => $item)
                            @if($index > 0)
                                <span class="text-gray-400">›</span>
                            @endif
                            <button 
                                wire:click="selectCode('{{ $item->hs_code }}')"
                                class="px-3 py-1 rounded {{ $item->hs_code === $selectedCode->hs_code ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-blue-100' }} transition">
                                {{ $item->hs_code }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Current Code Details --}}
                <div class="border-l-4 border-blue-600 pl-6 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $selectedCode->hs_code }}</h2>
                            <p class="mt-2 text-gray-700 text-lg">{{ $selectedCode->description_id }}</p>
                        </div>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            Level {{ $selectedCode->hs_level }}
                        </span>
                    </div>
                    
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Chapter:</span>
                            <span class="ml-2 font-medium">{{ $selectedCode->chapter_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Parent:</span>
                            <span class="ml-2 font-medium">{{ $selectedCode->parent_code ?? 'None' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Children --}}
                @if($this->children->count() > 0)
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Sub-kategori ({{ $this->children->count() }})</h3>
                        <div class="space-y-2">
                            @foreach($this->children as $child)
                                <button 
                                    wire:click="selectCode('{{ $child->hs_code }}')"
                                    class="w-full text-left p-4 bg-gray-50 hover:bg-blue-50 rounded-lg transition group">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900 group-hover:text-blue-600">{{ $child->hs_code }}</p>
                                            <p class="text-sm text-gray-600 mt-1">{{ Str::limit($child->description_id, 100) }}</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Search/Browse Results --}}
        @if(in_array($viewMode, ['search', 'browse']) && $results->count() > 0)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <p class="text-sm text-gray-600">
                        Menampilkan {{ $results->firstItem() ?? 0 }} - {{ $results->lastItem() ?? 0 }} dari {{ number_format($results->total()) }} hasil
                    </p>
                </div>
                
                <div class="divide-y divide-gray-200">
                    @foreach($results as $code)
                        <button 
                            wire:click="selectCode('{{ $code->hs_code }}')"
                            class="w-full text-left p-6 hover:bg-blue-50 transition group">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono font-bold text-lg text-blue-600 group-hover:text-blue-800">
                                            {{ $code->hs_code }}
                                        </span>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">
                                            Level {{ $code->hs_level }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-gray-700">{{ $code->description_id }}</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 flex-shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($results->hasPages())
                    <div class="p-6 border-t border-gray-200">
                        {{ $results->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- No Results --}}
        @if(in_array($viewMode, ['search', 'browse']) && $results->count() === 0 && (!empty($search) || !empty($selectedChapter)))
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500 text-lg">Tidak ada hasil ditemukan</p>
                <p class="text-gray-400 mt-2">Coba dengan kata kunci lain</p>
            </div>
        @endif

        {{-- Empty State --}}
        @if($viewMode === 'search' && empty($search))
            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl shadow-lg p-12 text-center">
                <svg class="w-20 h-20 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Mulai Pencarian Kode HS</h3>
                <p class="text-gray-600 mb-6">Ketik kode HS atau deskripsi barang di atas</p>
                
                <div class="max-w-lg mx-auto bg-white rounded-lg p-4 text-left">
                    <p class="text-sm font-medium text-gray-700 mb-2">💡 Contoh pencarian:</p>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• <code class="bg-gray-100 px-2 py-1 rounded">01.01</code> - Cari kode spesifik</li>
                        <li>• <code class="bg-gray-100 px-2 py-1 rounded">kuda</code> - Cari berdasarkan nama barang</li>
                        <li>• <code class="bg-gray-100 px-2 py-1 rounded">susu</code> - Cari produk dairy</li>
                    </ul>
                </div>
            </div>
        @endif

    </div>
</div>
