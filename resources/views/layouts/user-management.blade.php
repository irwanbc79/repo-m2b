<div class="space-y-6">
    @section('header', 'Internal User Management (Multi-Role)')

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
                        <th class="px-6 py-3">Jabatan / Role (Multi)</th>
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
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border mr-1 mb-1 inline-block
                                    {{ $role == 'admin' ? 'bg-purple-100 text-purple-800 border-purple-200' : 
                                      ($role == 'manager' ? 'bg-indigo-100 text-indigo-800 border-indigo-200' : 
                                      ($role == 'staf_accounting' ? 'bg-orange-100 text-orange-800 border-orange-200' : 'bg-blue-50 text-blue-700 border-blue-100')) }}">
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
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-xl rounded-lg shadow-2xl flex flex-col max-h-[90vh]">
            <div class="p-5 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
                <h3 class="font-bold text-lg text-gray-800">{{ $isEditing ? 'Edit Staf & Roles' : 'Tambah Staf Baru' }}</h3>
                <button wire:click="closeModal" class="text-2xl text-gray-400 hover:text-red-500">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" wire:model="name" class="w-full border rounded p-2">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                    <input type="email" wire:model="email" class="w-full border rounded p-2">
                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Password {{ $isEditing ? '(Opsional)' : '' }}</label>
                    <input type="password" wire:model="password" class="w-full border rounded p-2" placeholder="******">
                </div>

                <div class="bg-blue-50 p-4 rounded border border-blue-200">
                    <label class="block text-sm font-bold text-blue-900 mb-3">Pilih Jabatan / Akses (Bisa Lebih dari 1)</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($rolesList as $key => $label)
                            <label class="inline-flex items-center cursor-pointer p-2 bg-white border rounded hover:bg-gray-50">
                                <input type="checkbox" wire:model="selectedRoles" value="{{ $key }}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedRoles') <span class="text-red-500 text-xs font-bold mt-2 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="p-5 border-t bg-gray-50 flex justify-end gap-2 rounded-b-lg">
                <button wire:click="closeModal" class="px-4 py-2 border rounded bg-white">Batal</button>
                <button wire:click="save" class="px-6 py-2 bg-m2b-primary text-white rounded font-bold">Simpan Data</button>
            </div>
        </div>
    </div>
    @endif
</div>