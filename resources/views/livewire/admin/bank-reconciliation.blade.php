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
                        wire:loading.attr="disabled"
                        wire:target="runAutoMatch"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2">
                    <span wire:loading.remove wire:target="runAutoMatch">🔄</span>
                    <span wire:loading wire:target="runAutoMatch">⏳</span>
                    <span wire:loading.remove wire:target="runAutoMatch">Auto-Match</span>
                    <span wire:loading wire:target="runAutoMatch">Memproses...</span>
                </button>
                {{-- Export Dropdown --}}
                <div class="relative" id="exportDropdownWrap">
                    <button onclick="toggleExportDropdown()"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
                        <span>📥</span> Export <span style="font-size:10px;">▼</span>
                    </button>
                    <div id="exportDropdown" style="display:none; position:absolute; top:100%; left:0; z-index:200; margin-top:4px;
                                                    background:#fff; border:1px solid #e5e7eb; border-radius:8px;
                                                    box-shadow:0 8px 24px rgba(0,0,0,0.12); min-width:160px; overflow:hidden;">
                        <button onclick="doExport('pdf')"
                                style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 16px; border:none;
                                       background:none; text-align:left; font-size:13px; color:#374151; cursor:pointer;"
                                onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                            📄 Export PDF
                        </button>
                        <button onclick="doExport('excel')"
                                style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 16px; border:none;
                                       background:none; text-align:left; font-size:13px; color:#374151; cursor:pointer;"
                                onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                            📊 Export Excel (.xlsx)
                        </button>
                        <button onclick="doExport('csv')"
                                style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 16px; border:none;
                                       background:none; text-align:left; font-size:13px; color:#374151; cursor:pointer;"
                                onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                            📋 Export CSV
                        </button>
                    </div>
                </div>
            </div>

            {{-- Hidden inputs untuk export JS (diupdate otomatis saat Livewire re-render) --}}
            <input type="hidden" id="exp_search"         value="{{ $search }}">
            <input type="hidden" id="exp_filterBank"     value="{{ $filterBank }}">
            <input type="hidden" id="exp_filterStatus"   value="{{ $filterStatus }}">
            <input type="hidden" id="exp_filterCategory" value="{{ $filterCategory }}">
            <input type="hidden" id="exp_filterDateFrom" value="{{ $filterDateFrom }}">
            <input type="hidden" id="exp_filterDateTo"   value="{{ $filterDateTo }}">

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
                                    <button onclick="openVoucherModal({{ $trx->id }}, {{ $trx->debit_amount > 0 ? 'true' : 'false' }}, {{ json_encode(Str::limit($trx->description, 80)) }}, '')"
                                            class="p-1 text-gray-600 hover:text-gray-900" title="Cetak Voucher">
                                        🖨️
                                    </button>
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

    {{-- ── Voucher Print Modal (vanilla JS — tidak terpengaruh Livewire re-render) ── --}}
    <div id="voucherPrintModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.6);"
         onclick="if(event.target===this) closeVoucherModal()">
        <div id="voucherModalInner" style="background:#fff; border-radius:12px; width:500px; max-width:95vw;
                    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                    box-shadow:0 20px 60px rgba(0,0,0,0.35); overflow:hidden; transition:width 0.25s, height 0.25s;">

            {{-- Header --}}
            <div style="background:#0F2C59; color:#fff; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
                <span id="vModalTitle" style="font-weight:bold; font-size:15px;">🖨️ Cetak Voucher</span>
                <button onclick="closeVoucherModal()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">&times;</button>
            </div>

            {{-- STEP 1: Form isi nama --}}
            <div id="vStepForm">
                <div style="padding:20px;">

                    {{-- Kolom custom: Nama pihak & Keterangan --}}
                    <div style="margin-bottom:16px; padding-bottom:16px; border-bottom:1px solid #e5e7eb;">
                        <div style="margin-bottom:10px;">
                            <label id="v_pihak_label" style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Dibayar Kepada</label>
                            <input id="v_pihak_nama" type="text" placeholder="Nama pihak..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:7px 10px; font-size:13px;">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Keterangan / Uraian</label>
                            <textarea id="v_keterangan" rows="2" placeholder="Keterangan transaksi..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:7px 10px; font-size:13px; resize:vertical;"></textarea>
                        </div>
                    </div>

                    <p style="font-size:12px; color:#666; margin-bottom:14px;">Nama pejabat penandatangan (opsional):</p>

                    {{-- Fields Penerimaan (3 kolom) --}}
                    <div id="vFieldsPenerimaan">
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Diperiksa Oleh</label>
                                <input id="v_diperiksa_nama" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Diketahui Oleh</label>
                                <input id="v_diketahui_nama_p" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Kasir</label>
                                <input id="v_kasir_nama" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                        </div>
                    </div>

                    {{-- Fields Pengeluaran (5 kolom, 2 baris) --}}
                    <div id="vFieldsPengeluaran" style="display:none;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Disetujui Oleh</label>
                                <input id="v_disetujui_nama" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Diketahui Oleh</label>
                                <input id="v_diketahui_nama_k" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Diperiksa Oleh</label>
                                <input id="v_diperiksa2_nama" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Dibayar Oleh</label>
                                <input id="v_dibayar_nama" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#0F2C59; display:block; margin-bottom:4px;">Yang Menerima</label>
                                <input id="v_penerima_nama" type="text" placeholder="Nama..." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:12px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div style="background:#f9fafb; padding:10px 20px; border-top:1px solid #e5e7eb;">
                    {{-- Pilihan ukuran kertas --}}
                    <div style="margin-bottom:10px;">
                        <p style="font-size:11px; font-weight:600; color:#0F2C59; margin-bottom:6px;">Ukuran Kertas:</p>
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            <label style="display:flex; align-items:center; gap:5px; font-size:12px; color:#374151; cursor:pointer;">
                                <input type="radio" name="v_layout" value="" checked style="cursor:pointer;"> A5 (1 lembar)
                            </label>
                            <label style="display:flex; align-items:center; gap:5px; font-size:12px; color:#374151; cursor:pointer;">
                                <input type="radio" name="v_layout" value="half" style="cursor:pointer;"> Setengah A4 <span style="color:#aaa; font-size:11px;">(sisa kosong)</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:5px; font-size:12px; color:#374151; cursor:pointer;">
                                <input type="radio" name="v_layout" value="2up" style="cursor:pointer;"> 2 voucher dalam 1 A4 <span style="color:#aaa; font-size:11px;">(hemat kertas)</span>
                            </label>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button onclick="closeVoucherModal()"
                                style="padding:8px 18px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#374151; font-size:13px; cursor:pointer;">
                            Batal
                        </button>
                        <button onclick="showVoucherPreview()"
                                style="padding:8px 20px; border:none; border-radius:6px; background:#0F2C59; color:#fff; font-size:13px; font-weight:600; cursor:pointer;">
                            👁️ Preview
                        </button>
                    </div>
                </div>
            </div>

            {{-- STEP 2: Preview iframe --}}
            <div id="vStepPreview" style="display:none; flex-direction:column; height:calc(90vh - 52px);">
                {{-- Loading indicator --}}
                <div id="vIframeLoading" style="display:flex; align-items:center; justify-content:center; padding:30px; color:#666; font-size:13px;">
                    <span>⏳ Memuat preview...</span>
                </div>
                <iframe id="voucherFrame"
                        src=""
                        style="flex:1; width:100%; border:none; display:none;"
                        onload="onVoucherFrameLoad()">
                </iframe>
                <div style="background:#f9fafb; padding:12px 20px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e5e7eb; flex-shrink:0;">
                    <button onclick="backToVoucherForm()"
                            style="padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#374151; font-size:13px; cursor:pointer;">
                        ← Kembali
                    </button>
                    <div style="display:flex; gap:8px;">
                        <button onclick="closeVoucherModal()"
                                style="padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#374151; font-size:13px; cursor:pointer;">
                            Tutup
                        </button>
                        <button onclick="printVoucherFrame()"
                                style="padding:8px 20px; border:none; border-radius:6px; background:#0F2C59; color:#fff; font-size:13px; font-weight:600; cursor:pointer;">
                            🖨️ Cetak
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    // ── Export Dropdown ─────────────────────────────────────────────────────────
    var _exportRoutes = {
        pdf:   '{{ route("admin.bank-reconciliation.export.pdf") }}',
        excel: '{{ route("admin.bank-reconciliation.export.excel") }}',
        csv:   '{{ route("admin.bank-reconciliation.export.csv") }}',
    };
    function toggleExportDropdown() {
        var dd = document.getElementById('exportDropdown');
        dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
    }
    function doExport(type) {
        document.getElementById('exportDropdown').style.display = 'none';
        var params = new URLSearchParams();
        var g = function(id){ return document.getElementById(id) ? document.getElementById(id).value : ''; };
        if (g('exp_search'))         params.set('search',         g('exp_search'));
        if (g('exp_filterBank'))     params.set('filterBank',     g('exp_filterBank'));
        if (g('exp_filterStatus'))   params.set('filterStatus',   g('exp_filterStatus'));
        if (g('exp_filterCategory')) params.set('filterCategory', g('exp_filterCategory'));
        if (g('exp_filterDateFrom')) params.set('filterDateFrom', g('exp_filterDateFrom'));
        if (g('exp_filterDateTo'))   params.set('filterDateTo',   g('exp_filterDateTo'));
        var url = _exportRoutes[type] + (params.toString() ? '?' + params.toString() : '');
        window.location.href = url;
    }
    document.addEventListener('click', function(e) {
        var wrap = document.getElementById('exportDropdownWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('exportDropdown').style.display = 'none';
        }
    });

    // ── Voucher Print Modal ──────────────────────────────────────────────────────
    var _voucherId    = null;
    var _voucherDebit = false;
    var _voucherBase  = '{{ rtrim(url("/admin/bank-reconciliation/voucher"), "/") }}';

    function openVoucherModal(id, isDebit, defaultPihak, defaultKet) {
        _voucherId    = id;
        _voucherDebit = isDebit;
        document.getElementById('vModalTitle').textContent = isDebit
            ? '🖨️ Cetak Bukti Pengeluaran'
            : '🖨️ Cetak Bukti Penerimaan';
        document.getElementById('v_pihak_label').textContent = isDebit ? 'Dibayar Kepada' : 'Diterima Dari';
        document.getElementById('v_pihak_nama').placeholder  = isDebit ? 'Nama penerima...' : 'Nama pengirim...';
        document.getElementById('v_pihak_nama').value        = defaultPihak || '';
        document.getElementById('v_keterangan').value        = defaultKet   || '';
        document.getElementById('vFieldsPenerimaan').style.display  = isDebit ? 'none'  : 'block';
        document.getElementById('vFieldsPengeluaran').style.display = isDebit ? 'block' : 'none';
        // reset input tanda tangan
        ['v_diperiksa_nama','v_diketahui_nama_p','v_kasir_nama',
         'v_disetujui_nama','v_diketahui_nama_k','v_diperiksa2_nama',
         'v_dibayar_nama','v_penerima_nama'].forEach(function(fid){ var el=document.getElementById(fid); if(el) el.value=''; });
        // reset modal ke ukuran form
        var inner = document.getElementById('voucherModalInner');
        inner.style.width  = '500px';
        inner.style.height = 'auto';
        document.getElementById('vStepForm').style.display    = 'block';
        document.getElementById('vStepPreview').style.display = 'none';
        document.getElementById('voucherFrame').src = '';
        document.getElementById('voucherPrintModal').style.display = 'block';
    }

    function closeVoucherModal() {
        document.getElementById('voucherPrintModal').style.display = 'none';
        document.getElementById('voucherFrame').src = '';
    }

    function buildVoucherUrl() {
        var params = new URLSearchParams();
        // Layout kertas
        var layoutEl = document.querySelector('input[name="v_layout"]:checked');
        var layout = layoutEl ? layoutEl.value : '';
        if (layout) params.set('layout', layout);
        // Kolom custom
        var pihak = document.getElementById('v_pihak_nama').value.trim();
        var ket   = document.getElementById('v_keterangan').value.trim();
        if (pihak) params.set('custom_pihak', pihak);
        if (ket)   params.set('custom_keterangan', ket);
        // Tanda tangan
        if (_voucherDebit) {
            var n;
            n = document.getElementById('v_disetujui_nama').value.trim();   if(n) params.set('disetujui_nama', n);
            n = document.getElementById('v_diketahui_nama_k').value.trim(); if(n) params.set('diketahui_nama', n);
            n = document.getElementById('v_diperiksa2_nama').value.trim();  if(n) params.set('diperiksa_nama', n);
            n = document.getElementById('v_dibayar_nama').value.trim();     if(n) params.set('dibayar_nama', n);
            n = document.getElementById('v_penerima_nama').value.trim();    if(n) params.set('penerima_nama', n);
        } else {
            var n;
            n = document.getElementById('v_diperiksa_nama').value.trim();   if(n) params.set('diperiksa_nama', n);
            n = document.getElementById('v_diketahui_nama_p').value.trim(); if(n) params.set('diketahui_nama', n);
            n = document.getElementById('v_kasir_nama').value.trim();       if(n) params.set('kasir_nama', n);
        }
        var url = _voucherBase + '/' + _voucherId;
        var qs  = params.toString();
        if (qs) url += '?' + qs;
        return url;
    }

    function showVoucherPreview() {
        if (!_voucherId) return;
        var url = buildVoucherUrl();
        // Perbesar modal ke mode preview
        var inner = document.getElementById('voucherModalInner');
        inner.style.width  = 'min(860px, 95vw)';
        inner.style.height = '90vh';
        // Tampilkan loading, sembunyikan iframe dulu
        document.getElementById('vIframeLoading').style.display = 'flex';
        document.getElementById('voucherFrame').style.display   = 'none';
        document.getElementById('voucherFrame').src = url;
        // Tukar step
        document.getElementById('vStepForm').style.display    = 'none';
        document.getElementById('vStepPreview').style.display = 'flex';
    }

    function onVoucherFrameLoad() {
        document.getElementById('vIframeLoading').style.display = 'none';
        document.getElementById('voucherFrame').style.display   = 'block';
    }

    function backToVoucherForm() {
        var inner = document.getElementById('voucherModalInner');
        inner.style.width  = '500px';
        inner.style.height = 'auto';
        document.getElementById('vStepPreview').style.display = 'none';
        document.getElementById('vStepForm').style.display    = 'block';
        document.getElementById('voucherFrame').src = '';
    }

    function printVoucherFrame() {
        var frame = document.getElementById('voucherFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }
    </script>
</div>
