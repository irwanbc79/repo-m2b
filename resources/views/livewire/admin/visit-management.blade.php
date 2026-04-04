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
            <h1 class="text-2xl font-bold text-gray-100">🚗 Kunjungan Karyawan</h1>
            <p class="text-sm text-gray-400 mt-1">Rekap kunjungan pelanggan dari aplikasi mobile</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-5">
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-blue-400">{{ $visits->total() }}</div>
            <div class="text-xs text-gray-400 mt-1">Total Kunjungan</div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-purple-400">{{ $summary['withPhoto'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Ada Foto</div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-emerald-400">{{ $visits->lastPage() > 1 ? '25+' : $visits->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">Halaman Ini</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text"
            placeholder="Cari karyawan / pelanggan / catatan..."
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-red-500">

        <input wire:model.live="filterMonth" type="month"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">

        <select wire:model.live="filterUser"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none w-44">
            <option value="">Semua Karyawan</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>

        <button wire:click="$set('search', '')" class="px-3 py-2 bg-gray-600 hover:bg-gray-500 text-gray-300 rounded-lg text-sm">
            Reset
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
                        <th class="px-4 py-3 text-left">Pelanggan / Klien</th>
                        <th class="px-4 py-3 text-left">Catatan</th>
                        <th class="px-4 py-3 text-center">Lokasi</th>
                        <th class="px-4 py-3 text-center">Foto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($visits as $visit)
                    <tr class="hover:bg-gray-750 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-gray-200 font-medium">{{ $visit->visited_at->format('d/m/Y') }}</div>
                            <div class="text-gray-500 text-xs">{{ $visit->visited_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-200">{{ $visit->user?->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $visit->user?->getPrimaryRole() ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-200">{{ $visit->client_name }}</div>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            @if($visit->notes)
                                @if($previewId === $visit->id)
                                    <div class="text-gray-300 text-xs whitespace-pre-wrap leading-relaxed">{{ $visit->notes }}</div>
                                    <button wire:click="preview({{ $visit->id }})" class="text-blue-400 text-xs mt-1 hover:underline">Tutup</button>
                                @else
                                    <div class="text-gray-400 text-xs truncate max-w-48">{{ $visit->notes }}</div>
                                    <button wire:click="preview({{ $visit->id }})" class="text-blue-400 text-xs mt-1 hover:underline">Lihat selengkapnya</button>
                                @endif
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($visit->latitude && $visit->longitude)
                                <a href="https://maps.google.com/?q={{ $visit->latitude }},{{ $visit->longitude }}"
                                    target="_blank"
                                    class="text-blue-400 hover:text-blue-300 text-xs underline">
                                    📍 Peta
                                </a>
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($visit->photo_path)
                                <a href="{{ asset('storage/' . $visit->photo_path) }}" target="_blank"
                                    class="inline-block">
                                    <img src="{{ asset('storage/' . $visit->photo_path) }}"
                                        class="w-14 h-14 object-cover rounded-lg border border-gray-600 hover:scale-110 transition-transform cursor-pointer"
                                        alt="Foto kunjungan">
                                </a>
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">Tidak ada data kunjungan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($visits->hasPages())
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $visits->links() }}
        </div>
        @endif
    </div>
</div>
