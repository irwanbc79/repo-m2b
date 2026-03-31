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
                        <th class="px-4 py-3 text-left">NIK</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Jabatan</th>
                        <th class="px-4 py-3 text-left">Join Date</th>
                        <th class="px-4 py-3 text-left">No HP</th>
                        <th class="px-4 py-3 text-center">Jenis</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($employees as $index => $emp)
                    <tr class="hover:bg-gray-750">
                        <td class="px-4 py-3 text-gray-500">{{ $employees->firstItem() + $index }}</td>
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
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada data karyawan.</td>
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 overflow-y-auto py-6">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6">
            <h2 class="text-lg font-bold text-gray-100 mb-4">
                {{ $isEditing ? 'Edit Karyawan' : 'Tambah Karyawan' }}
            </h2>
            <form wire:submit="save" class="space-y-4">
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
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirm --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <p class="text-gray-200 mb-5">Yakin ingin menghapus data karyawan ini?</p>
            <div class="flex justify-center gap-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-sm rounded-lg">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif
</div>
