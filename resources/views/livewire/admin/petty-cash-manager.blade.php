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
            @endif
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
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $t)
                        <tr class="hover:bg-gray-50 {{ $t->isCancelled() ? 'bg-gray-50/70' : '' }}">
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $t->transaction_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm font-mono {{ $t->isCancelled() ? 'text-gray-400 line-through' : 'text-gray-800' }}">{{ $t->transaction_number }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">{{ $t->category_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <span class="{{ $t->isCancelled() ? 'line-through text-gray-400' : '' }}">{{ Str::limit($t->description, 40) }}</span>

                                {{-- Penanda status: dibatalkan & pernah diubah sengaja terlihat
                                     di daftar, bukan disembunyikan di dalam modal. --}}
                                @if($t->isCancelled())
                                    <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 rounded"
                                          title="{{ $t->reject_reason }}">DIBATALKAN</span>
                                @elseif($t->logs_count > 0)
                                    <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded">DIUBAH</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $t->shipment->awb_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold {{ $t->isCancelled() ? 'text-gray-400 line-through' : 'text-red-600' }}">-Rp {{ number_format($t->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($t->proof_file)
                                <button onclick="openProof('{{ Storage::disk('public')->url($t->proof_file) }}', '{{ pathinfo($t->proof_file, PATHINFO_EXTENSION) }}')" class="text-blue-500 hover:text-blue-700 transition" title="Lihat Bukti">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    @if($t->logs_count > 0)
                                    <button wire:click="openRiwayat({{ $t->id }})"
                                        class="p-1.5 text-gray-400 hover:text-gray-700 transition" title="Lihat riwayat perubahan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    @endif

                                    @if($this->canEditTransaction($t))
                                    <button wire:click="openEdit({{ $t->id }})"
                                        class="p-1.5 text-blue-500 hover:text-blue-700 transition" title="Ubah transaksi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="openBatal({{ $t->id }})"
                                        class="p-1.5 text-red-400 hover:text-red-600 transition" title="Batalkan transaksi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500">
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
    <div class="erp-modal-backdrop">
        <div class="erp-modal-panel max-w-lg">
            <div class="erp-modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base font-semibold border border-blue-100 shadow-sm">
                        💸
                    </div>
                    <div>
                        <h3 class="erp-modal-title">Input Pengeluaran Kas Kecil</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Catat voucher pengeluaran kas operasional</p>
                    </div>
                </div>
                <button wire:click="$set('showModal', false)" class="erp-modal-close" aria-label="Tutup">&times;</button>
            </div>
            <form wire:submit.prevent="saveTransaction" class="erp-modal-body">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal</label>
                            <input type="date" wire:model="transaction_date" class="erp-input-modern">
                            @error('transaction_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah (Rp)</label>
                            <input type="number" wire:model="amount" placeholder="50000" class="erp-input-modern font-mono">
                            @error('amount') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            <p class="text-[11px] text-slate-400 mt-1 font-mono">Max: Rp {{ number_format($fund->max_transaction ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kategori</label>
                        <select wire:model="category" class="erp-input-modern">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach(\App\Models\PettyCashTransaction::CATEGORIES as $key => $cat)
                                <option value="{{ $key }}">{{ $cat['label'] }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan</label>
                        <input type="text" wire:model="description" placeholder="Contoh: Parkir kirim ke Tanjung Priok" class="erp-input-modern">
                        @error('description') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                    <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Job Number (Opsional)</label>
                    <div x-data="{ open: false, search: ''  }" class="relative">
                        <input type="text" 
                            x-model="search" 
                            @focus="open = true" 
                            @click.away="open = false"
                            placeholder="Ketik No. Shipment atau nama customer..." 
                            class="erp-input-modern">
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white/95 backdrop-blur-md border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                            <div @click="$wire.set('shipment_id', ''); search = ''; open = false" class="px-4 py-2 hover:bg-slate-100 cursor-pointer text-slate-500 text-xs">-- Tidak terkait job --</div>
                            @foreach($shipments as $s)
                            <div x-show="!search || '{{ strtolower($s->awb_number . ($s->customer->company_name ?? "")) }}'.includes(search.toLowerCase())" 
                                @click="$wire.set('shipment_id', '{{ $s->id }}'); search = '{{ $s->awb_number }}'; open = false" 
                                class="px-4 py-2 hover:bg-blue-50/70 cursor-pointer transition text-xs">
                                <span class="font-mono font-semibold text-blue-700">{{ $s->awb_number }}</span>
                                @if($s->customer)<span class="text-slate-500 ml-2">- {{ $s->customer->company_name }}</span>@endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Upload Bukti <span class="text-red-500">*</span></label>
                        <input type="file" wire:model="proof_file" accept="image/*,.pdf" class="erp-input-modern file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        @error('proof_file') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="proof_file" class="text-xs text-blue-600 font-medium mt-1.5 flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Uploading bukti...
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end gap-2.5">
                    <button type="button" wire:click="$set('showModal', false)" class="erp-btn-secondary">Batal</button>
                    <button type="submit" class="erp-btn-primary">
                        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- MODAL: Request Top Up --}}
    @if($showTopupModal)
    <div class="erp-modal-backdrop">
        <div class="erp-modal-panel max-w-md">
            <div class="erp-modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base font-semibold border border-emerald-100 shadow-sm">
                        💰
                    </div>
                    <div>
                        <h3 class="erp-modal-title">Request Top Up Kas Kecil</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pengajuan pengisian kembali dana kas operasional</p>
                    </div>
                </div>
                <button wire:click="$set('showTopupModal', false)" class="erp-modal-close" aria-label="Tutup">&times;</button>
            </div>
            <form wire:submit.prevent="requestTopup" class="erp-modal-body">
                <div class="bg-gradient-to-br from-emerald-50/60 to-blue-50/40 p-4 rounded-xl border border-emerald-100 mb-4 shadow-sm">
                    <div class="flex justify-between text-xs items-center">
                        <span class="text-slate-600 font-medium">Saldo saat ini:</span>
                        <span class="font-bold text-slate-800 font-mono text-sm">Rp {{ number_format($fund->current_balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-xs items-center mt-2 pt-2 border-t border-emerald-200/50">
                        <span class="text-slate-600 font-medium">Max top up diizinkan:</span>
                        <span class="font-bold text-emerald-700 font-mono text-sm">Rp {{ number_format($fund->max_topup_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah Top Up (Rp)</label>
                        <input type="number" wire:model="topup_amount" placeholder="500000" class="erp-input-modern font-mono">
                        @error('topup_amount') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Pengajuan (Opsional)</label>
                        <textarea wire:model="topup_notes" rows="2" placeholder="Alasan request top up..." class="erp-input-modern"></textarea>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end gap-2.5">
                    <button type="button" wire:click="$set('showTopupModal', false)" class="erp-btn-secondary">Batal</button>
                    <button type="submit" class="erp-btn-primary bg-gradient-to-r from-emerald-600 to-teal-600 shadow-emerald-500/25">
                        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Kirim Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- MODAL: Pengaturan --}}
    @if($showSettingModal)
    <div class="erp-modal-backdrop">
        <div class="erp-modal-panel max-w-lg">
            <div class="erp-modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-base font-semibold border border-slate-200 shadow-sm">
                        ⚙️
                    </div>
                    <div>
                        <h3 class="erp-modal-title">Pengaturan Kas Kecil</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Konfigurasi limit plafon, ambang alert, dan otoritas</p>
                    </div>
                </div>
                <button wire:click="$set('showSettingModal', false)" class="erp-modal-close" aria-label="Tutup">&times;</button>
            </div>
            <form wire:submit.prevent="saveSettings" class="erp-modal-body">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Plafon (Rp)</label>
                            <input type="number" wire:model="setting_plafon" class="erp-input-modern font-mono">
                            @error('setting_plafon') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Max per Transaksi (Rp)</label>
                            <input type="number" wire:model="setting_max_transaction" class="erp-input-modern font-mono">
                            @error('setting_max_transaction') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alert Saldo Minimum (Rp)</label>
                        <input type="number" wire:model="setting_min_balance" class="erp-input-modern font-mono">
                        @error('setting_min_balance') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        <p class="text-[11px] text-slate-400 mt-1">Notifikasi sistem muncul otomatis jika saldo tersisa di bawah nilai ini</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Pemegang Kas (Holder)</label>
                        <select wire:model="setting_holder_id" class="erp-input-modern">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('setting_holder_id') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Approver Top Up</label>
                        <select wire:model="setting_approver_id" class="erp-input-modern">
                            <option value="">-- Pilih Approver --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alasan Perubahan Kebijakan</label>
                        <textarea wire:model="setting_reason" rows="2" placeholder="Contoh: Kenaikan plafon untuk kebutuhan operasional..." class="erp-input-modern"></textarea>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end gap-2.5">
                    <button type="button" wire:click="$set('showSettingModal', false)" class="erp-btn-secondary">Batal</button>
                    <button type="submit" class="erp-btn-primary">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ══════════ UBAH TRANSAKSI ══════════ --}}
    @if($showEditModal)
    <div class="erp-modal-backdrop">
        <div class="erp-modal-panel max-w-lg">
            <div class="erp-modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base font-semibold border border-amber-100 shadow-sm">
                        ✏️
                    </div>
                    <div>
                        <h3 class="erp-modal-title">Ubah Transaksi</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Koreksi data voucher pengeluaran kas kecil</p>
                    </div>
                </div>
                <button wire:click="$set('showEditModal', false)" class="erp-modal-close" aria-label="Tutup">&times;</button>
            </div>

            <form wire:submit.prevent="saveEdit" class="erp-modal-body space-y-4">
                <div class="text-xs text-amber-800 bg-amber-50/80 border border-amber-200/80 rounded-xl p-3 flex items-start gap-2.5">
                    <span class="text-amber-600 text-sm shrink-0">⚠️</span>
                    <p class="leading-relaxed">
                        Mengubah <strong>jumlah</strong>, <strong>kategori</strong>, atau <strong>tanggal</strong> ikut
                        memperbaiki pembukuan: jurnal lama dibalik, lalu dibuat jurnal baru. Saldo kas menyesuaikan otomatis.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal</label>
                    <input type="date" wire:model="editTanggal" class="erp-input-modern">
                    @error('editTanggal') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jumlah (Rp)</label>
                    <input type="number" wire:model="editJumlah" min="1" step="1" class="erp-input-modern font-mono">
                    @error('editJumlah') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kategori</label>
                    <select wire:model="editKategori" class="erp-input-modern">
                        @foreach(\App\Models\PettyCashTransaction::CATEGORIES as $key => $cat)
                            <option value="{{ $key }}">{{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                    @error('editKategori') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan</label>
                    <input type="text" wire:model="editKeterangan" class="erp-input-modern">
                    @error('editKeterangan') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Job (opsional)</label>

                    <div x-data="{ open: false, search: @js($editJobLabel) }" class="relative">
                        <input type="text"
                            x-model="search"
                            @focus="open = true"
                            @click.away="open = false"
                            placeholder="Ketik No. Shipment atau nama customer..."
                            class="erp-input-modern">

                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white/95 backdrop-blur-md border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                            <div @click="$wire.set('editJob', ''); search = ''; open = false"
                                class="px-4 py-2 hover:bg-slate-100 cursor-pointer text-slate-500 text-xs">— tidak terkait job —</div>

                            @foreach($shipments as $s)
                            <div x-show="!search || '{{ strtolower($s->awb_number . ' ' . ($s->customer->company_name ?? '')) }}'.includes(search.toLowerCase())"
                                @click="$wire.set('editJob', '{{ $s->id }}'); search = '{{ $s->awb_number }}'; open = false"
                                class="px-4 py-2 hover:bg-blue-50/70 cursor-pointer transition text-xs">
                                <span class="font-mono font-semibold text-blue-700">{{ $s->awb_number }}</span>
                                @if($s->customer)<span class="text-slate-500 ml-2">- {{ $s->customer->company_name }}</span>@endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Kosongkan kotak lalu pilih "tidak terkait job" untuk melepas kaitan.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alasan perubahan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="editAlasan" placeholder="mis. salah ketik nominal"
                        class="erp-input-modern">
                    <p class="text-[11px] text-slate-500 mt-1">Dicatat di riwayat perubahan supaya jejak audit bisa ditelusuri.</p>
                    @error('editAlasan') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" wire:click="$set('showEditModal', false)" class="erp-btn-secondary">Batal</button>
                    <button type="submit" class="erp-btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ══════════ BATALKAN TRANSAKSI ══════════ --}}
    @if($showBatalModal)
    <div class="erp-modal-backdrop">
        <div class="erp-modal-panel max-w-md">
            <div class="erp-modal-header border-b border-red-100 bg-red-50/40">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-base font-semibold border border-red-200 shadow-sm">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="erp-modal-title text-red-700">Batalkan Transaksi</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Void voucher & pembukuan otomatis</p>
                    </div>
                </div>
                <button wire:click="$set('showBatalModal', false)" class="erp-modal-close" aria-label="Tutup">&times;</button>
            </div>

            <form wire:submit.prevent="confirmBatal" class="erp-modal-body space-y-4">
                <div class="text-xs text-slate-600 bg-slate-50 border border-slate-200/80 rounded-xl p-3 leading-relaxed">
                    Saldo kas kecil dikembalikan dan efeknya di buku besar ditiadakan lewat jurnal balik.
                    Barisnya <strong>tetap terlihat</strong> dengan tanda dibatalkan — bukan dihapus, supaya
                    nomor transaksi tidak lompat tanpa penjelasan audit.
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alasan pembatalan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="batalAlasan" placeholder="mis. dobel input"
                        class="erp-input-modern">
                    @error('batalAlasan') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" wire:click="$set('showBatalModal', false)" class="erp-btn-secondary">Tidak jadi</button>
                    <button type="submit" class="erp-btn-danger">
                        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Ya, batalkan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ══════════ RIWAYAT PERUBAHAN ══════════ --}}
    @if($showRiwayatModal)
    <div class="erp-modal-backdrop">
        <div class="erp-modal-panel max-w-lg">
            <div class="erp-modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-base font-semibold border border-slate-200 shadow-sm">
                        📜
                    </div>
                    <div>
                        <h3 class="erp-modal-title">Riwayat Perubahan (Audit Trail)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Jejak log aktivitas & perubahan data</p>
                    </div>
                </div>
                <button wire:click="$set('showRiwayatModal', false)" class="erp-modal-close" aria-label="Tutup">&times;</button>
            </div>

            <div class="erp-modal-body space-y-4">
                @forelse($riwayat as $log)
                <div class="border-l-2 {{ $log->action === 'dibatalkan' ? 'border-red-400' : 'border-amber-400' }} pl-4 pb-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md font-mono {{ $log->action === 'dibatalkan' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                            {{ strtoupper($log->action) }}
                        </span>
                        <span class="text-xs font-semibold text-slate-800">{{ $log->changed_by_name ?? '—' }}</span>
                        <span class="ml-auto text-[11px] text-slate-400 tabular-nums font-mono">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    @if($log->reason)
                        <p class="text-xs text-slate-600 mt-1.5 bg-slate-50/70 p-2 rounded-lg border border-slate-100">Alasan: {{ $log->reason }}</p>
                    @endif

                    @if($log->changes)
                    <ul class="mt-2 space-y-1">
                        @foreach($log->changes as $field => $ubah)
                        <li class="text-xs text-slate-600 font-mono">
                            <span class="font-medium text-slate-700 font-sans">{{ \App\Models\PettyCashTransactionLog::labelField($field) }}:</span>
                            <span class="text-red-500 line-through">{{ $ubah['dari'] ?? '—' }}</span>
                            <span class="text-slate-400 mx-1">→</span>
                            <span class="text-emerald-600 font-semibold">{{ $ubah['ke'] ?? '—' }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @empty
                <div class="text-center py-8">
                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl mb-2">📜</div>
                    <p class="text-xs text-slate-500 font-medium">Belum ada riwayat perubahan tercatat.</p>
                </div>
                @endforelse
            </div>
            <div class="erp-modal-footer">
                <button type="button" wire:click="$set('showRiwayatModal', false)" class="erp-btn-secondary w-full sm:w-auto">Tutup</button>
            </div>
        </div>
    </div>
    @endif

    <div id="proofOverlay" onclick="if(event.target===this)closeProof()" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(6,13,26,0.75);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);justify-content:center;align-items:center;padding:1rem">
        <div style="background:#fff;border-radius:1rem;max-width:900px;width:100%;max-height:90vh;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);border:1px solid rgba(226,232,240,0.8)">
            <div style="background:#0a1629;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;border-top-left-radius:1rem;border-top-right-radius:1rem">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#38bdf8"></span>
                    <span style="color:#fff;font-weight:600;font-size:14px;letter-spacing:0.025em">Preview Bukti Transaksi</span>
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    <a id="proofDownload" href="#" download style="padding:6px 14px;background:#0d9488;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,0.1)">Download</a>
                    <a id="proofNewTab" href="#" target="_blank" style="padding:6px 14px;background:#2563eb;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,0.1)">Tab Baru</a>
                    <button onclick="closeProof()" style="color:#94a3b8;font-size:24px;background:none;border:none;cursor:pointer;margin-left:8px;line-height:1" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
                </div>
            </div>
            <div id="proofBody" style="padding:20px;background:#f8fafc;display:flex;justify-content:center;align-items:center;max-height:calc(90vh - 65px);overflow:auto"></div>
        </div>
    </div>
    <script>
    function openProof(url, ext) {
        document.getElementById("proofDownload").href = url;
        document.getElementById("proofNewTab").href = url;
        var body = document.getElementById("proofBody");
        if (ext === "pdf") {
            body.innerHTML = "<iframe src=\"" + url + "\" style=\"width:100%;height:75vh;border:none;border-radius:8px\"></iframe>";
        } else {
            body.innerHTML = "<img src=\"" + url + "\" style=\"max-width:100%;max-height:75vh;object-fit:contain;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,.1)\">";
        }
        var o = document.getElementById("proofOverlay");
        o.style.display = "flex";
    }
    function closeProof() {
        document.getElementById("proofOverlay").style.display = "none";
        document.getElementById("proofBody").innerHTML = "";
    }
    document.addEventListener("keydown", function(e) { if (e.key === "Escape") closeProof(); });
    </script>

</div>