<div class="hs-code-search-container">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-search"></i> Pencarian HS Code BTKI 2022
                </h5>
                <div>
                    <a href="#" onclick="window.open('/hs-code/general-rules', '_blank')" class="btn btn-sm btn-light">
                        <i class="fas fa-book"></i> Ketentuan Umum
                    </a>
                    <a href="#" onclick="window.open('/hs-code/explanatory-notes', '_blank')" class="btn btn-sm btn-light">
                        <i class="fas fa-file-alt"></i> Explanatory Notes
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Search Form --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-bold">Cari HS Code atau Deskripsi</label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                class="form-control form-control-lg" 
                                wire:model.debounce.500ms="search"
                                placeholder="Contoh: 0101 atau ikan atau fish..."
                                autofocus
                            >
                            <button 
                                class="btn btn-primary" 
                                wire:click="performSearch"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="performSearch">
                                    <i class="fas fa-search"></i> Cari
                                </span>
                                <span wire:loading wire:target="performSearch">
                                    <i class="fas fa-spinner fa-spin"></i> Mencari...
                                </span>
                            </button>
                        </div>
                        <small class="text-muted">Minimal 2 karakter untuk pencarian</small>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label fw-bold">Mode Pencarian</label>
                        <select class="form-select" wire:model="searchMode">
                            <option value="all">Semua</option>
                            <option value="code">Hanya Kode</option>
                            <option value="description">Hanya Deskripsi</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label fw-bold">Filter Bab</label>
                        <select class="form-select" wire:model="selectedChapter">
                            <option value="">Semua Bab</option>
                            @foreach($chapters as $chapter)
                                <option value="{{ $chapter->chapter_number }}">
                                    Bab {{ $chapter->chapter_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label fw-bold">Bahasa</label>
                        <select class="form-select" wire:model="language">
                            <option value="id">Indonesia</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Popular Searches --}}
            @if(count($popularSearches) > 0 && !$search)
                <div class="mb-3">
                    <small class="text-muted">Pencarian populer:</small>
                    @foreach($popularSearches as $popular)
                        <button 
                            class="btn btn-sm btn-outline-secondary ms-1" 
                            wire:click="$set('search', '{{ $popular }}')"
                        >
                            {{ $popular }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Loading State --}}
            <div wire:loading wire:target="performSearch" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Mencari data...</p>
            </div>

            {{-- Search Results --}}
            @if($showResults && !$isLoading)
                <div class="search-results">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">
                            Hasil Pencarian: <span class="badge bg-primary">{{ $results->count() }}</span>
                        </h6>
                        <div>
                            <button class="btn btn-sm btn-success" wire:click="exportResults">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                            <button class="btn btn-sm btn-secondary" wire:click="resetFilters">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>

                    @if($results->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 150px;">HS Code</th>
                                        <th style="width: 100px;">Level</th>
                                        <th>Deskripsi</th>
                                        <th style="width: 80px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $result)
                                        <tr>
                                            <td>
                                                <code class="fs-6">{{ $result->getFormattedCode() }}</code>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    {{ $result->level_name }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($language === 'id')
                                                    {{ $result->description_id }}
                                                @else
                                                    {{ $result->description_en ?: $result->description_id }}
                                                @endif
                                            </td>
                                            <td>
                                                <button 
                                                    class="btn btn-sm btn-primary" 
                                                    wire:click="selectCode('{{ $result->hs_code }}')"
                                                    title="Lihat Detail"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Tidak ada hasil untuk "{{ $search }}"
                        </div>
                    @endif
                </div>
            @endif

            {{-- Selected Code Detail --}}
            @if($showHierarchy && $selectedCode)
                <div class="selected-code-detail">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Detail HS Code</h6>
                        <div>
                            @auth
                                <button 
                                    class="btn btn-sm {{ $isFavorite ? 'btn-warning' : 'btn-outline-warning' }}" 
                                    wire:click="toggleFavorite"
                                >
                                    <i class="fas fa-star"></i> 
                                    {{ $isFavorite ? 'Hapus dari Favorit' : 'Tambah ke Favorit' }}
                                </button>
                            @endauth
                            <button class="btn btn-sm btn-secondary" wire:click="clearSelection">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </button>
                        </div>
                    </div>

                    {{-- Hierarchy Breadcrumb --}}
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb">
                            @foreach($hierarchy as $item)
                                <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                    @if(!$loop->last)
                                        <a href="#" wire:click="selectCode('{{ $item['hs_code'] }}')">
                                            <code>{{ $item['hs_code'] }}</code>
                                        </a>
                                    @else
                                        <code class="fs-6">{{ $item['hs_code'] }}</code>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    {{-- Main Info Card --}}
                    <div class="card mb-3 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <code class="text-white">{{ $selectedCode->getFormattedCode() }}</code>
                                <span class="badge bg-light text-dark ms-2">{{ $selectedCode->level_name }}</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Deskripsi (Indonesia)</h6>
                                    <p>{{ $selectedCode->description_id }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Description (English)</h6>
                                    <p>{{ $selectedCode->description_en ?: '-' }}</p>
                                </div>
                            </div>

                            @if($selectedCode->has_explanatory_note)
                                <div class="mt-3">
                                    <a href="{{ $selectedCode->explanatory_note_url ?: '#' }}" 
                                       target="_blank" 
                                       class="btn btn-info">
                                        <i class="fas fa-book-open"></i> Lihat Explanatory Note
                                    </a>
                                </div>
                            @endif

                            @if($selectedCode->notes)
                                <div class="mt-3">
                                    <h6 class="text-muted">Catatan</h6>
                                    <div class="alert alert-info">
                                        {{ $selectedCode->notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Children (Sub-categories) --}}
                    @if($children->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-sitemap"></i> Sub-kategori ({{ $children->count() }})
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    @foreach($children as $child)
                                        <a href="#" 
                                           wire:click.prevent="selectCode('{{ $child->hs_code }}')"
                                           class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <code>{{ $child->getFormattedCode() }}</code>
                                                    <span class="ms-2">
                                                        @if($language === 'id')
                                                            {{ $child->description_id }}
                                                        @else
                                                            {{ $child->description_en ?: $child->description_id }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <span class="badge bg-secondary">{{ $child->level_name }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.hs-code-search-container {
    max-width: 1200px;
    margin: 0 auto;
}

code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    font-size: 1.2rem;
}
</style>
