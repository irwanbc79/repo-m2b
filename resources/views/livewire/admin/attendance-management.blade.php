<div x-data="{ showSelfieModal: false, selfieUrl: '', zoomLevel: 1 }">
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
        @if($filterDate && ($summary['unverified'] ?? 0) > 0)
        <button wire:click="verifyAll"
            wire:confirm="Verifikasi semua {{ $summary['unverified'] }} absensi yang belum terverifikasi?"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg flex items-center gap-2">
            ✔ Verifikasi Semua ({{ $summary['unverified'] }})
        </button>
        @endif
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
                                <button wire:click="verify({{ $rec->id }})" title="Klik untuk batalkan verifikasi"
                                    class="px-2 py-1 bg-emerald-900 text-emerald-300 rounded-full text-xs hover:bg-emerald-800 transition-colors">
                                    ✔ Terverifikasi
                                </button>
                                <div class="text-xs text-gray-600 mt-1">{{ $rec->verified_at->format('H:i') }}</div>
                            @else
                                <button wire:click="verify({{ $rec->id }})"
                                    class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs hover:bg-yellow-700 transition-colors">
                                    ⚠ Verifikasi
                                </button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($rec->selfie_path)
                                <button
                                    @click="selfieUrl = '{{ asset('storage/' . $rec->selfie_path) }}'; showSelfieModal = true; zoomLevel = 1"
                                    class="text-blue-400 hover:text-blue-300 text-xs underline">Lihat</button>
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

    {{-- Modal Preview Selfie --}}
    <div
        x-show="showSelfieModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
        @click.self="showSelfieModal = false">

        <div class="relative bg-gray-900 rounded-2xl shadow-2xl flex flex-col items-center p-4 max-w-lg w-full mx-4">
            {{-- Header --}}
            <div class="flex items-center justify-between w-full mb-3">
                <span class="text-white text-sm font-semibold">Preview Selfie</span>
                <button @click="showSelfieModal = false" class="text-gray-400 hover:text-white text-xl leading-none">&times;</button>
            </div>

            {{-- Image --}}
            <div class="overflow-auto w-full flex justify-center" style="max-height:65vh;">
                <img :src="selfieUrl"
                    :style="`transform: scale(${zoomLevel}); transform-origin: center top; transition: transform 0.2s;`"
                    class="rounded-lg max-w-full object-contain"
                    alt="Selfie">
            </div>

            {{-- Zoom & Download Controls --}}
            <div class="flex items-center gap-3 mt-4">
                <button @click="zoomLevel = Math.max(0.5, zoomLevel - 0.25)"
                    class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm font-bold">− Zoom</button>
                <span x-text="`${Math.round(zoomLevel * 100)}%`" class="text-gray-300 text-xs w-12 text-center"></span>
                <button @click="zoomLevel = Math.min(4, zoomLevel + 0.25)"
                    class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm font-bold">+ Zoom</button>
                <a :href="selfieUrl" download
                    class="ml-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold flex items-center gap-1">
                    ⬇ Download
                </a>
            </div>
        </div>
    </div>
</div>
