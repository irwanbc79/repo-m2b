<div>
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">&times;</button>
        </div>
    @endif

    @if (session()->has('info'))
        <div class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg flex items-center justify-between">
            <span>{{ session('info') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-blue-700 hover:text-blue-900">&times;</button>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            🏦 Rekonsiliasi Bank
        </h1>
        <p class="text-gray-600 mt-1">Kelola dan rekonsiliasi transaksi bank dengan pembayaran invoice</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Transaksi</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($statistics['total_transactions'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">📊</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Sudah Rekonsiliasi</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($statistics['reconciled'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400">{{ $statistics['reconciliation_rate'] ?? 0 }}% dari total</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Belum Rekonsiliasi</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($statistics['unreconciled'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">⏳</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Kredit</p>
                    <p class="text-xl font-bold text-blue-600">Rp {{ number_format($statistics['total_credit'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons & Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center justify-between">
            {{-- Left: Action Buttons --}}
            <div class="flex flex-wrap gap-2">
                <button wire:click="openImportModal" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <span>📤</span> Import CSV
                </button>
                <button wire:click="runAutoMatch" 
                        wire:confirm="Jalankan auto-matching untuk semua transaksi?"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                    <span>🔄</span> Auto-Match
                </button>
                <button wire:click="exportExcel" 
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
                    <span>📥</span> Export
                </button>
            </div>

            {{-- Right: Search --}}
            <div class="flex-1 max-w-md">
                <input type="text" wire:model.live.debounce.300ms="search" 
                       placeholder="Cari deskripsi, referensi..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        {{-- Filters Row --}}
        <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t border-gray-200">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-gray-500 mb-1">Bank</label>
                <select wire:model.live="filterBank" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Bank</option>
                    @foreach($supportedBanks as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select wire:model.live="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Status</option>
                    <option value="reconciled">✅ Sudah Rekonsiliasi</option>
                    <option value="unreconciled">⏳ Belum Rekonsiliasi</option>
                </select>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-gray-500 mb-1">Kategori</label>
                <select wire:model.live="filterCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-gray-500 mb-1">Dari Tanggal</label>
                <input type="date" wire:model.live="filterDateFrom" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
                <input type="date" wire:model.live="filterDateTo" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div class="flex items-end">
                <button wire:click="resetFilters" class="px-3 py-2 text-gray-600 hover:text-gray-800 text-sm">
                    🔄 Reset
                </button>
            </div>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-gray-50 {{ $trx->is_reconciled ? 'bg-green-50/30' : '' }}">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $trx->transaction_date->format('d M Y') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $trx->transaction_date->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $trx->description }}">
                                    {{ Str::limit($trx->description, 50) }}
                                </div>
                                @if($trx->reference_number)
                                    <div class="text-xs text-gray-500">
                                        Ref: {{ $trx->reference_number }}
                                    </div>
                                @endif
                                @if($trx->invoicePayment)
                                    <div class="text-xs text-green-600 mt-1">
                                        ✅ {{ $trx->invoicePayment->invoice->invoice_number ?? 'N/A' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $trx->category === 'payment_received' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $trx->category === 'salary' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $trx->category === 'trucking' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $trx->category === 'operational' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $trx->category === 'bank_fee' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ !in_array($trx->category, ['payment_received', 'salary', 'trucking', 'operational', 'bank_fee']) ? 'bg-gray-100 text-gray-800' : '' }}
                                ">
                                    {{ $categories[$trx->category] ?? 'Lainnya' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                @if($trx->credit_amount > 0)
                                    <span class="text-green-600 font-medium">
                                        +{{ number_format($trx->credit_amount, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                @if($trx->debit_amount > 0)
                                    <span class="text-red-600 font-medium">
                                        -{{ number_format($trx->debit_amount, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-600">
                                {{ number_format($trx->balance, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($trx->is_reconciled)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        ✅ Matched
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">
                                        ⏳ Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="showDetail({{ $trx->id }})" 
                                            class="p-1 text-blue-600 hover:text-blue-800" title="Detail">
                                        👁️
                                    </button>
                                    @if(!$trx->is_reconciled && $trx->credit_amount > 0)
                                        <button wire:click="openMatchModal({{ $trx->id }})" 
                                                class="p-1 text-green-600 hover:text-green-800" title="Match">
                                            🔗
                                        </button>
                                    @endif
                                    @if($trx->is_reconciled)
                                        <button wire:click="unmatchTransaction({{ $trx->id }})" 
                                                wire:confirm="Batalkan matching transaksi ini?"
                                                class="p-1 text-orange-600 hover:text-orange-800" title="Unmatch">
                                            ⛓️‍💥
                                        </button>
                                    @endif
                                    <button wire:click="deleteTransaction({{ $trx->id }})" 
                                            wire:confirm="Hapus transaksi ini?"
                                            class="p-1 text-red-600 hover:text-red-800" title="Hapus">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                                <div class="text-4xl mb-2">📭</div>
                                <p>Belum ada transaksi bank</p>
                                <button wire:click="openImportModal" class="mt-2 text-blue-600 hover:text-blue-800">
                                    Import CSV sekarang
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- Import Modal --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeImportModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                            📤 Import Statement Bank
                        </h3>

                        {{-- Import Form --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bank</label>
                                <select wire:model="selectedBank" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    @foreach($supportedBanks as $key => $name)
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">File CSV</label>
                                <input type="file" wire:model="csvFile" accept=".csv,.txt"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('csvFile') 
                                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                                @enderror
                                
                                <div wire:loading wire:target="csvFile" class="text-sm text-blue-600 mt-1">
                                    Mengupload file...
                                </div>
                            </div>

                            {{-- Format Info --}}
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
                                <p class="font-medium text-blue-800 mb-1">Format yang didukung:</p>
                                <ul class="text-blue-700 text-xs space-y-1">
                                    <li>• <strong>Bank Mandiri:</strong> CSV dengan separator semicolon (;)</li>
                                    <li>• <strong>Bank BCA:</strong> CSV dengan separator comma (,)</li>
                                </ul>
                            </div>

                            {{-- Import Result --}}
                            @if($importResult)
                                <div class="rounded-lg p-3 {{ $importResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                                    @if($importResult['success'])
                                        <p class="text-green-800 font-medium">✅ Import Berhasil!</p>
                                        <ul class="text-green-700 text-sm mt-1">
                                            <li>• {{ $importResult['imported'] }} transaksi diimport</li>
                                            <li>• {{ $importResult['duplicates'] }} duplikat dilewati</li>
                                            <li>• {{ $importResult['skipped'] }} baris dilewati</li>
                                        </ul>
                                    @else
                                        <p class="text-red-800 font-medium">❌ Import Gagal</p>
                                        @if(!empty($importResult['errors']))
                                            <ul class="text-red-700 text-sm mt-1">
                                                @foreach($importResult['errors'] as $error)
                                                    <li>• {{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button wire:click="importCsv" 
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="importCsv">Import</span>
                            <span wire:loading wire:target="importCsv">Mengimport...</span>
                        </button>
                        <button wire:click="closeImportModal" 
                                class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Match Modal --}}
    @if($showMatchModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeMatchModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                            🔗 Match Transaksi dengan Payment
                        </h3>

                        {{-- Selected Transaction Info --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-blue-800">
                                <strong>Tanggal:</strong> {{ $selectedTransaction->transaction_date->format('d M Y H:i') }}
                            </p>
                            <p class="text-sm text-blue-800">
                                <strong>Jumlah:</strong> Rp {{ number_format($selectedTransaction->credit_amount, 0, ',', '.') }}
                            </p>
                            <p class="text-sm text-blue-800 truncate">
                                <strong>Deskripsi:</strong> {{ $selectedTransaction->description }}
                            </p>
                        </div>

                        {{-- Search Payment --}}
                        <div class="mb-4">
                            <input type="text" wire:model.live.debounce.300ms="searchPayment" 
                                   placeholder="Cari nomor invoice atau nama customer..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>

                        {{-- Matching Payments List --}}
                        <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                            @forelse($matchingPayments as $payment)
                                <div class="p-3 border-b border-gray-100 hover:bg-gray-50 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $payment['invoice']['invoice_number'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-xs text-gray-600">
                                            {{ $payment['invoice']['customer']['company_name'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($payment['payment_date'])->format('d M Y') }} • 
                                            Rp {{ number_format($payment['amount'], 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <button wire:click="matchWithPayment({{ $payment['id'] }})"
                                            class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                        Match
                                    </button>
                                </div>
                            @empty
                                <div class="p-4 text-center text-gray-500">
                                    Tidak ada payment yang cocok
                                </div>
                            @endforelse
                        </div>

                        {{-- Notes --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                            <input type="text" wire:model="matchingNotes" 
                                   placeholder="Tambahkan catatan..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeMatchModal" 
                                class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetailModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                            📄 Detail Transaksi
                        </h3>

                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Tanggal</p>
                                    <p class="text-sm font-medium">{{ $selectedTransaction->transaction_date->format('d M Y H:i:s') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Bank</p>
                                    <p class="text-sm font-medium">{{ ucfirst($selectedTransaction->bank_name) }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Deskripsi</p>
                                <p class="text-sm">{{ $selectedTransaction->description }}</p>
                            </div>

                            @if($selectedTransaction->additional_description)
                                <div>
                                    <p class="text-xs text-gray-500">Deskripsi Tambahan</p>
                                    <p class="text-sm">{{ $selectedTransaction->additional_description }}</p>
                                </div>
                            @endif

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Kredit</p>
                                    <p class="text-sm font-medium text-green-600">
                                        {{ $selectedTransaction->credit_amount > 0 ? 'Rp ' . number_format($selectedTransaction->credit_amount, 0, ',', '.') : '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Debit</p>
                                    <p class="text-sm font-medium text-red-600">
                                        {{ $selectedTransaction->debit_amount > 0 ? 'Rp ' . number_format($selectedTransaction->debit_amount, 0, ',', '.') : '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Saldo</p>
                                    <p class="text-sm font-medium">Rp {{ number_format($selectedTransaction->balance, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Kategori</p>
                                    <p class="text-sm">{{ $categories[$selectedTransaction->category] ?? 'Lainnya' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Status</p>
                                    <p class="text-sm">
                                        @if($selectedTransaction->is_reconciled)
                                            <span class="text-green-600">✅ Sudah Direkonsiliasi</span>
                                        @else
                                            <span class="text-orange-600">⏳ Belum Direkonsiliasi</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($selectedTransaction->is_reconciled && $selectedTransaction->invoicePayment)
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                    <p class="text-xs text-green-800 font-medium mb-1">Matched dengan:</p>
                                    <p class="text-sm text-green-700">
                                        Invoice: {{ $selectedTransaction->invoicePayment->invoice->invoice_number ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-green-600">
                                        Customer: {{ $selectedTransaction->invoicePayment->invoice->customer->company_name ?? 'N/A' }}
                                    </p>
                                    @if($selectedTransaction->matchedByUser)
                                        <p class="text-xs text-green-600 mt-1">
                                            Matched oleh: {{ $selectedTransaction->matchedByUser->name }} 
                                            pada {{ $selectedTransaction->matched_at->format('d M Y H:i') }}
                                        </p>
                                    @endif
                                </div>
                            @endif

                            @if($selectedTransaction->reference_number)
                                <div>
                                    <p class="text-xs text-gray-500">Reference Number</p>
                                    <p class="text-sm font-mono">{{ $selectedTransaction->reference_number }}</p>
                                </div>
                            @endif

                            <div>
                                <p class="text-xs text-gray-500">Import Batch</p>
                                <p class="text-sm font-mono">{{ $selectedTransaction->import_batch }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeDetailModal" 
                                class="w-full sm:w-auto px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
