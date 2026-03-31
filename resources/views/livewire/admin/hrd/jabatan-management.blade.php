<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-800 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-100">🏷️ Master Jabatan</h1>
            <p class="text-sm text-gray-400 mt-1">Kelola daftar jabatan dan komponen gaji default</p>
        </div>
        @if($this->canCreate())
        <button wire:click="openCreate"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            + Tambah Jabatan
        </button>
        @endif
    </div>

    {{-- Search --}}
    <div class="bg-gray-800 rounded-xl p-4 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama jabatan..."
            class="w-full md:w-72 px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    {{-- Table --}}
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm text-gray-300">
            <thead>
                <tr class="bg-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Nama Jabatan</th>
                    <th class="px-4 py-3 text-right">Gaji Pokok Default</th>
                    <th class="px-4 py-3 text-right">Tunjangan Jabatan Default</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($jabatanList as $index => $jabatan)
                <tr class="hover:bg-gray-750">
                    <td class="px-4 py-3 text-gray-500">{{ $jabatanList->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium text-gray-200">{{ $jabatan->nama_jabatan }}</td>
                    <td class="px-4 py-3 text-right">Rp {{ number_format($jabatan->gaji_pokok_default, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">Rp {{ number_format($jabatan->tunjangan_jabatan_default, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @if($this->canEdit())
                            <button wire:click="openEdit({{ $jabatan->id }})"
                                class="px-3 py-1 bg-yellow-600 hover:bg-yellow-700 text-white text-xs rounded-lg transition-colors">
                                Edit
                            </button>
                            @endif
                            @if($this->canDelete())
                            <button wire:click="confirmDelete({{ $jabatan->id }})"
                                class="px-3 py-1 bg-red-700 hover:bg-red-800 text-white text-xs rounded-lg transition-colors">
                                Hapus
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        Belum ada data jabatan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $jabatanList->links() }}
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" x-data x-on:keydown.escape.window="$wire.closeModal()">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 p-6">
            <h2 class="text-lg font-bold text-gray-100 mb-4">
                {{ $isEditing ? 'Edit Jabatan' : 'Tambah Jabatan' }}
            </h2>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nama Jabatan <span class="text-red-400">*</span></label>
                    <input wire:model="nama_jabatan" type="text"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('nama_jabatan') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Gaji Pokok Default (Rp)</label>
                    <input wire:model="gaji_pokok_default" type="number" min="0" step="1000"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('gaji_pokok_default') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Tunjangan Jabatan Default (Rp)</label>
                    <input wire:model="tunjangan_jabatan_default" type="number" min="0" step="1000"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('tunjangan_jabatan_default') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirm Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <p class="text-gray-200 mb-5">Yakin ingin menghapus jabatan ini?</p>
            <div class="flex justify-center gap-3">
                <button wire:click="closeModal"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                <button wire:click="delete"
                    class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-sm rounded-lg">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif
</div>
