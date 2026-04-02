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
            <h1 class="text-2xl font-bold text-gray-100">📍 Manajemen Absensi</h1>
            <p class="text-sm text-gray-400 mt-1">Rekap absensi karyawan dari aplikasi mobile</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    @if($summary)
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-green-400">{{ $summary['checkin'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Check-in</div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-blue-400">{{ $summary['checkout'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Check-out</div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-emerald-400">{{ $summary['verified'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Terverifikasi</div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-yellow-400">{{ $summary['unverified'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Belum Verif</div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-gray-400">{{ $summary['offline'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Offline Sync</div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text"
            placeholder="Cari nama karyawan..."
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm w-52 focus:outline-none focus:ring-2 focus:ring-red-500">

        <input wire:model.live="filterDate" type="date"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">

        <select wire:model.live="filterType"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
            <option value="">Semua Tipe</option>
            <option value="checkin">Check-in</option>
            <option value="checkout">Check-out</option>
        </select>

        <select wire:model.live="filterUser"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none w-44">
            <option value="">Semua Karyawan</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>

        <button wire:click="$set('filterDate', '')" class="px-3 py-2 bg-gray-600 hover:bg-gray-500 text-gray-300 rounded-lg text-sm">
            Reset Filter
        </button>
    </div>

    {{-- Tabel --}}
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
                <thead>
                    <tr class="bg-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                        <th class="px-4 py-3 text-left">Waktu</th>
                        <th class="px-4 py-3 text-left">Karyawan</th>
                        <th class="px-4 py-3 text-center">Tipe</th>
                        <th class="px-4 py-3 text-left">Lokasi</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Selfie</th>
                        <th class="px-4 py-3 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($records as $rec)
                    <tr class="hover:bg-gray-750">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-gray-200 font-medium">{{ $rec->created_at->format('d/m/Y') }}</div>
                            <div class="text-gray-500 text-xs">{{ $rec->created_at->format('H:i:s') }}</div>
                            @if($rec->is_offline_sync)
                                <span class="text-xs text-orange-400">📵 Offline Sync</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-200">{{ $rec->user?->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $rec->user?->getPrimaryRole() }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rec->type === 'checkin')
                                <span class="px-2 py-1 bg-green-900 text-green-300 rounded-full text-xs font-medium">✅ Check-in</span>
                            @else
                                <span class="px-2 py-1 bg-blue-900 text-blue-300 rounded-full text-xs font-medium">🚪 Check-out</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($rec->location)
                                <div class="text-gray-200">{{ $rec->location->name }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst($rec->location->type) }}</div>
                            @else
                                <div class="text-gray-500 text-xs">
                                    📌 {{ number_format($rec->latitude, 5) }}, {{ number_format($rec->longitude, 5) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rec->verified_at)
                                <span class="px-2 py-1 bg-emerald-900 text-emerald-300 rounded-full text-xs">✔ Terverifikasi</span>
                            @else
                                <span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs">⚠ Belum Verif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rec->selfie_path)
                                <a href="{{ asset('storage/' . $rec->selfie_path) }}" target="_blank"
                                    class="text-blue-400 hover:text-blue-300 text-xs underline">Lihat</a>
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400 max-w-xs truncate">{{ $rec->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">Tidak ada data absensi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $records->links() }}
        </div>
        @endif
    </div>
</div>
