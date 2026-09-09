<div class="space-y-6">
    @section('header', 'Internal User Management')

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm mb-4">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div class="relative w-64">
                <input wire:model.live="search" type="text" placeholder="Cari Staf..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-m2b-primary">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button wire:click="create" class="bg-m2b-primary hover:bg-blue-900 text-white px-4 py-2 rounded-lg font-bold shadow-sm transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add User
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 font-bold text-gray-600 uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nama Lengkap</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Jabatan / Role</th>
                        <th class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $u)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-mono font-bold text-m2b-primary">M2B-{{ str_pad($u->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">{{ $u->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $u->email }}</td>
                        <td class="px-6 py-4">
                            @foreach($u->roles as $role)
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border mr-1 mb-1 inline-block bg-blue-50 text-blue-700 border-blue-100">
                                    {{ str_replace('_', ' ', $role) }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="edit({{ $u->id }})" class="text-blue-600 border border-blue-200 px-3 py-1 rounded hover:bg-blue-50 text-xs font-bold mr-2">Edit</button>
                            @if($u->id != auth()->id())
                            <button wire:click="delete({{ $u->id }})" wire:confirm="Hapus user ini?" class="text-red-500 border border-red-200 px-3 py-1 rounded hover:bg-red-50 text-xs font-bold">Del</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="p-6 text-center text-gray-500">Belum ada staf.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $users->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="erp-modal-backdrop">
        <div class="erp-modal-panel max-w-xl">
            <div class="erp-modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base font-semibold border border-blue-100 shadow-sm">
                        {{ $isEditing ? '✏️' : '👤' }}
                    </div>
                    <div>
                        <h3 class="erp-modal-title">{{ $isEditing ? 'Edit Staf' : 'Tambah Staf Baru' }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola akun staf dan hak akses peran (multi-role)</p>
                    </div>
                </div>
                <button wire:click="closeModal" class="erp-modal-close" aria-label="Tutup">&times;</button>
            </div>
            <div class="erp-modal-body space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                    <input type="text" wire:model="name" class="erp-input-modern" placeholder="Masukkan nama staf...">
                    @error('name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" wire:model="email" class="erp-input-modern" placeholder="nama@m2b.co.id">
                    @error('email') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Password {{ $isEditing ? '(Opsional)' : '' }}</label>
                    <input type="password" wire:model="password" class="erp-input-modern" placeholder="••••••••">
                    @if($isEditing)
                        <p class="text-[11px] text-slate-400 mt-1">Biarkan kosong jika tidak ingin mengubah password.</p>
                    @endif
                </div>
                <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/80">
                    <label class="block text-xs font-semibold text-slate-800 uppercase tracking-wider mb-3 flex items-center justify-between">
                        <span>Pilih Jabatan (Multi-Role)</span>
                        <span class="text-[11px] font-normal text-slate-500 lowercase">bisa lebih dari satu</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach($rolesList as $key => $label)
                            <label class="inline-flex items-center cursor-pointer p-2.5 bg-white border border-slate-200 rounded-lg hover:border-blue-400 hover:bg-blue-50/30 transition-all text-xs">
                                <input type="checkbox" wire:model="selectedRoles" value="{{ $key }}" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-slate-700 font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedRoles') <span class="text-red-500 text-xs font-medium mt-2 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="erp-modal-footer">
                <button wire:click="closeModal" class="erp-btn-secondary">Batal</button>
                <button wire:click="save" class="erp-btn-primary">
                    <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
            </div>
        </div>
    </div>
    @endif
</div>