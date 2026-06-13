@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">💸 Simple Invoice</h1>
            <p class="text-gray-600 mt-1">Invoice sederhana untuk biaya misc</p>
        </div>
        <a href="{{ route('finance.simple-invoice.create') }}"
           class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
            + Buat Invoice Baru
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <p class="text-green-700">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <p class="text-red-700">{{ session('error') }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-600">Total Invoices</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
                <div class="text-blue-500 text-3xl">📊</div>
            </div>
            <p class="text-sm text-gray-500 mt-2">Rp {{ number_format($stats['total_amount'], 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-600">Unpaid</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['unpaid'] }}</p>
                </div>
                <div class="text-red-500 text-3xl">❌</div>
            </div>
            <p class="text-sm text-gray-500 mt-2">Rp {{ number_format($stats['unpaid_amount'], 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-600">Paid</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['paid'] }}</p>
                </div>
                <div class="text-green-500 text-3xl">✅</div>
            </div>
            <p class="text-sm text-gray-500 mt-2">Rp {{ number_format($stats['paid_amount'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">🔍 Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Invoice Number or Customer Name..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">📋 Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Status</option>
                        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">📅 Year</label>
                    <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Years</option>
                        @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                    <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Months</option>
                        @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                        </option>
                        @endfor
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">🔍</button>
                    <a href="{{ route('finance.simple-invoice.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">🔄</a>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow mb-2 p-3">
        <div class="flex justify-between items-center text-sm text-gray-600">
            <span>Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }}</span>
            <span>Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }}</span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Invoice No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Keterangan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ $invoice->invoice_number }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $invoice->customer_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        @php
                            $itemsCount = $invoice->items->count();
                            $firstItem = $invoice->items->first();
                            $allDescriptions = $invoice->items->pluck('description')->join("\n");
                        @endphp
                        @if($firstItem)
                            <div class="truncate max-w-[280px]" title="{{ $allDescriptions }}">
                                {{ $firstItem->description }}
                                @if($itemsCount > 1)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100 whitespace-nowrap">
                                        +{{ $itemsCount - 1 }} item
                                    </span>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-400 italic">Tidak ada keterangan</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $invoice->formatted_total }}</td>
                    <td class="px-4 py-3 text-sm">
                        {!! $invoice->status_badge !!}
                        @if($invoice->status === 'paid' && $invoice->paid_date)
                        <div class="text-xs text-gray-500 mt-1">{{ $invoice->paid_date->format('d M Y') }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex gap-2">
                            <button onclick="openViewModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}')"
                                    class="text-blue-600 hover:text-blue-800 text-lg" title="View">🔍</button>
                            <button onclick="openPaymentModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}', '{{ $invoice->status }}', '{{ $invoice->paid_date }}', '{{ addslashes($invoice->payment_notes) }}')"
                                    class="text-green-600 hover:text-green-800 text-lg" title="Payment">💰</button>
                            @if($invoice->payment_proof)
                            <button onclick="viewProof('{{ asset('storage/' . $invoice->payment_proof) }}', '{{ pathinfo($invoice->payment_proof, PATHINFO_EXTENSION) }}')"
                                    class="text-orange-600 hover:text-orange-800 text-lg" title="View Proof">📎</button>
                            @endif
                            <button onclick="openPrintPreview('{{ route('finance.simple-invoice.print', $invoice->id) }}')"
                               class="text-green-600 hover:text-green-800 text-lg" title="Print">🖨️</button>
                            <a href="{{ route('finance.simple-invoice.edit', $invoice->id) }}"
                               class="text-purple-600 hover:text-purple-800 text-lg" title="Edit">✏️</a>
                            @if(in_array(auth()->user()->role, ['admin', 'director']))
                            <form action="{{ route('finance.simple-invoice.destroy', $invoice->id) }}" method="POST" 
                                  onsubmit="return confirm('Yakin hapus {{ $invoice->invoice_number }}?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-lg" title="Delete">🗑️</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        @if(request()->hasAny(['search', 'status', 'date_from', 'date_to', 'year', 'month']))
                            No invoices found. <a href="{{ route('finance.simple-invoice.index') }}" class="text-blue-600">Clear filters</a>
                        @else
                            Belum ada invoice. <a href="{{ route('finance.simple-invoice.create') }}" class="text-blue-600">Buat yang pertama</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($invoices->hasPages())
        <div class="px-6 py-4 border-t">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>

{{-- View Modal --}}
<div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h2 class="text-xl font-bold" id="viewModalTitle">Invoice</h2>
            <div class="flex gap-2">
                <a id="editLink" href="#" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-semibold">
                    ✏️ Edit
                </a>
                <a id="downloadLink" href="#" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-semibold">
                    📥 Download
                </a>
                <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">✕</button>
            </div>
        </div>
        <div class="flex-1 overflow-auto p-6" id="invoiceContent">
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    <div class="text-4xl mb-2">⏳</div>
                    <div class="text-gray-500">Loading invoice...</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Payment Modal --}}
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <form id="paymentForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4" id="paymentModalTitle">Update Payment</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" id="paymentStatus" required class="w-full px-4 py-2 border rounded-lg" onchange="togglePaidFields()">
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div id="paidDateField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Bayar *</label>
                        <input type="date" name="paid_date" id="paidDate" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div id="proofField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bukti 📎</label>
                        <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full px-4 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Max 5MB (JPG, PNG, PDF)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="payment_notes" id="paymentNotes" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-2 p-4 border-t bg-gray-50">
                <button type="button" onclick="closePaymentModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">💾 Update</button>
            </div>
        </form>
    </div>
</div>

{{-- Payment Proof Modal --}}
<div id="proofModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-5/6 flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-xl font-bold">📎 Payment Proof</h2>
            <button onclick="closeProofModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">✕</button>
        </div>
        <div class="flex-1 overflow-hidden">
            <div id="proofImageContainer" class="hidden h-full p-4 flex items-center justify-center bg-gray-100">
                <img id="proofImage" src="" class="max-w-full max-h-full object-contain">
            </div>
            <div id="proofPdfContainer" class="hidden h-full">
                <iframe id="proofPdf" src="" class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openViewModal(id, no) {
    const modal = document.getElementById('viewModal');
    const content = document.getElementById('invoiceContent');
    
    modal.classList.remove('hidden');
    document.getElementById('viewModalTitle').textContent = 'Invoice ' + no;
    document.getElementById('editLink').href = `/finance/simple-invoice/${id}/edit`;
    document.getElementById('downloadLink').href = `/finance/simple-invoice/${id}/download`;
    
    // Show loading
    content.innerHTML = `
        <div class="flex items-center justify-center h-64">
            <div class="text-center">
                <div class="text-4xl mb-2">⏳</div>
                <div class="text-gray-500">Loading invoice...</div>
            </div>
        </div>
    `;
    
    // Load invoice detail
    fetch(`/finance/simple-invoice/${id}/detail`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = `
                <div class="text-center text-red-500">
                    <div class="text-4xl mb-2">❌</div>
                    <div>Error loading invoice</div>
                </div>
            `;
        });
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

function openPaymentModal(id, no, status, date, notes) {
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentForm').action = `/finance/simple-invoice/${id}/update-payment`;
    document.getElementById('paymentModalTitle').textContent = `Payment - ${no}`;
    document.getElementById('paymentStatus').value = status;
    document.getElementById('paidDate').value = date || new Date().toISOString().split('T')[0];
    document.getElementById('paymentNotes').value = notes || '';
    togglePaidFields();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function togglePaidFields() {
    const status = document.getElementById('paymentStatus').value;
    const show = status === 'paid';
    document.getElementById('paidDateField').style.display = show ? 'block' : 'none';
    document.getElementById('proofField').style.display = show ? 'block' : 'none';
    document.getElementById('paidDate').required = show;
}

function viewProof(url, extension) {
    const modal = document.getElementById('proofModal');
    const imageContainer = document.getElementById('proofImageContainer');
    const pdfContainer = document.getElementById('proofPdfContainer');
    const img = document.getElementById('proofImage');
    const pdf = document.getElementById('proofPdf');
    
    if (extension === 'pdf') {
        imageContainer.classList.add('hidden');
        pdfContainer.classList.remove('hidden');
        pdf.src = url;
    } else {
        pdfContainer.classList.add('hidden');
        imageContainer.classList.remove('hidden');
        img.src = url;
    }
    
    modal.classList.remove('hidden');
}

function closeProofModal() {
    document.getElementById('proofModal').classList.add('hidden');
    document.getElementById('proofImage').src = '';
    document.getElementById('proofPdf').src = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { 
        closeViewModal();
        closePaymentModal(); 
        closeProofModal();
        closePrintPreview();
    }
});

function openPrintPreview(url) {
    document.getElementById('printPreviewIframe').src = url;
    document.getElementById('printPreviewModal').classList.remove('hidden');
}

function closePrintPreview() {
    document.getElementById('printPreviewModal').classList.add('hidden');
    document.getElementById('printPreviewIframe').src = '';
}

function printFromPreview() {
    document.getElementById('printPreviewIframe').contentWindow.print();
}

function openInNewTab() {
    var url = document.getElementById('printPreviewIframe').src;
    window.open(url, '_blank');
}
</script>

{{-- PRINT PREVIEW MODAL --}}
<div id="printPreviewModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60">
    <div class="bg-white w-full max-w-5xl rounded-xl shadow-2xl flex flex-col" style="height: 90vh;">
        <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
            <h3 class="font-bold text-lg text-green-800 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                Preview Simple Invoice
            </h3>
            <div class="flex items-center gap-2">
                <button onclick="printFromPreview()" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg font-bold text-sm flex items-center gap-2 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak
                </button>
                <button onclick="openInNewTab()" class="border border-gray-300 hover:bg-gray-100 text-gray-600 px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition" title="Buka di Tab Baru">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </button>
                <button onclick="closePrintPreview()" class="text-gray-400 hover:text-red-500 text-2xl leading-none px-1">&times;</button>
            </div>
        </div>
        <div class="flex-1 overflow-hidden">
            <iframe id="printPreviewIframe" src="" class="w-full h-full border-0 rounded-b-xl"></iframe>
        </div>
    </div>
</div>
@endsection
