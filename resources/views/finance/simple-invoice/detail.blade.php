<div class="space-y-6">
    {{-- Invoice Header Info --}}
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
        <h3 class="text-xl font-bold text-blue-900 mb-2">{{ $invoice->invoice_number }}</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-600">Customer:</span>
                <span class="font-semibold text-gray-900">{{ $invoice->customer_name }}</span>
            </div>
            <div>
                <span class="text-gray-600">Tanggal:</span>
                <span class="font-semibold text-gray-900">{{ $invoice->invoice_date->format('d F Y') }}</span>
            </div>
            <div>
                <span class="text-gray-600">Total:</span>
                <span class="font-semibold text-blue-600">{{ $invoice->formatted_total }}</span>
            </div>
            <div>
                <span class="text-gray-600">Status:</span>
                {!! $invoice->status_badge !!}
            </div>
        </div>
        @if($invoice->customer_address)
        <div class="mt-3 text-sm">
            <span class="text-gray-600">Alamat:</span>
            <p class="text-gray-900 mt-1">{{ $invoice->customer_address }}</p>
        </div>
        @endif
    </div>

    {{-- Items Table --}}
    <div class="bg-white border rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">No</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Keterangan</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700">Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Harga</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                    <td class="px-4 py-3 text-sm font-medium">{{ $item->description }}</td>
                    <td class="px-4 py-3 text-sm text-center">{{ $item->quantity }}</td>
                    <td class="px-4 py-3 text-sm text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-semibold">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="bg-blue-50 font-bold">
                    <td colspan="4" class="px-4 py-3 text-right text-sm">TOTAL:</td>
                    <td class="px-4 py-3 text-right text-blue-600">{{ $invoice->formatted_total }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Terbilang --}}
    <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
        <div class="text-xs text-gray-600 font-semibold mb-1">💬 TERBILANG:</div>
        <div class="text-sm text-blue-900 italic">{{ $invoice->terbilang }}</div>
    </div>

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-4">
        <div class="text-xs text-gray-600 font-semibold mb-1">📝 CATATAN:</div>
        <div class="text-sm text-gray-900">{{ $invoice->notes }}</div>
    </div>
    @endif

    {{-- Bank Info --}}
    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
        <div class="text-xs text-gray-600 font-semibold mb-2">💳 INFORMASI TRANSFER:</div>
        <div class="text-sm space-y-1">
            <div><span class="text-gray-600">Bank:</span> <span class="font-medium">PT BANK MANDIRI (Persero) Tbk</span></div>
            <div><span class="text-gray-600">No. Rekening:</span> <span class="font-bold text-green-700">106-00-5598809-6</span></div>
            <div><span class="text-gray-600">Atas Nama:</span> <span class="font-medium">PT. MORA MULTI BERKAH</span></div>
        </div>
    </div>

    {{-- Payment Info if Paid --}}
    @if($invoice->status === 'paid')
    <div class="bg-green-50 border-2 border-green-400 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-green-600 text-xl">✅</span>
            <div class="text-sm font-semibold text-green-800">Invoice Telah Dibayar</div>
        </div>
        @if($invoice->paid_date)
        <div class="text-sm text-gray-700">Tanggal Bayar: {{ $invoice->paid_date->format('d F Y') }}</div>
        @endif
        @if($invoice->payment_notes)
        <div class="text-sm text-gray-700 mt-1">Catatan: {{ $invoice->payment_notes }}</div>
        @endif
        @if($invoice->payment_proof)
        <button onclick="window.parent.viewProof('{{ asset('storage/' . $invoice->payment_proof) }}', '{{ pathinfo($invoice->payment_proof, PATHINFO_EXTENSION) }}')"
                class="mt-2 text-sm text-blue-600 hover:text-blue-800">
            📎 Lihat Bukti Pembayaran
        </button>
        @endif
    </div>
    @endif
</div>
