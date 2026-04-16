<div class="space-y-5">
    @section('header', 'Penawaran Saya')

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm font-medium">
        ✅ {{ session('success') }}
    </div>
    @endif
    @if(session('info'))
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-xl text-sm font-medium">
        ℹ️ {{ session('info') }}
    </div>
    @endif

    {{-- Filter --}}
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex flex-wrap gap-2">
            @foreach(['' => 'Semua', 'sent' => 'Menunggu', 'accepted' => 'Disetujui', 'rejected' => 'Ditolak', 'expired' => 'Kedaluwarsa'] as $val => $label)
            <button wire:click="$set('filterStatus', '{{ $val }}')"
                class="px-4 py-1.5 rounded-full text-sm font-medium transition
                    {{ $filterStatus === $val
                        ? 'bg-m2b-primary text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- List --}}
    @forelse($quotations as $qt)
    @php
        $isExpired = $qt->valid_until->isPast() && $qt->status !== 'accepted';
        $statusLabel = match(true) {
            $qt->status === 'accepted'  => ['Disetujui',    'bg-green-100 text-green-700'],
            $qt->status === 'rejected'  => ['Ditolak',      'bg-red-100 text-red-700'],
            $isExpired                  => ['Kedaluwarsa',  'bg-gray-100 text-gray-500'],
            $qt->status === 'sent'      => ['Menunggu',     'bg-yellow-100 text-yellow-700'],
            default                     => [ucfirst($qt->status), 'bg-gray-100 text-gray-600'],
        };
    @endphp
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="px-5 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-black text-m2b-primary text-sm">{{ $qt->quotation_number }}</span>
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full {{ $statusLabel[1] }}">
                        {{ $statusLabel[0] }}
                    </span>
                    @if($qt->approval_status === 'pending' && !$isExpired)
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 animate-pulse">
                        Menunggu Keputusan Anda
                    </span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $qt->origin }} → {{ $qt->destination }}
                    <span class="mx-1 text-gray-300">·</span>
                    {{ ucfirst($qt->service_type) }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    Berlaku s/d: <span class="{{ $qt->valid_until->diffInDays(now()) <= 3 && !$isExpired ? 'text-red-500 font-semibold' : '' }}">
                        {{ $qt->valid_until->format('d M Y') }}
                    </span>
                    <span class="mx-1">·</span>
                    {{ $qt->quotation_date->format('d M Y') }}
                </p>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
                <span class="font-black text-lg text-m2b-primary font-mono">
                    Rp {{ number_format($qt->grand_total, 0, ',', '.') }}
                </span>
                <span class="text-xs text-gray-400">{{ $qt->items->count() }} item</span>
            </div>
        </div>

        {{-- Action row --}}
        @if($qt->approval_status === 'pending' && !$isExpired)
        <div class="px-5 py-3 bg-yellow-50 border-t border-yellow-100 flex flex-wrap items-center gap-2">
            <span class="text-xs text-yellow-700 flex-1">Penawaran ini menunggu persetujuan Anda.</span>
            <button wire:click="approve({{ $qt->id }})"
                wire:confirm="Yakin menyetujui penawaran {{ $qt->quotation_number }}?"
                class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition">
                ✅ Setujui
            </button>
            <button wire:click="reject({{ $qt->id }})"
                wire:confirm="Yakin menolak penawaran {{ $qt->quotation_number }}?"
                class="px-4 py-1.5 bg-white border border-red-300 hover:border-red-500 text-red-600 text-xs font-semibold rounded-lg transition">
                Tolak
            </button>
        </div>
        @endif

        @if($qt->status === 'accepted')
        <div class="px-5 py-2.5 bg-green-50 border-t border-green-100">
            <p class="text-xs text-green-700">
                ✅ Disetujui pada {{ $qt->approved_at?->format('d M Y, H:i') }}
                @if($qt->approved_by) oleh {{ $qt->approved_by }}@endif
            </p>
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-400">
        <svg class="mx-auto w-14 h-14 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="font-semibold">Belum ada penawaran</p>
        <p class="text-sm mt-1">Tim M2B akan mengirimkan penawaran ke email Anda.</p>
        <a href="https://wa.me/6281263027818" class="inline-block mt-4 text-sm font-semibold text-green-600 hover:underline">
            💬 Hubungi tim kami
        </a>
    </div>
    @endforelse

    @if($quotations->hasPages())
    <div class="bg-white rounded-xl shadow-sm px-4 py-3">
        {{ $quotations->links() }}
    </div>
    @endif
</div>
