@extends('layouts.admin')

@section('title', 'Dokumentasi ' . ($shipment->awb_number ?: $shipment->bl_number ?: 'Shipment #'.$shipment->id))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css">
<style>
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    @media (max-width: 640px) {
        .photo-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
    }
    .photo-card {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        border-radius: 0.75rem;
        background: #f3f4f6;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .photo-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .photo-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.7));
        padding: 2rem 0.75rem 0.75rem;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .photo-card:hover .photo-overlay { opacity: 1; }
    .photo-badge {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        display: flex;
        gap: 0.25rem;
    }
    .photo-delete-btn {
        position: absolute;
        bottom: 0.5rem;
        right: 0.5rem;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .photo-card:hover .photo-delete-btn { opacity: 1; }
    .photo-checkbox {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        z-index: 10;
    }
    .photo-card.selected {
        box-shadow: 0 0 0 3px #3b82f6;
    }
    /* GLightbox */
    .glightbox-container .gnext,
    .glightbox-container .gprev {
        width: 60px !important;
        height: 60px !important;
        background: rgba(255, 255, 255, 0.95) !important;
        border-radius: 50% !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
        opacity: 1 !important;
    }
    .glightbox-container .gprev { left: 20px !important; }
    .glightbox-container .gnext { right: 20px !important; }
    .glightbox-container .gclose {
        width: 50px !important;
        height: 50px !important;
        background: rgba(255, 255, 255, 0.95) !important;
        border-radius: 50% !important;
        top: 20px !important;
        right: 20px !important;
        opacity: 1 !important;
    }

    /* Hide photo grid - hanya tampilkan tabel */
    .photo-grid-section { display: none !important; }
    
    /* Table styling */
    .photo-table { width: 100%; border-collapse: collapse; }
    .photo-table th { background: #f3f4f6; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; }
    .photo-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
    .photo-table tr:hover { background: #f9fafb; }
    .photo-table .preview-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; }
    .photo-table .preview-img:hover { opacity: 0.8; transform: scale(1.05); }

</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    📸 Dokumentasi {{ $shipment->awb_number ?: $shipment->bl_number ?: 'Shipment #'.$shipment->id }}
                </h1>
                <p class="text-gray-500 mt-1">{{ $shipment->customer->company_name ?? 'N/A' }}</p>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <div class="text-center px-4 py-2 bg-gray-50 rounded-lg">
                    <span class="block text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</span>
                    <span class="text-gray-500">📷 foto</span>
                </div>
                <div class="text-center px-4 py-2 bg-gray-50 rounded-lg">
                    <span class="block text-2xl font-bold text-green-600">{{ $stats['with_location'] ?? 0 }}</span>
                    <span class="text-gray-500">📍 GPS</span>
                </div>
                <div class="text-center px-4 py-2 bg-gray-50 rounded-lg">
                    <span class="block text-2xl font-bold text-blue-600">{{ $stats['today'] ?? 0 }}</span>
                    <span class="text-gray-500">🕐 hari ini</span>
                </div>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-3 mt-6">
            <a href="{{ route('admin.field-docs.upload', $shipment->awb_number ?: $shipment->id) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                ➕ Tambah Foto
            </a>
            <a href="{{ route('admin.field-docs.qr', $shipment->awb_number ?: $shipment->id) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                📱 QR Code
            </a>
            @if($canDelete ?? false)
            <button type="button" id="bulk-delete-btn" onclick="bulkDeletePhotos()"
                    class="hidden items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                🗑️ <span id="bulk-delete-count">Hapus (0)</span>
            </button>
            <button type="button" id="toggle-select-btn" onclick="toggleSelectMode()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded-lg">
                ☑️ <span id="select-mode-text">Pilih Foto</span>
            </button>
            @endif
            <a href="{{ route('admin.field-docs.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                ← Kembali
            </a>
        </div>
    </div>

    {{-- Filter & Actions Section --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Date Filter --}}
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">📅 Tanggal:</label>
                    <select id="filter-date" onchange="applyFilters()" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua</option>
                        <option value="today">Hari Ini</option>
                        <option value="week">7 Hari Terakhir</option>
                        <option value="month">30 Hari Terakhir</option>
                    </select>
                </div>
                
                {{-- User Filter --}}
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">👤 Petugas:</label>
                    <select id="filter-user" onchange="applyFilters()" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua</option>
                        @php $users = $photos->pluck('user')->unique('id')->filter(); @endphp
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- GPS Filter --}}
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">📍 GPS:</label>
                    <select id="filter-gps" onchange="applyFilters()" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua</option>
                        <option value="with">Dengan GPS</option>
                        <option value="without">Tanpa GPS</option>
                    </select>
                </div>
                
                {{-- Category Filter --}}
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">🏷️ Kategori:</label>
                    <select id="filter-category" onchange="applyFilters()" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua</option>
                        <option value="loading">Loading/Unloading</option>
                        <option value="document">Dokumen Fisik</option>
                        <option value="container">Kondisi Kontainer</option>
                        <option value="damage">Kerusakan/Damage</option>
                        <option value="handover">Serah Terima</option>
                    </select>
                </div>
                
                <button type="button" onclick="resetFilters()" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                    🔄 Reset
                </button>
            </div>
            
            {{-- Bulk Actions --}}
            <div class="flex items-center gap-2">
                <span id="selected-count" class="text-sm text-gray-500 hidden">0 dipilih</span>
                <button type="button" id="btn-download-selected" onclick="downloadSelectedPhotos()" 
                        class="hidden items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">
                    📥 Download ZIP
                </button>
                <button type="button" onclick="downloadAllPhotos()" 
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg">
                    📦 Download Semua
                </button>
            </div>
        </div>
        
        {{-- Active Filters Display --}}
        <div id="active-filters" class="hidden mt-3 pt-3 border-t border-gray-100">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-gray-500">Filter aktif:</span>
                <div id="filter-tags" class="flex gap-1 flex-wrap"></div>
            </div>
        </div>
    </div>

    {{-- Gallery --}}
    @if($photos && $photos->count() > 0)
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4" style="display:none;">
            <h2 class="text-lg font-semibold text-gray-800">Galeri Foto</h2>
            <p class="text-sm text-gray-500">Klik foto untuk melihat lebih besar</p>
        </div>
        
        <div class="photo-grid-section"><div class="photo-grid" id="photo-grid">
            @foreach($photos as $index => $photo)
            @php
                $photoUrl = asset('storage/' . $photo->file_path);
                $thumbUrl = asset('storage/' . ($photo->thumbnail_path ?: $photo->file_path));
            @endphp
            
            <div class="photo-card" data-photo-id="{{ $photo->id }}">
                @if($canDelete ?? false)
                <div class="photo-checkbox hidden">
                    <input type="checkbox" class="photo-select-checkbox w-5 h-5 rounded" 
                           value="{{ $photo->id }}" onchange="updateSelectedCount()">
                </div>
                @endif
                
                <a href="{{ $photoUrl }}" class="glightbox block w-full h-full" data-gallery="gallery1"
                   data-glightbox="title: Foto {{ $index + 1 }}; description: {{ $photo->created_at->format('d/m/Y H:i') }} - {{ $photo->user->name ?? 'Unknown' }}">
                    <img src="{{ $thumbUrl }}" alt="Photo" loading="lazy" class="w-full h-full object-cover">
                </a>
                
                <div class="photo-badge">
                    @if($photo->latitude)
                    <span class="px-1.5 py-0.5 bg-green-500 text-white text-xs rounded-full">📍</span>
                    @endif
                    @if($photo->created_at->isToday())
                    <span class="px-1.5 py-0.5 bg-blue-500 text-white text-xs rounded-full">Baru</span>
                    @endif
                </div>
                
                @if($canDelete ?? false)
                <button type="button" class="photo-delete-btn p-2 bg-red-500 hover:bg-red-600 text-white rounded-full shadow-lg"
                        onclick="event.preventDefault(); event.stopPropagation(); deletePhoto({{ $photo->id }})"
                        title="Hapus foto">
                    🗑️
                </button>
                @endif
                
                <div class="photo-overlay pointer-events-none">
                    <p class="text-white text-sm truncate">{{ $photo->original_filename ?? 'Photo' }}</p>
                    <p class="text-gray-300 text-xs">{{ $photo->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div></div>
    
    <!-- Tabel Detail Foto -->
    <div class="bg-white rounded-xl shadow-sm p-6 mt-0">
        <h3 class="text-xl font-bold text-gray-800 mb-4">📸 Galeri Foto</h3>
        <div class="overflow-x-auto">
            <table class="photo-table">
                <thead>
                    <tr data-category="{{ $photo->category ?? '' }}" data-user-id="{{ $photo->user_id }}">
                        <th style="width:40px" class="select-column hidden"><input type="checkbox" id="select-all-checkbox" onclick="toggleSelectAll()" class="w-4 h-4 rounded"></th>
                        <th style="width:40px">No</th>
                        <th style="width:80px">Preview</th>
                        <th>Tanggal/Waktu</th>
                        <th>Lokasi GPS</th>
                        <th>Diambil Oleh</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">AKSI</th></tr>
                </thead>
                <tbody>
                    @foreach($photos as $idx => $photo)
                    <tr data-category="{{ $photo->category ?? '' }}" data-user-id="{{ $photo->user_id }}">
                        <td class="select-column hidden"><input type="checkbox" class="photo-select-checkbox w-4 h-4 rounded" value="{{ $photo->id }}" onchange="updateSelectedCount()"></td>
                        <td class="text-gray-500">{{ $photos->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ asset('storage/' . $photo->file_path) }}" class="glightbox" data-gallery="table">
                                <img src="{{ asset('storage/' . ($photo->thumbnail_path ?: $photo->file_path)) }}" 
                                     class="preview-img" alt="Preview">
                            </a>
                        </td>
                        <td>
                            <div class="font-medium text-gray-900">{{ $photo->created_at->format('d M Y') }}</div>
                            <div class="text-sm text-gray-500">{{ $photo->created_at->format('H:i:s') }} WIB</div>
                            <div class="text-xs text-gray-400">{{ $photo->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            @if($photo->latitude && $photo->longitude)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">✓ GPS</span>
                            <a href="https://www.google.com/maps?q={{ $photo->latitude }},{{ $photo->longitude }}" 
                               target="_blank" class="block text-xs text-blue-600 hover:underline mt-1">📍 Buka Maps</a>
                            <div class="text-xs text-gray-400 mt-1">{{ number_format($photo->latitude, 6) }}, {{ number_format($photo->longitude, 6) }}</div>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">✗ Tidak ada GPS</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-medium text-sm">
                                    {{ strtoupper(substr($photo->user->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $photo->user->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ $photo->user->email ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $categoryLabels = [
                                    "loading" => ["label" => "📦 Loading/Unloading", "class" => "bg-blue-100 text-blue-700"],
                                    "document" => ["label" => "📋 Dokumen Fisik", "class" => "bg-yellow-100 text-yellow-700"],
                                    "container" => ["label" => "🚛 Kondisi Kontainer", "class" => "bg-purple-100 text-purple-700"],
                                    "damage" => ["label" => "⚠️ Kerusakan", "class" => "bg-red-100 text-red-700"],
                                    "handover" => ["label" => "✅ Serah Terima", "class" => "bg-green-100 text-green-700"],
                                    "other" => ["label" => "📷 Lainnya", "class" => "bg-gray-100 text-gray-700"],
                                ];
                                $cat = $categoryLabels[$photo->category] ?? null;
                            @endphp
                            @if($cat)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $cat["class"] }}">{{ $cat["label"] }}</span>
                            @else
                                <span class="text-gray-400 italic text-sm">-</span>
                            @endif
                        </td>
                        <td>
                            @if($photo->description)
                            <p class="text-gray-700">{{ $photo->description }}</p>
                            @else
                            <span class="text-gray-400 italic">-</span>
                            @endif
                            @if($photo->original_filename)
                            <div class="text-xs text-gray-400 mt-1">File: {{ Str::limit($photo->original_filename, 25) }}</div>
                            @endif
                            @if($photo->file_size)
                            <div class="text-xs text-gray-400">Size: {{ number_format($photo->file_size / 1024, 1) }} KB</div>
                            @endif
                        </td>
                    
                    <td class="px-4 py-3 text-center">
                        @if($canDelete ?? false)
                        <button type="button" 
                                onclick="event.stopPropagation(); deletePhoto({{ $photo->id }})"
                                class="btn-delete inline-flex items-center justify-center w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full shadow transition-all hover:scale-110"
                                title="Hapus foto">
                            🗑️
                        </button>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($photos->hasPages())
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Menampilkan {{ $photos->firstItem() }} - {{ $photos->lastItem() }} dari {{ $photos->total() }} foto
            </div>
            <div class="flex items-center gap-2">
                {{ $photos->links() }}
            </div>
        </div>
        @endif
        </div>
    </div>
    
    @else
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <p class="text-gray-500 mb-4">Belum ada foto untuk shipment ini</p>
        <a href="{{ route('admin.field-docs.upload', $shipment->awb_number ?: $shipment->id) }}" 
           class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg">
            ➕ Upload Foto Pertama
        </a>
    </div>
    @endif
</div>

{{-- Delete Modal --}}
<div id="delete-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">🗑️</span>
            </div>
            <h3 class="text-lg font-semibold mb-2">Hapus Foto?</h3>
            <p class="text-gray-500 mb-6" id="delete-modal-message">Foto akan dihapus permanen.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">
                    Batal
                </button>
                <button id="confirm-delete-btn" onclick="confirmDelete()" 
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js">
    function updateBulkCount() {
        const countEl = document.getElementById('bulk-delete-count') || document.querySelector('[id*="bulk"] span');
        if (countEl) {
            countEl.textContent = 'Hapus (' + selectedPhotos.length + ')';
        }
    }
</script>
<script>
// Init lightbox
document.addEventListener('DOMContentLoaded', function() {
    GLightbox({ selector: '.glightbox', loop: true });
});

let photoToDelete = null;
let isBulkDelete = false;
let isSelectMode = false;

    function deletePhoto(photoId) {
        console.log('🗑️ deletePhoto called with ID:', photoId);
        
        if (!confirm('Apakah Anda yakin ingin menghapus foto ini?')) {
            console.log('❌ User cancelled');
            return;
        }
        
        const btn = event?.target?.closest('button');
        let originalHtml = '🗑️';
        
        if (btn) {
            originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="animate-spin">⏳</span>';
            btn.disabled = true;
            console.log('✅ Button found and disabled');
        } else {
            console.log('⚠️ Button not found');
        }
        
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta?.content;
        console.log('🔑 CSRF Token:', csrfToken ? 'Found (' + csrfToken.substring(0,10) + '...)' : 'NOT FOUND!');
        
        if (!csrfToken) {
            alert('CSRF token tidak ditemukan! Refresh halaman.');
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
            return;
        }
        
        const url = '/admin/field-docs/photo/' + photoId;
        console.log('📡 Fetching:', url);
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('📥 Response status:', response.status, response.statusText);
            console.log('📥 Response headers:', [...response.headers.entries()]);
            
            return response.text().then(text => {
                console.log('📥 Response body (raw):', text.substring(0, 500));
                
                try {
                    const data = JSON.parse(text);
                    console.log('📥 Response JSON:', data);
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Server error: ' + response.status);
                    }
                    return data;
                } catch (e) {
                    console.error('❌ JSON parse error:', e);
                    console.log('Raw response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('✅ Success data:', data);
            
            if (data.success) {
                const card = btn?.closest('.photo-card') || document.querySelector('[data-photo-id="' + photoId + '"]');
                console.log('🎴 Card found:', card ? 'Yes' : 'No');
                
                if (card) {
                    card.style.transition = 'all 0.4s ease';
                    card.style.transform = 'scale(0.8)';
                    card.style.opacity = '0';
                    
                    setTimeout(() => {
                        card.remove();
                        console.log('🗑️ Card removed');
                        
                        // Update counter
                        const counter = document.querySelector('.text-2xl.font-bold.text-blue-600');
                        if (counter) {
                            const num = parseInt(counter.textContent) || 0;
                            counter.textContent = Math.max(0, num - 1);
                        }
                        
                        // Reload if no photos left
                        if (document.querySelectorAll('.photo-card').length === 0) {
                            console.log('📭 No photos left, reloading...');
                            location.reload();
                        }
                    }, 400);
                }
                
                showNotification('Foto berhasil dihapus', 'success');
            } else {
                throw new Error(data.message || 'Gagal menghapus foto');
            }
        })
        .catch(error => {
            console.error('❌ Delete error:', error);
            showNotification(error.message || 'Gagal menghapus foto', 'error');
            
            if (btn) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    }
    
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        document.querySelectorAll('.toast-notification').forEach(el => el.remove());
        
        const toast = document.createElement('div');
        toast.className = 'toast-notification fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ' +
            (type === 'success' ? 'bg-green-500 text-white' : 
             type === 'error' ? 'bg-red-500 text-white' : 'bg-blue-500 text-white');
        toast.innerHTML = (type === 'success' ? '✅ ' : type === 'error' ? '❌ ' : 'ℹ️ ') + message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
    photoToDelete = null;
    isBulkDelete = false;
    // Reset button
    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = false;
    btn.textContent = 'Ya, Hapus';
}

function confirmDelete() {
    if (isBulkDelete) {
        executeBulkDelete();
        return;
    }
    
    if (!photoToDelete) return;
    
    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = true;
    btn.textContent = 'Menghapus...';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        showToast('error', 'CSRF token tidak ditemukan');
        closeDeleteModal();
        return;
    }
    
    console.log('Deleting photo:', photoToDelete);
    
    fetch(`/admin/field-docs/photo/${photoToDelete}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json().then(data => ({status: response.status, data: data}));
    })
    .then(({status, data}) => {
        console.log('Response data:', data);
        
        if (status === 200 && data.success) {
            const card = document.querySelector(`[data-photo-id="${photoToDelete}"]`);
            if (card) card.remove();
            showToast('success', data.message || 'Foto berhasil dihapus');
            
            if (document.querySelectorAll('.photo-card').length === 0) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showToast('error', data.message || 'Gagal menghapus foto');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast('error', 'Terjadi kesalahan jaringan');
    })
    .finally(() => {
        closeDeleteModal();
    });
}

function toggleSelectMode() {
        console.log('toggleSelectMode called, current:', isSelectMode);
        isSelectMode = !isSelectMode;
        
        const btn = document.getElementById('toggle-select-btn') || document.querySelector('[onclick*="toggleSelectMode"]');
        const bulkBtn = document.getElementById('bulk-delete-btn');
        const checkboxes = document.querySelectorAll('.photo-checkbox, .row-checkbox');
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        
        console.log('Found elements:', {
            btn: !!btn,
            bulkBtn: !!bulkBtn, 
            checkboxes: checkboxes.length,
            selectAllCheckbox: !!selectAllCheckbox
        });
        
        if (isSelectMode) {
            // Aktifkan mode pilih
            if (btn) {
                const span = btn.querySelector('span') || btn;
                if (span.textContent) span.textContent = 'Batal Pilih';
                btn.classList.remove('bg-yellow-100', 'text-yellow-700');
                btn.classList.add('bg-red-100', 'text-red-700');
            }
            if (bulkBtn) {
                bulkBtn.classList.remove('hidden');
                bulkBtn.classList.add('inline-flex');
            }
            checkboxes.forEach(cb => {
                cb.classList.remove('hidden');
                cb.style.display = 'inline-block';
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.closest('th, td')?.classList.remove('hidden');
            }
            console.log('✅ Select mode ENABLED');
            // Show select columns in table
            document.querySelectorAll('.select-column').forEach(col => col.classList.remove('hidden'));
        } else {
            // Nonaktifkan mode pilih
            if (btn) {
                const span = btn.querySelector('span') || btn;
                if (span.textContent) span.textContent = 'Pilih Foto';
                btn.classList.add('bg-yellow-100', 'text-yellow-700');
                btn.classList.remove('bg-red-100', 'text-red-700');
            }
            if (bulkBtn) {
                bulkBtn.classList.add('hidden');
                bulkBtn.classList.remove('inline-flex');
            }
            checkboxes.forEach(cb => {
                cb.classList.add('hidden');
                cb.style.display = 'none';
                const input = cb.querySelector('input') || cb;
                if (input.checked !== undefined) input.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.closest('th, td')?.classList.add('hidden');
            }
            selectedPhotos = [];
            updateBulkCount();
            console.log('✅ Select mode DISABLED');
            // Hide select columns in table
            document.querySelectorAll('.select-column').forEach(col => col.classList.add('hidden'));
        }
    }


function toggleSelectAll() {
    const selectAll = document.getElementById("select-all-checkbox");
    const checkboxes = document.querySelectorAll(".photo-select-checkbox");
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll(".photo-select-checkbox:checked").length;
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDownloadBtn = document.getElementById("btn-download-selected");
    const selectedCountEl = document.getElementById("selected-count");
    const bulkCountEl = document.getElementById("bulk-delete-count");
    
    // Update count displays
    if (bulkCountEl) bulkCountEl.textContent = "Hapus (" + selected + ")";
    if (selectedCountEl) {
        selectedCountEl.textContent = selected + " dipilih";
        selectedCountEl.classList.toggle("hidden", selected === 0);
    }
    
    // Show/hide bulk action buttons
    if (selected > 0) {
        if (bulkDeleteBtn) { bulkDeleteBtn.classList.remove("hidden"); bulkDeleteBtn.classList.add("inline-flex"); }
        if (bulkDownloadBtn) { bulkDownloadBtn.classList.remove("hidden"); bulkDownloadBtn.classList.add("inline-flex"); }
    } else {
        if (bulkDeleteBtn) { bulkDeleteBtn.classList.add("hidden"); bulkDeleteBtn.classList.remove("inline-flex"); }
        if (bulkDownloadBtn) { bulkDownloadBtn.classList.add("hidden"); bulkDownloadBtn.classList.remove("inline-flex"); }
    }
    
    // Highlight selected rows in table
    document.querySelectorAll(".photo-select-checkbox").forEach(cb => {
        const row = cb.closest("tr");
        if (row) row.classList.toggle("bg-blue-50", cb.checked);
    });
}
function bulkDeletePhotos() {
    const selectedIds = Array.from(document.querySelectorAll('.photo-select-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    
    photoToDelete = selectedIds;
    isBulkDelete = true;
    document.getElementById('delete-modal-message').textContent = `Hapus ${selectedIds.length} foto? Tidak dapat dikembalikan.`;
    document.getElementById('delete-modal').classList.remove('hidden');
}

function executeBulkDelete() {
    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = true;
    btn.textContent = 'Menghapus...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch('/admin/field-docs/photos/bulk-delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ photo_ids: photoToDelete }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            photoToDelete.forEach(id => {
                const card = document.querySelector(`[data-photo-id="${id}"]`);
                if (card) card.remove();
            });
            showToast('success', data.message);
            toggleSelectMode();
            
            if (document.querySelectorAll('.photo-card').length === 0) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showToast('error', data.message || 'Gagal menghapus foto');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Terjadi kesalahan');
    })
    .finally(() => {
        closeDeleteModal();
    });
}

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'} ${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// Close modal on ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDeleteModal(); });
document.getElementById('delete-modal').addEventListener('click', e => { if (e.target.id === 'delete-modal') closeDeleteModal(); });

    function updateBulkCount() {
        const countEl = document.getElementById('bulk-delete-count') || document.querySelector('[id*="bulk"] span');
        if (countEl) {
            countEl.textContent = 'Hapus (' + selectedPhotos.length + ')';
        }
    }

// ========== FILTER FUNCTIONS ==========
function applyFilters() {
    const dateFilter = document.getElementById("filter-date")?.value || "";
    const userFilter = document.getElementById("filter-user")?.value || "";
    const gpsFilter = document.getElementById("filter-gps")?.value || "";
    const categoryFilter = document.getElementById("filter-category")?.value || "";
    
    const rows = document.querySelectorAll(".photo-table tbody tr");
    let visibleCount = 0;
    
    rows.forEach(row => {
        let show = true;
        const dateText = row.querySelector("td:nth-child(3)")?.textContent || "";
        const userId = row.dataset.userId || "";
        const hasGps = row.querySelector(".bg-green-100") !== null;
        const category = row.dataset.category || "";
        
        // Date filter
        if (dateFilter === "today" && !dateText.includes("Hari ini") && !dateText.includes("jam") && !dateText.includes("menit")) show = false;
        if (dateFilter === "week" && dateText.includes("minggu") && !dateText.includes("1 minggu")) show = false;
        if (dateFilter === "month" && dateText.includes("bulan") && !dateText.includes("1 bulan")) show = false;
        
        // User filter
        if (userFilter && userId !== userFilter) show = false;
        
        // GPS filter
        if (gpsFilter === "with" && !hasGps) show = false;
        if (gpsFilter === "without" && hasGps) show = false;
        
        // Category filter
        if (categoryFilter && category !== categoryFilter) show = false;
        
        row.style.display = show ? "" : "none";
        if (show) visibleCount++;
    });
    
    updateFilterTags();
    updateVisibleCount(visibleCount, rows.length);
}

function resetFilters() {
    document.getElementById("filter-date").value = "";
    document.getElementById("filter-user").value = "";
    document.getElementById("filter-gps").value = "";
    document.getElementById("filter-category").value = "";
    applyFilters();
}

function updateFilterTags() {
    const container = document.getElementById("filter-tags");
    const wrapper = document.getElementById("active-filters");
    if (!container || !wrapper) return;
    
    const filters = [];
    const dateVal = document.getElementById("filter-date")?.value;
    const userVal = document.getElementById("filter-user");
    const gpsVal = document.getElementById("filter-gps")?.value;
    const catVal = document.getElementById("filter-category")?.value;
    
    if (dateVal) filters.push({label: "📅 " + document.getElementById("filter-date").selectedOptions[0].text, type: "date"});
    if (userVal?.value) filters.push({label: "👤 " + userVal.selectedOptions[0].text, type: "user"});
    if (gpsVal) filters.push({label: "📍 " + document.getElementById("filter-gps").selectedOptions[0].text, type: "gps"});
    if (catVal) filters.push({label: "🏷️ " + document.getElementById("filter-category").selectedOptions[0].text, type: "category"});
    
    if (filters.length > 0) {
        wrapper.classList.remove("hidden");
        container.innerHTML = filters.map(f => 
            `<span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">${f.label}<button onclick="clearFilter(\x27${f.type}\x27)" class="hover:text-blue-900">✕</button></span>`
        ).join("");
    } else {
        wrapper.classList.add("hidden");
        container.innerHTML = "";
    }
}

function clearFilter(type) {
    const el = document.getElementById("filter-" + type);
    if (el) el.value = "";
    applyFilters();
}

function updateVisibleCount(visible, total) {
    const counter = document.querySelector(".text-2xl.font-bold.text-gray-800");
    if (counter && visible !== total) {
        counter.innerHTML = visible + `<span class="text-sm font-normal text-gray-400">/${total}</span>`;
    } else if (counter) {
        counter.textContent = total;
    }
}

// ========== DOWNLOAD FUNCTIONS ==========
function downloadAllPhotos() {
    const shipmentId = "{{ $shipment->awb_number ?: $shipment->bl_number ?: $shipment->id }}";
    showToast("success", "Menyiapkan download semua foto...");
    window.location.href = "/admin/field-docs/download-zip/" + encodeURIComponent(shipmentId);
}

function downloadSelectedPhotos() {
    const checkboxes = document.querySelectorAll(".photo-select-checkbox:checked");
    if (checkboxes.length === 0) {
        showToast("error", "Pilih minimal 1 foto untuk download");
        return;
    }
    const ids = Array.from(checkboxes).map(cb => cb.value).join(",");
    showToast("success", "Menyiapkan download " + checkboxes.length + " foto...");
    window.location.href = "/admin/field-docs/download-zip?ids=" + ids;
}

// ========== INIT DATA ATTRIBUTES ==========
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".photo-table tbody tr").forEach(row => {
        const userCell = row.querySelector("td:nth-child(5) .font-medium");
        if (userCell) {
            const userName = userCell.textContent.trim();
            const userOption = Array.from(document.getElementById("filter-user")?.options || []).find(o => o.text === userName);
            if (userOption) row.dataset.userId = userOption.value;
        }
    });
});
</script>
@endpush
