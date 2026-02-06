<div class="p-6">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">💰 Kas Kecil</h1>
            <p class="text-sm text-gray-500">Kelola pengeluaran operasional harian</p>
        </div>
        @if($fund)
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('showModal', true)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Input Pengeluaran
            </button>
            <button wire:click="$set('showTopupModal', true)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Request Top Up
            </button>
            @if($canSetting)<button wire:click="$set('showSettingModal', true)" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Pengaturan
            </button>
        </div>
        @endif
    </div>

    @if(!$fund)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-yellow-400 mr-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>
                    <p class="font-bold text-yellow-800">Kas Kecil Belum Disetup</p>
                    <p class="text-sm text-yellow-700">Hubungi administrator untuk inisialisasi dana kas kecil.</p>
                </div>
            </div>
        </div>
    @else
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Saldo --}}
            <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 {{ $fund->needsTopup() ? 'border-red-500' : 'border-green-500' }}">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Saldo Saat Ini</p>
                        <p class="text-2xl font-bold {{ $fund->needsTopup() ? 'text-red-600' : 'text-green-600' }}">
                            Rp {{ number_format($fund->current_balance, 0, ',', '.') }}
                        </p>
                    </div>
                    @if($fund->needsTopup())
                        <span class="px-2 py-1 bg-red-100 text-red-600 text-xs font-semibold rounded-full animate-pulse">PERLU TOP UP</span>
                    @endif
                </div>
                <div class="mt-3">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $fund->usage_percentage > 70 ? 'bg-red-500' : ($fund->usage_percentage > 40 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ 100 - $fund->usage_percentage }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ number_format(100 - $fund->usage_percentage, 0) }}% tersisa dari plafon</p>
                </div>
            </div>

            {{-- Plafon --}}
            <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
                <p class="text-sm font-medium text-gray-500">Plafon</p>
                <p class="text-2xl font-bold text-blue-600">Rp {{ number_format($fund->plafon, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-2">Max/transaksi: Rp {{ number_format($fund->max_transaction, 0, ',', '.') }}</p>
            </div>

            {{-- Pengeluaran Bulan Ini --}}
            <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
                <p class="text-sm font-medium text-gray-500">Pengeluaran Bulan Ini</p>
                <p class="text-2xl font-bold text-purple-600">Rp {{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $summary['total_transactions'] ?? 0 }} transaksi</p>
            </div>

            {{-- Pemegang Kas --}}
            <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
                <p class="text-sm font-medium text-gray-500">Pemegang Kas</p>
                <p class="text-lg font-semibold text-gray-700">{{ $fund->holder->name ?? '-' }}</p>
                <p class="text-xs text-gray-400 mt-2">Approver: {{ $fund->approver->name ?? 'Belum diset' }}</p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mb-4 border-b border-gray-200">
            <nav class="flex gap-6">
                <button wire:click="$set('activeTab', 'transactions')" class="pb-3 px-1 font-medium transition {{ $activeTab === 'transactions' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    📋 Transaksi
                </button>
                <button wire:click="$set('activeTab', 'topups')" class="pb-3 px-1 font-medium transition {{ $activeTab === 'topups' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    💳 Top Up
                    @if($pendingTopups > 0)
                        <span class="ml-2 px-2 py-0.5 bg-red-500 text-white text-xs rounded-full">{{ $pendingTopups }}</span>
                    @endif
                </button>
                <button wire:click="$set('activeTab', 'logs')" class="pb-3 px-1 font-medium transition {{ $activeTab === 'logs' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    📜 Log Perubahan
                </button>
            </nav>
        </div>

        {{-- Tab: Transactions --}}
        @if($activeTab === 'transactions')
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">No. Transaksi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Keterangan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Job</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Jumlah</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $t->transaction_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">{{ $t->transaction_number }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">{{ $t->category_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($t->description, 40) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $t->shipment->awb_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">-Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($t->proof_file)
                                <a href="{{ Storage::disk('public')->url($t->proof_file) }}" target="_blank" class="text-blue-500 hover:text-blue-700 transition" title="Lihat Bukti">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Belum ada transaksi
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator && $transactions->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">{{ $transactions->links() }}</div>
            @endif
        </div>
        @endif

        {{-- Tab: Topups --}}
        @if($activeTab === 'topups')
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">No. Top Up</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Request</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Approved</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topups as $tp)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $tp->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-800">{{ $tp->topup_number }}</td>
                            <td class="px-4 py-3 text-sm text-right">Rp {{ number_format($tp->amount_requested, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-green-600">
                                {{ $tp->amount_approved ? 'Rp ' . number_format($tp->amount_approved, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-blue-100 text-blue-800',
                                    'transferred' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$tp->status] ?? '' }}">
                                    {{ ucfirst($tp->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($tp->status === 'pending' && $canApprove)
                                    <button wire:click="approveTopup({{ $tp->id }})" class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 mr-1">Approve</button>
                                    <button wire:click="rejectTopup({{ $tp->id }})" class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600">Tolak</button>
                                @elseif($tp->status === 'approved' && $canApprove)
                                    <button wire:click="transferTopup({{ $tp->id }})" class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">Konfirmasi Transfer</button>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">Belum ada request top up</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($topups instanceof \Illuminate\Pagination\LengthAwarePaginator && $topups->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">{{ $topups->links() }}</div>
            @endif
        </div>
        @endif

        {{-- Tab: Setting Logs --}}
        @if($activeTab === 'logs')
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Diubah Oleh</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Field</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nilai Lama</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nilai Baru</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($settingLogs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800">{{ $log->changedBy->name ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">{{ $log->field_changed }}</span></td>
                            <td class="px-4 py-3 text-sm text-red-600">{{ $log->old_value }}</td>
                            <td class="px-4 py-3 text-sm text-green-600">{{ $log->new_value }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->reason ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">Belum ada log perubahan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endif

    {{-- MODAL: Input Transaksi --}}
    @if($showModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Input Pengeluaran Kas Kecil</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form wire:submit.prevent="saveTransaction" class="p-6">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="date" wire:model="transaction_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('transaction_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp)</label>
                            <input type="number" wire:model="amount" placeholder="50000" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Max: Rp {{ number_format($fund->max_transaction ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select wire:model="category" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach(\App\Models\PettyCashTransaction::CATEGORIES as $key => $cat)
                                <option value="{{ $key }}">{{ $cat['label'] }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <input type="text" wire:model="description" placeholder="Contoh: Parkir kirim ke Tanjung Priok" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Number (Opsional)</label>
                    <div x-data="{ open: false, search: ''  }" class="relative">
                        <input type="text" 
                            x-model="search" 
                            @focus="open = true" 
                            @click.away="open = false"
                            placeholder="Ketik AWB atau nama customer..." 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <div @click="$wire.set('shipment_id', '')" class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-gray-500">-- Tidak terkait job --</div>
                            @foreach($shipments as $s)
                            <div x-show="!search || '{{ strtolower($s->awb_number . ($s->customer->company_name ?? "")) }}'.includes(search.toLowerCase())" 
                                @click="$wire.set('shipment_id', '{{ $s->id }}'); search = '{{ $s->awb_number }}'; open = false" 
                                class="px-4 py-2 hover:bg-blue-50 cursor-pointer">
                                <span class="font-mono text-sm">{{ $s->awb_number }}</span>
                                @if($s->customer)<span class="text-gray-500 text-sm ml-2">- {{ $s->customer->company_name }}</span>@endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Bukti <span class="text-red-500">*</span></label>
                        <input type="file" wire:model="proof_file" accept="image/*,.pdf" class="w-full text-sm border rounded-lg p-2">
                        @error('proof_file') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="proof_file" class="text-sm text-blue-500 mt-1">⏳ Uploading...</div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- MODAL: Request Top Up --}}
    @if($showTopupModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Request Top Up Kas Kecil</h3>
                <button wire:click="$set('showTopupModal', false)" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form wire:submit.prevent="requestTopup" class="p-6">
                <div class="bg-blue-50 p-4 rounded-lg mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Saldo saat ini:</span>
                        <span class="font-semibold">Rp {{ number_format($fund->current_balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-1">
                        <span class="text-gray-600">Max top up:</span>
                        <span class="font-semibold text-green-600">Rp {{ number_format($fund->max_topup_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Top Up</label>
                        <input type="number" wire:model="topup_amount" placeholder="500000" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('topup_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                        <textarea wire:model="topup_notes" rows="2" placeholder="Alasan request top up..." class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showTopupModal', false)" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Kirim Request</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- MODAL: Pengaturan --}}
    @if($showSettingModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">⚙️ Pengaturan Kas Kecil</h3>
                <button wire:click="$set('showSettingModal', false)" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form wire:submit.prevent="saveSettings" class="p-6">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Plafon (Rp)</label>
                            <input type="number" wire:model="setting_plafon" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('setting_plafon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max per Transaksi (Rp)</label>
                            <input type="number" wire:model="setting_max_transaction" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('setting_max_transaction') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alert Saldo Minimum (Rp)</label>
                        <input type="number" wire:model="setting_min_balance" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('setting_min_balance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-400 mt-1">Notifikasi muncul jika saldo di bawah nilai ini</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pemegang Kas</label>
                        <select wire:model="setting_holder_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('setting_holder_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Approver Top Up</label>
                        <select wire:model="setting_approver_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Pilih Approver --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Perubahan</label>
                        <textarea wire:model="setting_reason" rows="2" placeholder="Contoh: Kenaikan plafon untuk kebutuhan operasional..." class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showSettingModal', false)" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Simpan Pengaturan</button>@endif
                </div>
            </form>
        </div>
    </div>
    @endif

    </div>