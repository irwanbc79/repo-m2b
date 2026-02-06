<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white">
            <div class="text-sm opacity-80">🏢 TOTAL VENDOR</div>
            <div class="text-3xl font-bold">{{ $totalVendors ?? 0 }}</div>
        </div>
        @php
            $topCategories = collect($byCategory ?? [])->sortDesc()->take(3);
        @endphp
        @foreach($topCategories as $cat => $count)
        <div class="bg-white border rounded-xl p-4">
            <div class="text-sm text-gray-500 uppercase">{{ $cat }}</div>
            <div class="text-3xl font-bold text-gray-800">{{ $count }}</div>
        </div>
        @endforeach
    </div>

    {{-- Vendor Performance Table --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="text-lg font-semibold mb-4">📊 Vendor Performance (Periode Ini)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-3">#</th>
                        <th class="text-left p-3">Vendor</th>
                        <th class="text-left p-3">Kategori</th>
                        <th class="text-center p-3">Score</th>
                        <th class="text-center p-3">Grade</th>
                        <th class="text-center p-3">Jobs</th>
                        <th class="text-right p-3">Total Cost</th>
                        <th class="text-right p-3">Dibayar</th>
                        <th class="text-right p-3">Belum Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendorPerformance ?? [] as $index => $vendor)
                    @php 
                        $v = is_array($vendor) ? (object)$vendor : $vendor;
                        $vendorModel = \App\Models\Vendor::find($v->id);
                        $score = $vendorModel->vendor_score ?? 0;
                        $grade = $vendorModel->vendor_grade ?? 'D';
                        $badge = $vendorModel->grade_badge ?? ['color' => 'gray', 'icon' => '?', 'label' => 'N/A'];
                    @endphp
                    <tr class="border-t hover:bg-gray-50 {{ $index < 3 ? 'bg-amber-50' : '' }}">
                        <td class="p-3">
                            @if($index == 0)
                                <span class="text-xl">🥇</span>
                            @elseif($index == 1)
                                <span class="text-xl">🥈</span>
                            @elseif($index == 2)
                                <span class="text-xl">🥉</span>
                            @else
                                <span class="text-gray-400">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <div class="font-medium">{{ $v->name ?? '-' }}</div>
                            <div class="text-xs text-gray-400">{{ $v->code ?? '-' }}</div>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs 
                                @if(($v->category ?? '') == 'Trucking') bg-blue-100 text-blue-700
                                @elseif(($v->category ?? '') == 'PPJK') bg-purple-100 text-purple-700
                                @elseif(($v->category ?? '') == 'Depo') bg-green-100 text-green-700
                                @elseif(($v->category ?? '') == 'TPS') bg-orange-100 text-orange-700
                                @elseif(($v->category ?? '') == 'Shipping Line') bg-cyan-100 text-cyan-700
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $v->category ?? '-' }}
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            <div class="inline-flex items-center gap-1">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all
                                        @if($score >= 75) bg-green-500
                                        @elseif($score >= 50) bg-yellow-500
                                        @else bg-red-500 @endif"
                                        style="width: {{ min($score, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-medium">{{ number_format($score, 0) }}</span>
                            </div>
                        </td>
                        <td class="p-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold
                                @if($grade == 'A+') bg-yellow-100 text-yellow-800 border border-yellow-300
                                @elseif($grade == 'A') bg-green-100 text-green-800
                                @elseif($grade == 'B') bg-blue-100 text-blue-800
                                @elseif($grade == 'C') bg-orange-100 text-orange-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ $badge['icon'] }} {{ $grade }}
                            </span>
                        </td>
                        <td class="p-3 text-center font-medium">{{ $v->job_count ?? 0 }}</td>
                        <td class="p-3 text-right">Rp {{ number_format($v->total_cost ?? 0, 0, ',', '.') }}</td>
                        <td class="p-3 text-right text-green-600">Rp {{ number_format($v->paid ?? 0, 0, ',', '.') }}</td>
                        <td class="p-3 text-right {{ ($v->unpaid ?? 0) > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                            Rp {{ number_format($v->unpaid ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center p-8 text-gray-400">
                            Tidak ada vendor aktif dalam periode ini
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Grade Legend & Vendor by Category --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Grade Legend --}}
        <div class="bg-white border rounded-xl p-6">
            <h3 class="text-lg font-semibold mb-4">🏆 Vendor Grade Legend</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-2 bg-yellow-50 rounded-lg border border-yellow-200">
                    <span class="text-2xl">🥇</span>
                    <div>
                        <span class="font-bold text-yellow-800">A+ (85-100)</span>
                        <span class="text-sm text-yellow-600 ml-2">Preferred Vendor</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2 bg-green-50 rounded-lg">
                    <span class="text-2xl">🥈</span>
                    <div>
                        <span class="font-bold text-green-800">A (75-84)</span>
                        <span class="text-sm text-green-600 ml-2">Trusted Vendor</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2 bg-blue-50 rounded-lg">
                    <span class="text-2xl">🥉</span>
                    <div>
                        <span class="font-bold text-blue-800">B (65-74)</span>
                        <span class="text-sm text-blue-600 ml-2">Standard Vendor</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2 bg-orange-50 rounded-lg">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <span class="font-bold text-orange-800">C (50-64)</span>
                        <span class="text-sm text-orange-600 ml-2">Monitor Vendor</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2 bg-red-50 rounded-lg">
                    <span class="text-xl">🚫</span>
                    <div>
                        <span class="font-bold text-red-800">D (&lt;50)</span>
                        <span class="text-sm text-red-600 ml-2">Review Vendor</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                <p class="font-medium">📊 Scoring Formula:</p>
                <p>• Volume (50%): Jumlah job yang ditangani</p>
                <p>• Payment (50%): Rasio pembayaran selesai</p>
                <p>• Rating: Ditambahkan setelah staff memberikan rating</p>
            </div>
        </div>

        {{-- Vendor by Category --}}
        <div class="bg-white border rounded-xl p-6">
            <h3 class="text-lg font-semibold mb-4">🏷️ Vendor by Category</h3>
            <div class="grid grid-cols-2 gap-3">
                @forelse($byCategory ?? [] as $category => $count)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <span class="font-medium text-gray-700">{{ $category }}</span>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-bold">{{ $count }}</span>
                </div>
                @empty
                <div class="col-span-2 text-center text-gray-400 py-4">Tidak ada data kategori</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
