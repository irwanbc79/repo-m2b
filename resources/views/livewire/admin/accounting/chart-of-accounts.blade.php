<div class="space-y-6">
    @section('header', 'Chart of Accounts (Bagan Akun)')

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            {{ session('error') }}
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
            
            @if($this->canManage())
            <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                <button wire:click="syncBalances" wire:loading.attr="disabled" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold shadow-sm transition flex items-center gap-2 border border-gray-300">
                    <svg wire:loading.remove wire:target="syncBalances" class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <svg wire:loading wire:target="syncBalances" class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span wire:loading.remove wire:target="syncBalances">Sinkronkan Saldo</span>
                    <span wire:loading wire:target="syncBalances">Menyinkronkan...</span>
                </button>

                <button wire:click="create" class="bg-blue-900 hover:bg-blue-800 text-white px-4 py-2 rounded-lg font-bold shadow-sm transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Akun
                </button>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-3 w-28">Kode</th>
                        <th class="px-6 py-3">Nama Akun</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3 text-right">Saldo Awal</th>
                        <th class="px-6 py-3 text-right">Saldo Saat Ini (GL)</th>
                        <th class="px-6 py-3 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($accounts as $acc)
                    @php
                        $isNegative = $acc->calculated_balance < 0;
                    @endphp
                    <tr class="hover:bg-blue-50/60 transition duration-150 {{ $isNegative ? 'bg-red-50/30' : '' }}">
                        <td class="px-6 py-4 font-mono font-bold">
                            <button type="button" wire:click="openLedgerModal({{ $acc->id }})" class="hover:underline flex items-center gap-1.5 text-blue-900 hover:text-blue-700 group text-left" title="Klik untuk lihat riwayat jurnal akun {{ $acc->code }}">
                                <span>{{ $acc->code }}</span>
                                <svg class="w-3.5 h-3.5 text-blue-400 group-hover:text-blue-700 opacity-60 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </button>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-800">
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="button" wire:click="openLedgerModal({{ $acc->id }})" class="hover:underline text-left font-semibold text-gray-900 hover:text-blue-700" title="Klik untuk lihat riwayat jurnal akun {{ $acc->name }}">
                                    {{ $acc->name }}
                                </button>
                                @unless($acc->is_active)
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide bg-gray-200 text-gray-600"
                                      title="Akun nonaktif: tidak muncul saat input jurnal, tapi tetap ada di laporan">
                                    nonaktif
                                </span>
                                @endunless
                                @if($isNegative)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-700 border border-red-200 shadow-2xs"
                                      title="Peringatan: Saldo berjalan akun ini minus. Klik Lihat Jurnal untuk menelusuri sumber minus.">
                                    <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    Minus
                                </span>
                                @endif
                            </div>
                        </td>
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
                        <td class="px-6 py-4 text-right text-gray-500 font-mono">{{ number_format($acc->opening_balance, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-mono font-bold {{ $isNegative ? 'text-red-600 font-black' : 'text-gray-800' }}">
                            {{ number_format($acc->calculated_balance, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center items-center gap-1.5">
                                {{-- Tombol Lihat Jurnal Akun (Bisa diakses seluruh user yang berhak melihat COA) --}}
                                <button type="button" wire:click="openLedgerModal({{ $acc->id }})"
                                        class="text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 p-1.5 rounded-lg transition shadow-2xs flex items-center gap-1"
                                        title="Lihat seluruh jurnal akun {{ $acc->code }} - {{ $acc->name }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    <span class="text-xs font-semibold hidden xl:inline">Jurnal</span>
                                </button>

                                @if($this->canManage())
                                <button wire:click="edit({{ $acc->id }})" class="text-blue-600 hover:bg-blue-100 p-1.5 rounded transition" title="Edit Akun"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                <button wire:click="toggleAktif({{ $acc->id }})"
                                        wire:confirm="{{ $acc->is_active ? 'Nonaktifkan akun ini? Akun akan hilang dari pilihan saat input jurnal, tapi riwayat dan laporannya tetap utuh.' : 'Aktifkan kembali akun ini?' }}"
                                        class="{{ $acc->is_active ? 'text-gray-500 hover:bg-gray-100' : 'text-green-600 hover:bg-green-100' }} p-1.5 rounded transition"
                                        title="{{ $acc->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}">
                                    @if($acc->is_active)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                                <button wire:click="delete({{ $acc->id }})" wire:confirm="Hapus Akun ini?" class="text-red-500 hover:bg-red-100 p-1.5 rounded transition" title="Hapus Akun"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
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
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto" style="position: fixed; z-index: 50;">
        <div class="bg-white w-full max-w-lg rounded-lg shadow-xl transform transition-all" style="position: relative; z-index: 10;">
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
                    <label class="block text-sm font-bold text-gray-700 mb-1">Saldo Awal (Opening Balance Master)</label>
                    <p class="text-xs text-gray-500 mb-1.5">Saldo awal per tanggal cut-off/migrasi. Saldo berjalan saat ini akan otomatis dihitung dari saldo awal ditambah mutasi buku besar.</p>
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

    {{-- MODAL LIHAT JURNAL AKUN (LEDGER INSPECTION MODAL) --}}
    @if($isLedgerModalOpen && $ledgerData)
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 md:p-6 overflow-y-auto"
         style="position: fixed; z-index: 50;"
         wire:keydown.escape="closeLedgerModal">
        <div class="bg-white w-full max-w-6xl rounded-2xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden border border-gray-200">
            {{-- Header Modal --}}
            <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 text-white flex justify-between items-start gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 bg-blue-500/20 text-blue-200 border border-blue-400/30 rounded-md font-mono text-sm font-bold">
                            {{ $ledgerData['account']->code }}
                        </span>
                        <h3 class="font-bold text-xl text-white tracking-tight">
                            {{ $ledgerData['account']->name }}
                        </h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-slate-200">
                            {{ $accountTypes[$ledgerData['account']->type] ?? $ledgerData['account']->type }}
                            ({{ $ledgerData['isDebitNormal'] ? 'Debit Normal' : 'Kredit Normal' }})
                        </span>
                        @if($ledgerData['hasNegativeBalance'])
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold bg-red-500/30 text-red-200 border border-red-400/50">
                            ⚠️ Terdeteksi Saldo Minus
                        </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-300">
                        Rincian mutasi seluruh jurnal yang menggunakan akun ini untuk verifikasi transaksi dan penelusuran penyebab saldo minus.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('accounting.ledger', ['account_id' => $ledgerData['account']->id, 'start_date' => $ledgerStartDate, 'end_date' => $ledgerEndDate]) }}"
                       target="_blank"
                       class="text-xs bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg font-medium transition flex items-center gap-1 border border-white/20"
                       title="Buka laporan Buku Besar lengkap di tab baru">
                        <span>Buku Besar Penuh</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <button wire:click="closeLedgerModal" class="text-slate-400 hover:text-white text-2xl leading-none p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
                </div>
            </div>

            {{-- Summary Cards Bar --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 bg-slate-50 border-b border-gray-200">
                <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs">
                    <p class="text-xs text-gray-500 font-medium">Saldo Awal</p>
                    <p class="text-base font-bold text-gray-800 font-mono">Rp {{ number_format($ledgerData['openingBalance'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs">
                    <p class="text-xs text-gray-500 font-medium">Total Mutasi Debit</p>
                    <p class="text-base font-bold text-emerald-600 font-mono">Rp {{ number_format($ledgerData['totalDebit'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs">
                    <p class="text-xs text-gray-500 font-medium">Total Mutasi Kredit</p>
                    <p class="text-base font-bold text-amber-600 font-mono">Rp {{ number_format($ledgerData['totalCredit'], 0, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-xl border shadow-2xs {{ $ledgerData['closingBalance'] < 0 ? 'bg-red-50 border-red-300 text-red-900' : 'bg-blue-50 border-blue-200 text-blue-900' }}">
                    <p class="text-xs font-semibold {{ $ledgerData['closingBalance'] < 0 ? 'text-red-700' : 'text-blue-700' }}">
                        Saldo Berjalan Akhir {{ $ledgerData['closingBalance'] < 0 ? '(MINUS)' : '' }}
                    </p>
                    <p class="text-base font-black font-mono {{ $ledgerData['closingBalance'] < 0 ? 'text-red-700' : 'text-blue-900' }}">
                        Rp {{ number_format($ledgerData['closingBalance'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- Filter & Search Bar --}}
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 text-sm">
                {{-- Preset Periode --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-semibold text-gray-500 mr-1">Periode:</span>
                    <button type="button" wire:click="setLedgerPreset('all')"
                            class="px-2.5 py-1 text-xs font-medium rounded-lg transition {{ $ledgerPeriodPreset === 'all' ? 'bg-blue-900 text-white font-bold' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                        Semua Waktu
                    </button>
                    <button type="button" wire:click="setLedgerPreset('this_year')"
                            class="px-2.5 py-1 text-xs font-medium rounded-lg transition {{ $ledgerPeriodPreset === 'this_year' ? 'bg-blue-900 text-white font-bold' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                        Tahun Ini
                    </button>
                    <button type="button" wire:click="setLedgerPreset('this_month')"
                            class="px-2.5 py-1 text-xs font-medium rounded-lg transition {{ $ledgerPeriodPreset === 'this_month' ? 'bg-blue-900 text-white font-bold' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100' }}">
                        Bulan Ini
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-wrap ml-auto">
                    {{-- Date inputs --}}
                    <div class="flex items-center gap-1.5 text-xs">
                        <input type="date" wire:model.live="ledgerStartDate" class="px-2.5 py-1 border border-gray-300 rounded-lg text-xs bg-white focus:ring-1 focus:ring-blue-500" title="Dari Tanggal">
                        <span class="text-gray-400">s/d</span>
                        <input type="date" wire:model.live="ledgerEndDate" class="px-2.5 py-1 border border-gray-300 rounded-lg text-xs bg-white focus:ring-1 focus:ring-blue-500" title="Sampai Tanggal">
                    </div>

                    {{-- Search Ref / Memo --}}
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="ledgerSearch" placeholder="Cari ref, no jurnal, memo..."
                               class="pl-8 pr-3 py-1 border border-gray-300 rounded-lg text-xs w-48 sm:w-56 bg-white focus:ring-1 focus:ring-blue-500">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
            </div>

            {{-- Table Body (Scrollable) --}}
            <div class="overflow-x-auto overflow-y-auto flex-1 p-0">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-100 text-gray-600 font-bold uppercase sticky top-0 z-10 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2.5 w-24">Tanggal</th>
                            <th class="px-4 py-2.5 w-36">No. Jurnal / Ref</th>
                            <th class="px-4 py-2.5">Keterangan / Memo</th>
                            <th class="px-4 py-2.5 text-right w-28">Debit (Rp)</th>
                            <th class="px-4 py-2.5 text-right w-28">Kredit (Rp)</th>
                            <th class="px-4 py-2.5 text-right w-36">Saldo Berjalan (Rp)</th>
                            <th class="px-3 py-2.5 text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Row Saldo Awal --}}
                        <tr class="bg-slate-50/80 font-semibold text-slate-700">
                            <td class="px-4 py-2.5 text-gray-400 font-mono">{{ $ledgerStartDate ?: '-' }}</td>
                            <td class="px-4 py-2.5 text-gray-500 italic">SALDO-AWAL</td>
                            <td class="px-4 py-2.5 text-gray-600 italic">Saldo awal sebelum periode pencatatan terpilih</td>
                            <td class="px-4 py-2.5 text-right text-gray-400">-</td>
                            <td class="px-4 py-2.5 text-right text-gray-400">-</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold {{ $ledgerData['openingBalance'] < 0 ? 'text-red-600 bg-red-100/60' : 'text-slate-800' }}">
                                {{ number_format($ledgerData['openingBalance'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2.5 text-center text-gray-300">-</td>
                        </tr>

                        {{-- Rows Mutasi Jurnal --}}
                        @forelse($ledgerData['rows'] as $row)
                        <tr class="transition duration-100 hover:bg-blue-50/50 {{ $row['is_negative'] ? 'bg-red-50/80' : '' }}">
                            <td class="px-4 py-2.5 font-mono text-gray-700 whitespace-nowrap">{{ $row['date'] }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <div class="font-mono font-semibold text-blue-900">{{ $row['journal_number'] }}</div>
                                @if(!empty($row['reference_no']) && $row['reference_no'] !== '-')
                                <div class="text-[10px] text-gray-500 font-mono">Ref: {{ $row['reference_no'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="text-gray-900 font-medium">{{ $row['description'] }}</div>
                                @if(!empty($row['note']))
                                <div class="text-[11px] text-gray-500 mt-0.5">{{ $row['note'] }}</div>
                                @endif
                                <div class="text-[10px] text-slate-400 mt-0.5">Oleh: {{ $row['creator_name'] }}</div>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-emerald-700">
                                {{ $row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-amber-700">
                                {{ $row['credit'] > 0 ? number_format($row['credit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold whitespace-nowrap {{ $row['is_negative'] ? 'text-red-700 bg-red-100 font-black' : 'text-gray-900' }}">
                                @if($row['is_negative'])
                                <span class="inline-flex items-center gap-1 text-red-600 mr-1" title="Transaksi ini menyebabkan atau berada pada saldo minus">
                                    <svg class="w-3.5 h-3.5 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                </span>
                                @endif
                                {{ number_format($row['running_balance'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                @if($this->canManage())
                                <button type="button" wire:click="openEditJournalModal({{ $row['journal_id'] }})"
                                        class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded text-[11px] font-semibold transition inline-flex items-center gap-1 shadow-2xs"
                                        title="Edit Jurnal {{ $row['journal_number'] }} Langsung di Sini">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Edit</span>
                                </button>
                                @else
                                <span class="text-[10px] text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada mutasi jurnal untuk akun ini pada periode yang dipilih.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold border-t-2 border-gray-300 sticky bottom-0">
                        <tr>
                            <td colspan="3" class="px-4 py-2.5 text-right uppercase text-gray-600">Total Periode:</td>
                            <td class="px-4 py-2.5 text-right font-mono text-emerald-700">Rp {{ number_format($ledgerData['totalDebit'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-amber-700">Rp {{ number_format($ledgerData['totalCredit'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-black {{ $ledgerData['closingBalance'] < 0 ? 'text-red-700 bg-red-200' : 'text-blue-900' }}">
                                Rp {{ number_format($ledgerData['closingBalance'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2.5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Footer Modal --}}
            <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                <span class="text-xs text-gray-500">
                    Menampilkan {{ $ledgerData['count'] }} baris transaksi jurnal.
                </span>
                <button wire:click="closeLedgerModal"
                        class="px-5 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg text-sm font-semibold transition shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- SUB-MODAL EDIT JURNAL LANGSUNG (DIRECT IN-PLACE JOURNAL EDIT) --}}
    @if($isJournalEditModalOpen)
    <div class="fixed inset-0 bg-black/70 z-[60] flex items-center justify-center p-3 md:p-6 overflow-y-auto"
         style="position: fixed; z-index: 60;"
         wire:keydown.escape="closeEditJournalModal">
        <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl flex flex-col max-h-[94vh] overflow-hidden border border-gray-300">
            {{-- Header Edit Jurnal --}}
            <div class="p-5 border-b border-gray-200 bg-slate-900 text-white flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="p-2 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-400/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-bold text-lg text-white">Edit Jurnal Transaksi</h3>
                        <p class="text-xs text-slate-300 font-mono">No. Jurnal: <span class="text-amber-300 font-semibold">{{ $editJournalNumber }}</span></p>
                    </div>
                </div>
                <button wire:click="closeEditJournalModal" class="text-slate-400 hover:text-white text-2xl leading-none p-1 rounded-lg hover:bg-white/10 transition">&times;</button>
            </div>

            {{-- Form Fields --}}
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                @if($errors->has('editBalance'))
                <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded text-red-700 text-xs flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>{{ $errors->first('editBalance') }}</span>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Transaksi <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="editTransactionDate" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('editTransactionDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">No. Referensi (Invoice/Kuitansi)</label>
                        <input type="text" wire:model="editReferenceNo" placeholder="Contoh: INV-2026/09/001" class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-1 focus:ring-blue-500">
                        @error('editReferenceNo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Deskripsi Jurnal <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="editDescription" placeholder="Uraian transaksi..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500">
                        @error('editDescription') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Dynamic Debit & Credit Items --}}
                <div class="space-y-2 pt-2">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-600">Rincian Pos Debit & Kredit</label>
                        <button type="button" wire:click="addEditJournalItem" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold px-2.5 py-1 rounded-lg border border-blue-200 transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Tambah Baris</span>
                        </button>
                    </div>

                    <div class="border rounded-xl overflow-hidden bg-slate-50/50">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-100 text-gray-600 font-bold uppercase border-b">
                                <tr>
                                    <th class="px-3 py-2 text-left">Pilih Akun (COA)</th>
                                    <th class="px-3 py-2 text-left w-48">Catatan / Note</th>
                                    <th class="px-3 py-2 text-right w-36">Debit (Rp)</th>
                                    <th class="px-3 py-2 text-right w-36">Kredit (Rp)</th>
                                    <th class="px-2 py-2 text-center w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($editItems as $idx => $item)
                                <tr>
                                    <td class="p-2">
                                        <select wire:model="editItems.{{ $idx }}.account_id" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-1 focus:ring-blue-500">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach($allActiveAccounts as $accOpt)
                                                <option value="{{ $accOpt->id }}">{{ $accOpt->code }} - {{ $accOpt->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('editItems.'.$idx.'.account_id') <span class="text-red-500 text-[10px] block mt-0.5">{{ $message }}</span> @enderror
                                    </td>
                                    <td class="p-2">
                                        <input type="text" wire:model="editItems.{{ $idx }}.note" placeholder="Memo baris..." class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                                    </td>
                                    <td class="p-2 text-right">
                                        <input type="number" step="any" wire:model.live.debounce.300ms="editItems.{{ $idx }}.debit" wire:change="calculateEditJournalTotal"
                                               class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs text-right font-mono text-emerald-700 font-semibold focus:ring-1 focus:ring-blue-500">
                                    </td>
                                    <td class="p-2 text-right">
                                        <input type="number" step="any" wire:model.live.debounce.300ms="editItems.{{ $idx }}.credit" wire:change="calculateEditJournalTotal"
                                               class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs text-right font-mono text-amber-700 font-semibold focus:ring-1 focus:ring-blue-500">
                                    </td>
                                    <td class="p-2 text-center">
                                        @if(count($editItems) > 2)
                                        <button type="button" wire:click="removeEditJournalItem({{ $idx }})" class="text-gray-400 hover:text-red-600 p-1 rounded transition" title="Hapus baris ini">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 font-bold border-t">
                                <tr>
                                    <td colspan="2" class="px-3 py-2 text-right uppercase text-gray-600">Total Input:</td>
                                    <td class="px-3 py-2 text-right font-mono text-emerald-700">Rp {{ number_format($editTotalDebit, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-mono text-amber-700">Rp {{ number_format($editTotalCredit, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Balance Status Bar --}}
                @php
                    $isBalanced = abs($editTotalDebit - $editTotalCredit) <= 1 && $editTotalDebit > 0;
                @endphp
                <div class="p-3 rounded-xl border flex items-center justify-between text-xs {{ $isBalanced ? 'bg-green-50 border-green-300 text-green-800' : 'bg-amber-50 border-amber-300 text-amber-800' }}">
                    <div class="flex items-center gap-2">
                        @if($isBalanced)
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-bold">STATUS BALANCE:</span>
                            <span>Debit & Kredit sudah seimbang (Rp {{ number_format($editTotalDebit, 0, ',', '.') }}).</span>
                        @else
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span class="font-bold">BELUM BALANCE:</span>
                            <span>Selisih Rp {{ number_format(abs($editTotalDebit - $editTotalCredit), 0, ',', '.') }}. Total debit harus sama dengan total kredit.</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Footer Modal Edit --}}
            <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-end gap-2">
                <button type="button" wire:click="closeEditJournalModal" class="px-4 py-2 border rounded-lg bg-white text-gray-700 hover:bg-gray-100 text-sm font-semibold transition">
                    Batal
                </button>
                <button type="button" wire:click="saveEditedJournal" wire:loading.attr="disabled"
                        class="px-6 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-bold shadow-md transition flex items-center gap-1.5">
                    <svg wire:loading.remove wire:target="saveEditedJournal" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg wire:loading wire:target="saveEditedJournal" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span wire:loading.remove wire:target="saveEditedJournal">Simpan Perubahan Jurnal</span>
                    <span wire:loading wire:target="saveEditedJournal">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>