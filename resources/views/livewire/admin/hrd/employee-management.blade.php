<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-100">👥 Data Karyawan</h1>
            <p class="text-sm text-gray-400 mt-1">Kelola data karyawan perusahaan</p>
        </div>
        @if($this->canCreate())
        <button wire:click="openCreate"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            + Tambah Karyawan
        </button>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama / NIK..."
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <select wire:model.live="filterStatus"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Tidak Aktif</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
                <thead>
                    <tr class="bg-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                        <th class="px-4 py-3 text-left">No</th>
                        <th class="px-4 py-3 text-left">Foto</th>
                        <th class="px-4 py-3 text-left">NIK</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Jabatan</th>
                        <th class="px-4 py-3 text-left">Join Date</th>
                        <th class="px-4 py-3 text-left">No HP</th>
                        <th class="px-4 py-3 text-center">Jenis</th>
                        <th class="px-4 py-3 text-center">Dok.</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($employees as $index => $emp)
                    <tr class="hover:bg-gray-750">
                        <td class="px-4 py-3 text-gray-500">{{ $employees->firstItem() + $index }}</td>
                        {{-- Pas Foto --}}
                        <td class="px-4 py-3">
                            @if($emp->photo)
                                <button wire:click="openPreview('{{ $emp->photo }}', 'Pas Foto — {{ $emp->nama }}')" type="button">
                                    <img src="{{ Storage::url($emp->photo) }}" alt="foto"
                                        class="w-10 h-12 object-cover rounded border border-gray-600 hover:opacity-80 transition-opacity">
                                </button>
                            @else
                                <div class="w-10 h-12 bg-gray-700 rounded border border-gray-600 flex items-center justify-center text-gray-500 text-xs">—</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $emp->nik }}</td>
                        <td class="px-4 py-3 font-medium text-gray-200">{{ $emp->nama }}</td>
                        <td class="px-4 py-3">{{ $emp->jabatan->nama_jabatan ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $emp->join_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $emp->no_hp ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($emp->employment_type === 'permanent')
                                <span class="px-2 py-0.5 bg-blue-900 text-blue-300 text-xs rounded-full">Tetap</span>
                            @else
                                <span class="px-2 py-0.5 bg-amber-900 text-amber-300 text-xs rounded-full">Kontrak</span>
                            @endif
                        </td>
                        {{-- Dokumen indicators --}}
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-1">
                                @php
                                    $docDefs = [
                                        ['field' => 'ktp_image',      'label' => 'KTP',  'color' => 'green',  'preview' => true,  'title' => 'KTP'],
                                        ['field' => 'bpjs_image',     'label' => 'BPJS', 'color' => 'blue',   'preview' => true,  'title' => 'BPJS'],
                                        ['field' => 'surat_lamaran',  'label' => 'LAM',  'color' => 'purple', 'preview' => false, 'title' => 'Surat Lamaran'],
                                        ['field' => 'kartu_keluarga', 'label' => 'KK',   'color' => 'yellow', 'preview' => false, 'title' => 'Kartu Keluarga'],
                                        ['field' => 'ijazah',         'label' => 'IJZ',  'color' => 'orange', 'preview' => false, 'title' => 'Ijazah'],
                                        ['field' => 'npwp_image',     'label' => 'NPWP', 'color' => 'teal',   'preview' => true,  'title' => 'NPWP'],
                                    ];
                                    $colorMap = [
                                        'green'  => ['on' => 'bg-green-900 text-green-300 hover:bg-green-800',  'off' => 'bg-gray-700 text-gray-600'],
                                        'blue'   => ['on' => 'bg-blue-900 text-blue-300 hover:bg-blue-800',    'off' => 'bg-gray-700 text-gray-600'],
                                        'purple' => ['on' => 'bg-purple-900 text-purple-300 hover:bg-purple-800', 'off' => 'bg-gray-700 text-gray-600'],
                                        'yellow' => ['on' => 'bg-yellow-900 text-yellow-300 hover:bg-yellow-800', 'off' => 'bg-gray-700 text-gray-600'],
                                        'orange' => ['on' => 'bg-orange-900 text-orange-300 hover:bg-orange-800', 'off' => 'bg-gray-700 text-gray-600'],
                                        'teal'   => ['on' => 'bg-teal-900 text-teal-300 hover:bg-teal-800',    'off' => 'bg-gray-700 text-gray-600'],
                                    ];
                                @endphp
                                @foreach($docDefs as $doc)
                                    @php
                                        $value = $emp->{$doc['field']};
                                        $isPdf = $value && str_ends_with(strtolower($value), '.pdf');
                                    @endphp
                                    @if($value)
                                        @if($isPdf)
                                            <a href="{{ Storage::url($value) }}" target="_blank" title="Lihat {{ $doc['title'] }}"
                                                class="px-1.5 py-0.5 {{ $colorMap[$doc['color']]['on'] }} text-xs rounded font-mono">{{ $doc['label'] }}</a>
                                        @elseif($doc['preview'])
                                            <button wire:click="openPreview('{{ $value }}', '{{ $doc['title'] }} — {{ $emp->nama }}')" type="button"
                                                title="Lihat {{ $doc['title'] }}"
                                                class="px-1.5 py-0.5 {{ $colorMap[$doc['color']]['on'] }} text-xs rounded font-mono">{{ $doc['label'] }}</button>
                                        @else
                                            <button wire:click="openPreview('{{ $value }}', '{{ $doc['title'] }} — {{ $emp->nama }}')" type="button"
                                                title="Lihat {{ $doc['title'] }}"
                                                class="px-1.5 py-0.5 {{ $colorMap[$doc['color']]['on'] }} text-xs rounded font-mono">{{ $doc['label'] }}</button>
                                        @endif
                                    @else
                                        <span class="px-1.5 py-0.5 {{ $colorMap[$doc['color']]['off'] }} text-xs rounded font-mono">{{ $doc['label'] }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($emp->status === 'active')
                                <span class="px-2 py-0.5 bg-green-900 text-green-300 text-xs rounded-full">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-700 text-gray-400 text-xs rounded-full">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($this->canEdit())
                                <button wire:click="openEdit({{ $emp->id }})"
                                    class="px-3 py-1 bg-yellow-600 hover:bg-yellow-700 text-white text-xs rounded-lg">Edit</button>
                                @endif
                                @if($this->canDelete())
                                <button wire:click="confirmDelete({{ $emp->id }})"
                                    class="px-3 py-1 bg-red-700 hover:bg-red-800 text-white text-xs rounded-lg">Hapus</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-500">Belum ada data karyawan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $employees->links() }}
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 overflow-y-auto py-6">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 p-6 my-auto">
            <h2 class="text-lg font-bold text-gray-100 mb-4">
                {{ $isEditing ? 'Edit Karyawan' : 'Tambah Karyawan' }}
            </h2>
            <form wire:submit="save" class="space-y-4">

                {{-- Data Diri --}}
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Data Diri</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">NIK <span class="text-red-400">*</span></label>
                        <input wire:model="nik" type="text"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('nik') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Nama Lengkap <span class="text-red-400">*</span></label>
                        <input wire:model="nama" type="text"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('nama') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Jabatan <span class="text-red-400">*</span></label>
                        <select wire:model="jabatan_id"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($jabatanList as $jab)
                            <option value="{{ $jab->id }}">{{ $jab->nama_jabatan }}</option>
                            @endforeach
                        </select>
                        @error('jabatan_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Tanggal Bergabung <span class="text-red-400">*</span></label>
                        <input wire:model="join_date" type="date"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                        @error('join_date') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">No HP</label>
                        <input wire:model="no_hp" type="text"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Status</label>
                        <select wire:model="status"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Jenis Karyawan</label>
                        <select wire:model="employment_type"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                            <option value="permanent">Karyawan Tetap (PKWTT)</option>
                            <option value="contract">Karyawan Kontrak (PKWT)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Alamat</label>
                    <textarea wire:model="alamat" rows="2"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none resize-none"></textarea>
                </div>

                {{-- Dokumen & Foto --}}
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider pt-2">Dokumen & Foto <span class="text-gray-600 normal-case font-normal">(opsional, max 5MB, JPG/PNG)</span></p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    {{-- Pas Foto --}}
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <label class="block text-xs text-gray-400 mb-2 font-medium">📷 Pas Foto (ID Card)</label>
                        @if($current_photo)
                        <div class="mb-2 flex items-start gap-2">
                            <button type="button" wire:click="openPreview('{{ $current_photo }}', 'Pas Foto')">
                                <img src="{{ Storage::url($current_photo) }}" alt="foto"
                                    class="w-16 h-20 object-cover rounded border border-gray-500 hover:opacity-80">
                            </button>
                            <button type="button" wire:click="removeFile('photo')"
                                class="text-red-400 hover:text-red-300 text-xs mt-1">✕ Hapus</button>
                        </div>
                        @elseif($photo_upload)
                        <div class="mb-2">
                            <img src="{{ $photo_upload->temporaryUrl() }}" alt="preview"
                                class="w-16 h-20 object-cover rounded border border-blue-500">
                            <p class="text-xs text-blue-400 mt-1">Preview baru</p>
                        </div>
                        @endif
                        <input wire:model="photo_upload" type="file" accept="image/jpeg,image/png"
                            class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-700 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        @error('photo_upload') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-600 mt-1">Portrait 400×500px, resize otomatis</p>
                    </div>

                    {{-- KTP --}}
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <label class="block text-xs text-gray-400 mb-2 font-medium">🪪 Scan / Foto KTP</label>
                        @if($current_ktp)
                        <div class="mb-2 flex items-start gap-2">
                            <button type="button" wire:click="openPreview('{{ $current_ktp }}', 'KTP')">
                                <img src="{{ Storage::url($current_ktp) }}" alt="ktp"
                                    class="w-24 h-16 object-cover rounded border border-gray-500 hover:opacity-80">
                            </button>
                            <button type="button" wire:click="removeFile('ktp')"
                                class="text-red-400 hover:text-red-300 text-xs mt-1">✕ Hapus</button>
                        </div>
                        @elseif($ktp_upload)
                        <div class="mb-2">
                            <img src="{{ $ktp_upload->temporaryUrl() }}" alt="preview"
                                class="w-24 h-16 object-cover rounded border border-blue-500">
                            <p class="text-xs text-blue-400 mt-1">Preview baru</p>
                        </div>
                        @endif
                        <input wire:model="ktp_upload" type="file" accept="image/jpeg,image/png"
                            class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-700 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        @error('ktp_upload') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-600 mt-1">Max lebar 1200px, resize otomatis</p>
                    </div>

                    {{-- BPJS --}}
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <label class="block text-xs text-gray-400 mb-2 font-medium">🏥 Kartu BPJS</label>
                        @if($current_bpjs)
                        <div class="mb-2 flex items-start gap-2">
                            <button type="button" wire:click="openPreview('{{ $current_bpjs }}', 'Kartu BPJS')">
                                <img src="{{ Storage::url($current_bpjs) }}" alt="bpjs"
                                    class="w-24 h-16 object-cover rounded border border-gray-500 hover:opacity-80">
                            </button>
                            <button type="button" wire:click="removeFile('bpjs')"
                                class="text-red-400 hover:text-red-300 text-xs mt-1">✕ Hapus</button>
                        </div>
                        @elseif($bpjs_upload)
                        <div class="mb-2">
                            <img src="{{ $bpjs_upload->temporaryUrl() }}" alt="preview"
                                class="w-24 h-16 object-cover rounded border border-blue-500">
                            <p class="text-xs text-blue-400 mt-1">Preview baru</p>
                        </div>
                        @endif
                        <input wire:model="bpjs_upload" type="file" accept="image/jpeg,image/png"
                            class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-700 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        @error('bpjs_upload') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-600 mt-1">Max lebar 1200px, resize otomatis</p>
                    </div>

                </div>

                {{-- Dokumen Lainnya --}}
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider pt-2">Dokumen Lainnya <span class="text-gray-600 normal-case font-normal">(opsional, max 10MB, JPG/PNG/PDF)</span></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- Surat Lamaran --}}
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <label class="block text-xs text-gray-400 mb-2 font-medium">📄 Surat Lamaran Kerja</label>
                        @if($current_surat_lamaran)
                            @php $isPdf = str_ends_with(strtolower($current_surat_lamaran), '.pdf'); @endphp
                            <div class="mb-2 flex items-center gap-2">
                                @if($isPdf)
                                    <a href="{{ Storage::url($current_surat_lamaran) }}" target="_blank"
                                        class="flex items-center gap-1.5 px-2 py-1 bg-gray-600 hover:bg-gray-500 text-gray-200 text-xs rounded transition-colors">
                                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V8l-5-5H4v15zm7-14l4 4h-4V4z"/></svg>
                                        Lihat PDF
                                    </a>
                                @else
                                    <button type="button" wire:click="openPreview('{{ $current_surat_lamaran }}', 'Surat Lamaran')">
                                        <img src="{{ Storage::url($current_surat_lamaran) }}" alt="lamaran"
                                            class="w-24 h-16 object-cover rounded border border-gray-500 hover:opacity-80">
                                    </button>
                                @endif
                                <button type="button" wire:click="removeFile('surat_lamaran')"
                                    class="text-red-400 hover:text-red-300 text-xs">✕ Hapus</button>
                            </div>
                        @elseif($surat_lamaran_upload)
                            <div class="mb-2">
                                @if(str_ends_with(strtolower($surat_lamaran_upload->getClientOriginalName()), '.pdf'))
                                    <p class="text-xs text-blue-400 flex items-center gap-1">
                                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V8l-5-5H4v15zm7-14l4 4h-4V4z"/></svg>
                                        {{ $surat_lamaran_upload->getClientOriginalName() }}
                                    </p>
                                @else
                                    <img src="{{ $surat_lamaran_upload->temporaryUrl() }}" alt="preview"
                                        class="w-24 h-16 object-cover rounded border border-blue-500">
                                    <p class="text-xs text-blue-400 mt-1">Preview baru</p>
                                @endif
                            </div>
                        @endif
                        <input wire:model="surat_lamaran_upload" type="file" accept="image/jpeg,image/png,application/pdf"
                            class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-700 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        @error('surat_lamaran_upload') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-600 mt-1">JPG/PNG/PDF, max 10MB</p>
                    </div>

                    {{-- Kartu Keluarga --}}
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <label class="block text-xs text-gray-400 mb-2 font-medium">👨‍👩‍👧 Kartu Keluarga</label>
                        @if($current_kartu_keluarga)
                            @php $isPdf = str_ends_with(strtolower($current_kartu_keluarga), '.pdf'); @endphp
                            <div class="mb-2 flex items-center gap-2">
                                @if($isPdf)
                                    <a href="{{ Storage::url($current_kartu_keluarga) }}" target="_blank"
                                        class="flex items-center gap-1.5 px-2 py-1 bg-gray-600 hover:bg-gray-500 text-gray-200 text-xs rounded transition-colors">
                                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V8l-5-5H4v15zm7-14l4 4h-4V4z"/></svg>
                                        Lihat PDF
                                    </a>
                                @else
                                    <button type="button" wire:click="openPreview('{{ $current_kartu_keluarga }}', 'Kartu Keluarga')">
                                        <img src="{{ Storage::url($current_kartu_keluarga) }}" alt="kk"
                                            class="w-24 h-16 object-cover rounded border border-gray-500 hover:opacity-80">
                                    </button>
                                @endif
                                <button type="button" wire:click="removeFile('kartu_keluarga')"
                                    class="text-red-400 hover:text-red-300 text-xs">✕ Hapus</button>
                            </div>
                        @elseif($kartu_keluarga_upload)
                            <div class="mb-2">
                                @if(str_ends_with(strtolower($kartu_keluarga_upload->getClientOriginalName()), '.pdf'))
                                    <p class="text-xs text-blue-400 flex items-center gap-1">
                                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V8l-5-5H4v15zm7-14l4 4h-4V4z"/></svg>
                                        {{ $kartu_keluarga_upload->getClientOriginalName() }}
                                    </p>
                                @else
                                    <img src="{{ $kartu_keluarga_upload->temporaryUrl() }}" alt="preview"
                                        class="w-24 h-16 object-cover rounded border border-blue-500">
                                    <p class="text-xs text-blue-400 mt-1">Preview baru</p>
                                @endif
                            </div>
                        @endif
                        <input wire:model="kartu_keluarga_upload" type="file" accept="image/jpeg,image/png,application/pdf"
                            class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-700 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        @error('kartu_keluarga_upload') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-600 mt-1">JPG/PNG/PDF, max 10MB</p>
                    </div>

                    {{-- Ijazah --}}
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <label class="block text-xs text-gray-400 mb-2 font-medium">🎓 Ijazah Terakhir</label>
                        @if($current_ijazah)
                            @php $isPdf = str_ends_with(strtolower($current_ijazah), '.pdf'); @endphp
                            <div class="mb-2 flex items-center gap-2">
                                @if($isPdf)
                                    <a href="{{ Storage::url($current_ijazah) }}" target="_blank"
                                        class="flex items-center gap-1.5 px-2 py-1 bg-gray-600 hover:bg-gray-500 text-gray-200 text-xs rounded transition-colors">
                                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V8l-5-5H4v15zm7-14l4 4h-4V4z"/></svg>
                                        Lihat PDF
                                    </a>
                                @else
                                    <button type="button" wire:click="openPreview('{{ $current_ijazah }}', 'Ijazah Terakhir')">
                                        <img src="{{ Storage::url($current_ijazah) }}" alt="ijazah"
                                            class="w-24 h-16 object-cover rounded border border-gray-500 hover:opacity-80">
                                    </button>
                                @endif
                                <button type="button" wire:click="removeFile('ijazah')"
                                    class="text-red-400 hover:text-red-300 text-xs">✕ Hapus</button>
                            </div>
                        @elseif($ijazah_upload)
                            <div class="mb-2">
                                @if(str_ends_with(strtolower($ijazah_upload->getClientOriginalName()), '.pdf'))
                                    <p class="text-xs text-blue-400 flex items-center gap-1">
                                        <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V8l-5-5H4v15zm7-14l4 4h-4V4z"/></svg>
                                        {{ $ijazah_upload->getClientOriginalName() }}
                                    </p>
                                @else
                                    <img src="{{ $ijazah_upload->temporaryUrl() }}" alt="preview"
                                        class="w-24 h-16 object-cover rounded border border-blue-500">
                                    <p class="text-xs text-blue-400 mt-1">Preview baru</p>
                                @endif
                            </div>
                        @endif
                        <input wire:model="ijazah_upload" type="file" accept="image/jpeg,image/png,application/pdf"
                            class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-700 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        @error('ijazah_upload') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-600 mt-1">JPG/PNG/PDF, max 10MB</p>
                    </div>

                    {{-- NPWP --}}
                    <div class="bg-gray-700/50 rounded-lg p-3">
                        <label class="block text-xs text-gray-400 mb-2 font-medium">💳 NPWP</label>
                        @if($current_npwp)
                        <div class="mb-2 flex items-start gap-2">
                            <button type="button" wire:click="openPreview('{{ $current_npwp }}', 'NPWP')">
                                <img src="{{ Storage::url($current_npwp) }}" alt="npwp"
                                    class="w-24 h-16 object-cover rounded border border-gray-500 hover:opacity-80">
                            </button>
                            <button type="button" wire:click="removeFile('npwp')"
                                class="text-red-400 hover:text-red-300 text-xs mt-1">✕ Hapus</button>
                        </div>
                        @elseif($npwp_upload)
                        <div class="mb-2">
                            <img src="{{ $npwp_upload->temporaryUrl() }}" alt="preview"
                                class="w-24 h-16 object-cover rounded border border-blue-500">
                            <p class="text-xs text-blue-400 mt-1">Preview baru</p>
                        </div>
                        @endif
                        <input wire:model="npwp_upload" type="file" accept="image/jpeg,image/png"
                            class="w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-700 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        @error('npwp_upload') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-600 mt-1">Max lebar 1200px, resize otomatis</p>
                    </div>

                </div>

                {{-- Upload progress indicator --}}
                <div wire:loading wire:target="photo_upload,ktp_upload,bpjs_upload,surat_lamaran_upload,kartu_keluarga_upload,ijazah_upload,npwp_upload"
                    class="text-sm text-blue-400 flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Mengupload file...
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirm --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <p class="text-gray-200 mb-2 font-medium">Hapus data karyawan ini?</p>
            <p class="text-gray-400 text-sm mb-5">Semua file dokumen yang tersimpan juga akan ikut dihapus.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-sm rounded-lg">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Image Preview Lightbox --}}
    @if($previewImage)
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80"
        wire:click.self="closePreview">
        <div class="relative max-w-3xl w-full mx-4">
            <div class="flex items-center justify-between mb-2 px-1">
                <span class="text-white text-sm font-medium">{{ $previewTitle }}</span>
                <button wire:click="closePreview"
                    class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <img src="{{ Storage::url($previewImage) }}" alt="{{ $previewTitle }}"
                class="w-full max-h-[80vh] object-contain rounded-lg shadow-2xl">
        </div>
    </div>
    @endif
</div>
