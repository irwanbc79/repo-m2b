<div class="space-y-6">
    @section('header', 'Chart of Accounts (Bagan Akun)')

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- STATS CARDS - Ringkasan Saldo --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-black text-gray-800">{{ $stats["total_accounts"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Total Akun</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 shadow-sm border border-green-100 text-center cursor-pointer hover:shadow-md" wire:click="$set('type_filter', 'kas_bank')">
            <p class="text-lg font-black text-green-600">{{ number_format(($stats["kas_bank"] ?? 0) / 1000000, 1) }} Jt</p>
            <p class="text-xs text-gray-500">Kas & Bank</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 shadow-sm border border-blue-100 text-center cursor-pointer hover:shadow-md" wire:click="$set('type_filter', 'piutang')">
            <p class="text-lg font-black text-blue-600">{{ number_format(($stats["piutang"] ?? 0) / 1000000, 1) }} Jt</p>
            <p class="text-xs text-gray-500">Piutang</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 shadow-sm border border-red-100 text-center cursor-pointer hover:shadow-md" wire:click="$set('type_filter', 'hutang_lancar')">
            <p class="text-lg font-black text-red-600">{{ number_format(($stats["hutang"] ?? 0) / 1000000, 1) }} Jt</p>
            <p class="text-xs text-gray-500">Hutang</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 shadow-sm border border-purple-100 text-center cursor-pointer hover:shadow-md" wire:click="$set('type_filter', 'pendapatan')">
            <p class="text-lg font-black text-purple-600">{{ number_format(($stats["pendapatan"] ?? 0) / 1000000, 1) }} Jt</p>
            <p class="text-xs text-gray-500">Pendapatan</p>
        </div>
        <div class="bg-orange-50 rounded-xl p-4 shadow-sm border border-orange-100 text-center cursor-pointer hover:shadow-md" wire:click="$set('type_filter', 'beban_operasional')">
            <p class="text-lg font-black text-orange-600">{{ number_format(($stats["beban"] ?? 0) / 1000000, 1) }} Jt</p>
            <p class="text-xs text-gray-500">Beban</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4 shadow-sm border border-indigo-100 text-center cursor-pointer hover:shadow-md" wire:click="$set('type_filter', 'modal')">
            <p class="text-lg font-black text-indigo-600">{{ number_format(($stats["modal"] ?? 0) / 1000000, 1) }} Jt</p>
            <p class="text-xs text-gray-500">Modal</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex gap-2 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <input wire:model.live="search" type="text" placeholder="Cari Nama / Kode Akun..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <select wire:model.live="type_filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500">
                    <option value="">Semua Tipe</option>
                    @foreach($accountTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <button wire:click="create" class="bg-blue-900 hover:bg-blue-800 text-white px-4 py-2 rounded-lg font-bold shadow-sm transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Akun
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-3 w-24">Kode</th>
                        <th class="px-6 py-3">Nama Akun</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3 text-right">Saldo Awal</th>
                        <th class="px-6 py-3 text-right">Saldo Saat Ini</th>
                        <th class="px-6 py-3 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($accounts as $acc)
                    <tr class="hover:bg-blue-50 transition duration-150">
                        <td class="px-6 py-4 font-mono font-bold text-blue-900">{{ $acc->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $acc->name }}</td>
                        <td class="px-6 py-4">
                            @php
                                $colors = [
                                    'kas_bank' => 'bg-green-100 text-green-800',
                                    'piutang' => 'bg-blue-100 text-blue-800',
                                    'hutang_lancar' => 'bg-red-100 text-red-800',
                                    'pendapatan' => 'bg-purple-100 text-purple-800',
                                    'beban_operasional' => 'bg-yellow-100 text-yellow-800',
                                ];
                                $colorClass = $colors[$acc->type] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $colorClass }}">
                                {{ $accountTypes[$acc->type] ?? $acc->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-500">{{ number_format($acc->opening_balance, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-800">{{ number_format($acc->current_balance, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center flex justify-center gap-2">
                            <button wire:click="edit({{ $acc->id }})" class="text-blue-600 hover:bg-blue-100 p-1.5 rounded transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                            <button wire:click="delete({{ $acc->id }})" wire:confirm="Hapus Akun ini?" class="text-red-500 hover:bg-red-100 p-1.5 rounded transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">Data tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $accounts->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-lg rounded-lg shadow-xl transform transition-all">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center rounded-t-lg">
                <h3 class="font-bold text-lg text-gray-800">{{ $isEditing ? 'Edit Akun' : 'Tambah Akun Baru' }}</h3>
                <button wire:click="closeModal" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Kode Akun</label>
                        <input type="text" wire:model="code" class="w-full border rounded-lg px-3 py-2 text-sm font-mono" placeholder="1101">
                        @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Akun</label>
                        <input type="text" wire:model="name" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Kas Besar">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipe Akun</label>
                    <select wire:model="type" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">-- Pilih Tipe --</option>
                        @foreach($accountTypes as $key => $label)
                            <option value="{{ $key }}">{{ $key }} - {{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Saldo Awal (Opening Balance)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500 text-sm">Rp</span>
                        <input type="number" wire:model="opening_balance" class="w-full border rounded-lg pl-10 pr-3 py-2 text-sm text-right font-mono">
                    </div>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 rounded-b-lg">
                <button wire:click="closeModal" class="px-4 py-2 border rounded-lg bg-white text-gray-700 hover:bg-gray-100 transition">Batal</button>
                <button wire:click="save" class="px-6 py-2 bg-blue-900 text-white rounded-lg font-bold hover:bg-blue-800 transition shadow-md">Simpan</button>
            </div>
        </div>
    </div>
    @endif
</div>