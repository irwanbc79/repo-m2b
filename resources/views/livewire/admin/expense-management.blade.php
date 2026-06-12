<div>
    {{-- Notifikasi --}}
    <div x-data="{ show: false, message: '', type: 'success' }"
        @notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 3500)"
        x-show="show" x-transition
        :class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed top-4 right-4 z-50 px-5 py-3 rounded-lg text-white text-sm shadow-lg">
        <span x-text="message"></span>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-100">💰 Manajemen Klaim Biaya</h1>
            <p class="text-sm text-gray-400 mt-1">Klaim biaya perjalanan, makan, hotel, dan perlengkapan dari karyawan</p>
        </div>
        @if($totalPending > 0)
        <div class="bg-yellow-900 border border-yellow-700 rounded-xl px-4 py-2 text-right">
            <div class="text-xs text-yellow-400">Total Pending</div>
            <div class="text-lg font-bold text-yellow-300">Rp {{ number_format($totalPending, 0, ',', '.') }}</div>
        </div>
        @endif
    </div>

    {{-- Counter Cards --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <button wire:click="$set('filterStatus', 'pending')"
            class="rounded-xl p-4 text-center transition {{ $filterStatus === 'pending' ? 'bg-yellow-600' : 'bg-gray-800 hover:bg-gray-700' }}">
            <div class="text-2xl font-bold text-white">{{ $counts['pending'] }}</div>
            <div class="text-xs text-gray-300 mt-1">⏳ Menunggu</div>
        </button>
        <button wire:click="$set('filterStatus', 'approved')"
            class="rounded-xl p-4 text-center transition {{ $filterStatus === 'approved' ? 'bg-green-700' : 'bg-gray-800 hover:bg-gray-700' }}">
            <div class="text-2xl font-bold text-white">{{ $counts['approved'] }}</div>
            <div class="text-xs text-gray-300 mt-1">✅ Disetujui</div>
        </button>
        <button wire:click="$set('filterStatus', 'rejected')"
            class="rounded-xl p-4 text-center transition {{ $filterStatus === 'rejected' ? 'bg-red-700' : 'bg-gray-800 hover:bg-gray-700' }}">
            <div class="text-2xl font-bold text-white">{{ $counts['rejected'] }}</div>
            <div class="text-xs text-gray-300 mt-1">❌ Ditolak</div>
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text"
            placeholder="Cari nama karyawan..."
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm w-52 focus:outline-none focus:ring-2 focus:ring-red-500">

        <select wire:model.live="filterCategory"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
            <option value="">Semua Kategori</option>
            <option value="meal">🍽️ Makan</option>
            <option value="travel">🚗 Perjalanan</option>
            <option value="hotel">🏨 Hotel</option>
            <option value="supplies">📦 Perlengkapan</option>
            <option value="other">📋 Lainnya</option>
        </select>

        <button wire:click="$set('filterStatus', '')" class="px-3 py-2 bg-gray-600 hover:bg-gray-500 text-gray-300 rounded-lg text-sm">
            Semua Status
        </button>
    </div>

    {{-- Tabel --}}
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
                <thead>
                    <tr class="bg-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                        <th class="px-4 py-3 text-left">Karyawan</th>
                        <th class="px-4 py-3 text-center">Kategori</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                        <th class="px-4 py-3 text-center">Bukti</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($expenses as $exp)
                    <tr class="hover:bg-gray-750">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-200">{{ $exp->user?->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $exp->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $catIcons = ['meal' => '🍽️', 'travel' => '🚗', 'hotel' => '🏨', 'supplies' => '📦', 'other' => '📋'];
                            @endphp
                            <span class="px-2 py-1 bg-gray-700 rounded-full text-xs">
                                {{ $catIcons[$exp->category] ?? '📋' }} {{ ucfirst($exp->category) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-200 whitespace-nowrap">
                            Rp {{ number_format($exp->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <div class="text-gray-300 text-xs line-clamp-2">{{ $exp->description }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($exp->receipt_path)
                                <button @click="$dispatch('open-doc-viewer', { url: '{{ asset('storage/' . $exp->receipt_path) }}', title: 'Bukti Pengeluaran — {{ $exp->user?->name }}' })"
                                    class="text-blue-400 hover:text-blue-300 text-xs underline cursor-pointer">📎 Lihat</button>
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($exp->status === 'pending')
                                <span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs">⏳ Pending</span>
                            @elseif($exp->status === 'approved')
                                <span class="px-2 py-1 bg-green-900 text-green-300 rounded-full text-xs">✅ Disetujui</span>
                                <div class="text-xs text-gray-500 mt-1">{{ $exp->approver?->name }}</div>
                            @else
                                <span class="px-2 py-1 bg-red-900 text-red-300 rounded-full text-xs">❌ Ditolak</span>
                                <div class="text-xs text-gray-500 mt-1">{{ $exp->approver?->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($exp->status === 'pending')
                                <div class="flex gap-2 justify-center">
                                    <button wire:click="approve({{ $exp->id }})"
                                        wire:confirm="Setujui klaim {{ ucfirst($exp->category) }} dari {{ $exp->user?->name }}?"
                                        class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg transition">
                                        ✅ Setuju
                                    </button>
                                    <button wire:click="reject({{ $exp->id }})"
                                        wire:confirm="Tolak klaim {{ ucfirst($exp->category) }} dari {{ $exp->user?->name }}?"
                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg transition">
                                        ❌ Tolak
                                    </button>
                                </div>
                            @else
                                <span class="text-gray-600 text-xs">Sudah diproses</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">Tidak ada klaim biaya.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>
</div>
