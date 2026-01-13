<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">HS Code Explorer</h1>
            <p class="text-gray-600">Pencarian Kode HS BTKI 2022</p>
        </div>

        {{-- Search Box --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <input 
                type="text" 
                wire:model.live.debounce.500ms="search"
                placeholder="Cari kode HS atau deskripsi barang..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
            >
            
            @if(!empty($search))
                <div class="mt-3 text-sm text-gray-600">
                    Hasil pencarian: "{{ $search }}"
                </div>
            @endif
        </div>

        {{-- Results --}}
        @if($results->count() > 0)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="divide-y divide-gray-200">
                    @foreach($results as $code)
                        <div class="p-4 hover:bg-gray-50">
                            <div class="font-bold text-blue-600">{{ $code->hs_code }}</div>
                            <div class="text-gray-700 mt-1">{{ $code->description_id }}</div>
                            <div class="text-xs text-gray-500 mt-1">Level {{ $code->hs_level }}</div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Pagination --}}
                <div class="p-4 border-t border-gray-200">
                    {{ $results->links() }}
                </div>
            </div>
        @elseif(!empty($search))
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <p class="text-gray-500">Tidak ada hasil ditemukan untuk "{{ $search }}"</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <p class="text-gray-600">Ketik kata kunci untuk mencari kode HS</p>
                <p class="text-sm text-gray-500 mt-2">Contoh: "kuda", "susu", "01.01"</p>
            </div>
        @endif

    </div>
</div>
