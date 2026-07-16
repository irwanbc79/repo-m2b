<div class="space-y-6">
    @section('header', 'Laba per Shipment')

    {{-- SUMMARY TILES --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Pendapatan</p>
            <p class="text-xl font-black text-gray-900 mt-1 tabular-nums">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Biaya (Job Cost)</p>
            <p class="text-xl font-black text-gray-900 mt-1 tabular-nums">Rp {{ number_format($summary['cost'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow border p-5 {{ $summary['profit'] < 0 ? 'border-red-300 bg-red-50' : 'border-emerald-300 bg-emerald-50' }}">
            <p class="text-[11px] font-bold uppercase tracking-wider {{ $summary['profit'] < 0 ? 'text-red-500' : 'text-emerald-600' }}">Laba Kotor</p>
            <p class="text-xl font-black mt-1 tabular-nums {{ $summary['profit'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">Rp {{ number_format($summary['profit'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Margin Rata-rata</p>
            <p class="text-xl font-black text-gray-900 mt-1 tabular-nums">{{ number_format($summary['margin'], 1, ',', '.') }}%</p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ $summary['count'] }} shipment</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 p-4 flex flex-wrap items-center gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari AWB / BL / customer…"
            class="flex-1 min-w-[200px] border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
        <div class="flex flex-wrap gap-1.5">
            @foreach (['all' => 'Semua', 'loss' => 'Rugi', 'thin' => 'Margin < 10%', 'healthy' => 'Sehat ≥ 20%', 'unbilled' => 'Belum Ditagih'] as $key => $label)
                <button wire:click="$set('marginFilter', '{{ $key }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $marginFilter === $key ? 'bg-blue-900 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <select wire:model.live="sort" class="border-gray-200 rounded-lg text-xs font-bold text-gray-600 focus:ring-blue-500 focus:border-blue-500">
            <option value="margin_asc">Margin terkecil dulu</option>
            <option value="margin_desc">Margin terbesar dulu</option>
            <option value="profit_desc">Laba terbesar dulu</option>
            <option value="revenue_desc">Pendapatan terbesar</option>
        </select>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[820px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="text-left font-bold px-4 py-3">Shipment</th>
                        <th class="text-left font-bold px-4 py-3">Customer</th>
                        <th class="text-right font-bold px-4 py-3">Pendapatan</th>
                        <th class="text-right font-bold px-4 py-3">Jasa</th>
                        <th class="text-right font-bold px-4 py-3">Biaya</th>
                        <th class="text-right font-bold px-4 py-3">Laba Kotor</th>
                        <th class="text-right font-bold px-4 py-3">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($shipments as $s)
                        @php
                            $m = $s->margin_pct;
                            $badge = $m === null ? 'bg-gray-100 text-gray-400'
                                : ($m < 0 ? 'bg-red-100 text-red-700'
                                : ($m < 10 ? 'bg-amber-100 text-amber-700'
                                : ($m >= 20 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600')));
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.shipments.show', $s->id) }}" class="font-bold text-blue-700 hover:underline">
                                    {{ $s->bl_number ?: $s->awb_number ?: ('SHP-' . $s->id) }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Str::limit($s->customer->company_name ?? '-', 28) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-700">{{ number_format((float) ($s->revenue ?? 0), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ number_format((float) ($s->service_revenue ?? 0), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-700">{{ number_format((float) ($s->total_cost ?? 0), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-bold {{ $s->gross_profit < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($s->gross_profit, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-block px-2 py-0.5 rounded-md text-xs font-black tabular-nums {{ $badge }}">
                                    {{ $m === null ? '—' : number_format($m, 1, ',', '.') . '%' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 italic">Tidak ada shipment sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-[11px] text-gray-400 leading-relaxed">
        <b>Catatan:</b> "Pendapatan" memuat reimbursement (biaya yang ditagih ulang ke customer) + jasa. Kolom <b>Jasa</b>
        menunjukkan porsi fee riil M2B. <b>Laba Kotor = Pendapatan − Biaya</b> (angka absolut sudah benar). "Belum Ditagih"
        = ada biaya tapi belum ada invoice — potensi laba yang belum tertagih.
    </p>
</div>
