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
            <h1 class="text-2xl font-bold text-gray-100">📋 Manajemen Cuti & Izin</h1>
            <p class="text-sm text-gray-400 mt-1">Pengajuan cuti, izin, sakit, dan dinas dari karyawan</p>
        </div>
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

        <select wire:model.live="filterType"
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
            <option value="">Semua Jenis</option>
            <option value="sakit">Sakit</option>
            <option value="izin">Izin</option>
            <option value="cuti">Cuti</option>
            <option value="dinas">Dinas</option>
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
                        <th class="px-4 py-3 text-center">Jenis</th>
                        <th class="px-4 py-3 text-left">Periode</th>
                        <th class="px-4 py-3 text-left">Alasan</th>
                        <th class="px-4 py-3 text-center">Dokumen</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($leaves as $leave)
                    <tr class="hover:bg-gray-750">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-200">{{ $leave->user?->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $leave->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $typeColors = ['sakit' => 'bg-red-900 text-red-300', 'izin' => 'bg-yellow-900 text-yellow-300', 'cuti' => 'bg-blue-900 text-blue-300', 'dinas' => 'bg-purple-900 text-purple-300'];
                                $typeIcons  = ['sakit' => '🤒', 'izin' => '🙋', 'cuti' => '🏖️', 'dinas' => '✈️'];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs {{ $typeColors[$leave->type] ?? 'bg-gray-700 text-gray-300' }}">
                                {{ $typeIcons[$leave->type] ?? '' }} {{ strtoupper($leave->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-gray-200">{{ $leave->start_date->format('d/m/Y') }}</div>
                            @if($leave->start_date != $leave->end_date)
                                <div class="text-gray-500 text-xs">s/d {{ $leave->end_date->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} hari)</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <div class="text-gray-300 text-xs line-clamp-2">{{ $leave->reason }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($leave->document_path)
                                <button @click="$dispatch('open-doc-viewer', { url: '{{ asset('storage/' . $leave->document_path) }}', title: 'Surat Izin — {{ $leave->user?->name }}' })"
                                    class="text-blue-400 hover:text-blue-300 text-xs underline cursor-pointer">📎 Lihat</button>
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($leave->status === 'pending')
                                <span class="px-2 py-1 bg-yellow-900 text-yellow-300 rounded-full text-xs">⏳ Pending</span>
                            @elseif($leave->status === 'approved')
                                <span class="px-2 py-1 bg-green-900 text-green-300 rounded-full text-xs">✅ Disetujui</span>
                                <div class="text-xs text-gray-500 mt-1">{{ $leave->approver?->name }}</div>
                            @else
                                <span class="px-2 py-1 bg-red-900 text-red-300 rounded-full text-xs">❌ Ditolak</span>
                                <div class="text-xs text-gray-500 mt-1">{{ $leave->approver?->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($leave->status === 'pending')
                                <div class="flex gap-2 justify-center">
                                    <button wire:click="approve({{ $leave->id }})"
                                        wire:confirm="Setujui pengajuan {{ strtoupper($leave->type) }} dari {{ $leave->user?->name }}?"
                                        class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg transition">
                                        ✅ Setuju
                                    </button>
                                    <button wire:click="reject({{ $leave->id }})"
                                        wire:confirm="Tolak pengajuan {{ strtoupper($leave->type) }} dari {{ $leave->user?->name }}?"
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
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500">Tidak ada pengajuan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leaves->hasPages())
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $leaves->links() }}
        </div>
        @endif
    </div>
</div>
