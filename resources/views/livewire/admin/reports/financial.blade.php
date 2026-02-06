<div class="space-y-6">
    {{-- Invoice Status Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p class="text-green-600 text-xs font-bold">✅ PAID</p>
            <p class="text-2xl font-black text-green-700">{{ $invoiceStatus['paid']->count ?? 0 }}</p>
            <p class="text-xs text-green-600">Rp {{ number_format($invoiceStatus['paid']->total ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
            <p class="text-yellow-600 text-xs font-bold">⏳ PARTIAL</p>
            <p class="text-2xl font-black text-yellow-700">{{ $invoiceStatus['partial']->count ?? 0 }}</p>
            <p class="text-xs text-yellow-600">Rp {{ number_format($invoiceStatus['partial']->total ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <p class="text-red-600 text-xs font-bold">❌ UNPAID</p>
            <p class="text-2xl font-black text-red-700">{{ $invoiceStatus['unpaid']->count ?? 0 }}</p>
            <p class="text-xs text-red-600">Rp {{ number_format($invoiceStatus['unpaid']->total ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-gray-600 text-xs font-bold">🚫 CANCELLED</p>
            <p class="text-2xl font-black text-gray-700">{{ $invoiceStatus['cancelled']->count ?? 0 }}</p>
            <p class="text-xs text-gray-600">Rp {{ number_format($invoiceStatus['cancelled']->total ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        {{-- AR Aging --}}
        <div class="bg-white border rounded-xl p-6">
            <h3 class="font-bold text-gray-800 mb-4">📋 Aging Piutang (AR)</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <div>
                        <p class="font-medium text-green-800">Belum Jatuh Tempo</p>
                        <p class="text-xs text-green-600">{{ $arAging['current']->count ?? 0 }} invoice</p>
                    </div>
                    <p class="font-bold text-green-700">Rp {{ number_format($arAging['current']->total ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                    <div>
                        <p class="font-medium text-yellow-800">1-30 Hari Overdue</p>
                        <p class="text-xs text-yellow-600">{{ $arAging['overdue_30']->count ?? 0 }} invoice</p>
                    </div>
                    <p class="font-bold text-yellow-700">Rp {{ number_format($arAging['overdue_30']->total ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                    <div>
                        <p class="font-medium text-orange-800">31-60 Hari Overdue</p>
                        <p class="text-xs text-orange-600">{{ $arAging['overdue_60']->count ?? 0 }} invoice</p>
                    </div>
                    <p class="font-bold text-orange-700">Rp {{ number_format($arAging['overdue_60']->total ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="font-medium text-red-800">>60 Hari Overdue</p>
                        <p class="text-xs text-red-600">{{ $arAging['overdue_90']->count ?? 0 }} invoice</p>
                    </div>
                    <p class="font-bold text-red-700">Rp {{ number_format($arAging['overdue_90']->total ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Top Revenue by Customer --}}
        <div class="bg-white border rounded-xl p-6">
            <h3 class="font-bold text-gray-800 mb-4">🏆 Top 10 Revenue by Customer</h3>
            <div class="space-y-2">
                @forelse($revenueByCustomer as $index => $item)
                <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded">
                    <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold {{ $index < 3 ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-200 text-gray-600' }}">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 truncate">{{ $item->customer->company_name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">{{ $item->invoice_count }} invoice</p>
                    </div>
                    <p class="font-bold text-green-600 text-sm">Rp {{ number_format($item->total, 0, ',', '.') }}</p>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">Tidak ada data</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- AP by Vendor --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="font-bold text-gray-800 mb-4">📑 Hutang ke Vendor (AP Unpaid)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-2">Vendor</th>
                        <th class="text-center py-2">Jumlah Job</th>
                        <th class="text-right py-2">Total Hutang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apByVendor as $item)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3">
                            <p class="font-medium">{{ $item->vendor->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $item->vendor->code ?? '' }}</p>
                        </td>
                        <td class="py-3 text-center">{{ $item->count }}</td>
                        <td class="py-3 text-right font-bold text-red-600">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="py-8 text-center text-gray-500">Tidak ada hutang</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
