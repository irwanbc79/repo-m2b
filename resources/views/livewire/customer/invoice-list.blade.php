<div
    class="p-6 space-y-6"
    x-data="{
        invoiceOpen: false,
        invoiceSrc: '',
        proofOpen: false,
        proofSrc: '',
        proofType: ''
    }"
>

    @section('header', 'Pembayaran & Invoice')

    {{-- Flash Messages --}}
    @if (session()->has('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-md">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Total Invoice</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Lunas</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['paid'] }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Belum Lunas</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['unpaid'] }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Total Tagihan</p>
                    <p class="text-xl font-bold text-orange-600">IDR {{ number_format($stats['total_unpaid_amount'], 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-2">
                <select wire:model.live="filterStatus" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">Semua Status</option>
                    <option value="paid">Lunas</option>
                    <option value="unpaid">Belum Lunas</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari invoice atau AWB..." class="pl-10 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full md:w-64">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- Invoice Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">No. Invoice</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Shipment</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tipe</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Total</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-mono font-medium text-blue-600">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($invoice->shipment)
                            <span class="font-mono text-xs text-gray-600">{{ $invoice->shipment->awb_number }}</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(strtolower($invoice->type) == 'proforma')
                            <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded">Proforma</span>
                            @else
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">Commercial</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @php
                                $dueDate = \Carbon\Carbon::parse($invoice->due_date);
                                $isOverdue = $dueDate->isPast() && $invoice->status === 'unpaid';
                            @endphp
                            <span class="{{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                {{ $dueDate->format('d/m/Y') }}
                                @if($isOverdue)
                                <span class="text-xs">(Lewat)</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">
                            IDR {{ number_format($invoice->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($invoice->status === 'paid')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                ✓ Lunas
                            </span>
                            @elseif($invoice->status === 'unpaid')
                                @if($invoice->payment_proof)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    ⏳ Menunggu Verifikasi
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    ⏳ Belum Lunas
                                </span>
                                @endif
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                ✕ Dibatalkan
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                {{-- View/Print Invoice --}}
                                <button
    type="button"
    @click="$dispatch('open-invoice-preview', { id: {{ $invoice->id }} })"
    class="p-1.5 text-blue-600 hover:bg-blue-100 rounded-lg transition"
    title="Preview Invoice">

                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                
                                {{-- Upload Payment Proof (hanya untuk unpaid & belum upload) --}}
                                    {{-- Upload bukti bayar sementara dinonaktifkan --}}
                                
                                {{-- View Payment Proof (jika sudah upload) --}}
                                @if($invoice->payment_proof)
<button
    type="button"
    @click="
        proofOpen = true;
        proofSrc = '{{ Storage::url($invoice->payment_proof) }}';
        proofType = '{{ strtolower(pathinfo($invoice->payment_proof, PATHINFO_EXTENSION)) }}';
    "
    class="p-1.5 text-green-600 hover:bg-green-100 rounded-lg transition"
    title="Preview Bukti Bayar"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
</button>
@endif

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="font-medium">Belum ada invoice</p>
                            <p class="text-sm">Invoice akan muncul setelah ada shipment yang dibuat</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($invoices->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>

    {{-- Upload Payment Modal --}}
    @if($showUploadModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative mx-auto p-0 w-full max-w-lg shadow-2xl rounded-2xl bg-white">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Upload Bukti Pembayaran
                    </h3>
                    <button wire:click="closeUploadModal" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                @if($selectedInvoice)
                {{-- Invoice Info --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500">No. Invoice:</span>
                            <span class="font-semibold text-blue-700">{{ $selectedInvoice->invoice_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Shipment:</span>
                            <span class="font-mono text-xs">{{ $selectedInvoice->shipment->awb_number ?? '-' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">Total Tagihan:</span>
                            <span class="font-bold text-lg text-orange-600">IDR {{ number_format($selectedInvoice->grand_total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                <form wire:submit.prevent="uploadPaymentProof" class="space-y-4">
                    {{-- Tanggal Pembayaran --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="paymentDate" class="w-full border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500" required>
                        @error('paymentDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Upload File --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bukti Transfer/Pembayaran <span class="text-red-500">*</span></label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-orange-400 transition">
                            <input type="file" wire:model="paymentProof" accept=".jpg,.jpeg,.png,.pdf" class="hidden" id="paymentProofInput">
                            <label for="paymentProofInput" class="cursor-pointer">
                                @if($paymentProof)
                                <div class="text-green-600">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-sm font-medium">{{ $paymentProof->getClientOriginalName() }}</p>
                                    <p class="text-xs text-gray-500">Klik untuk ganti file</p>
                                </div>
                                @else
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <p class="text-sm text-gray-600">Klik untuk upload file</p>
                                <p class="text-xs text-gray-400">JPG, PNG, PDF (Max 5MB)</p>
                                @endif
                            </label>
                        </div>
                        @error('paymentProof') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                        <textarea wire:model="paymentNote" rows="2" class="w-full border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 text-sm" placeholder="Contoh: Transfer via BCA, a/n John Doe"></textarea>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="closeUploadModal" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Upload & Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    {{-- ================= INVOICE PREVIEW MODAL ================= --}}
<div
    x-on:open-invoice-preview.window="
        invoiceOpen = true;
        invoiceSrc = '/customer/invoices/' + $event.detail.id + '/preview';
    "
    x-show="invoiceOpen"
    x-cloak
    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60"
>

    <div class="bg-white w-full max-w-5xl h-[85vh] rounded-xl shadow-2xl overflow-hidden relative">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-800">Preview Invoice</h3>
            <button
    @click="invoiceOpen = false"
    class="text-gray-500 hover:text-red-600 text-xl leading-none">
    ×
</button>
        </div>

        {{-- Content --}}
        <iframe :src="invoiceSrc" class="w-full h-full border-0 bg-white"></iframe>
    </div>
</div>
{{-- ========================================================= --}}

{{-- ================= BUKTI BAYAR PREVIEW MODAL ================= --}}
<div
    x-show="proofOpen"
    x-cloak
    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60"
>
    <div class="bg-white w-full max-w-3xl max-h-[85vh] rounded-xl shadow-2xl relative flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3 border-b bg-gray-50 shrink-0">
            <h3 class="font-semibold text-gray-800">Preview Bukti Pembayaran</h3>
            <button
                @click="proofOpen = false"
                class="text-gray-500 hover:text-red-600 text-xl leading-none">
                ×
            </button>
        </div>

        {{-- Content (SCROLL HERE) --}}
        <div class="flex-1 overflow-y-auto bg-gray-100 p-6">
            <template x-if="proofType === 'pdf'">
                <iframe
                    :src="proofSrc"
                    class="w-full h-[75vh] border-0 bg-white rounded"
                ></iframe>
            </template>

            <template x-if="proofType !== 'pdf'">
                <img
                    :src="proofSrc"
                    class="block max-w-full h-auto"
                >
            </template>
        </div>
    </div>
</div>
{{-- ============================================================ --}}

</div>
