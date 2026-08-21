<div class="space-y-8">
    @section('header', 'Dashboard Overview')
    
    {{-- Header Selamat Datang --}}
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-m2b-primary flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Halo, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-gray-600 mt-1">Selamat datang di M2B Portal. Pantau kiriman logistik Anda secara real-time.</p>
        </div>
        <div class="text-left md:text-right bg-gray-50 px-5 py-3 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Customer Code</p>
            <p class="text-xl font-black text-m2b-primary font-mono tracking-tight">{{ auth()->user()->customer->customer_code ?? '-' }}</p>
        </div>
    </div>

    @foreach(($etaUpdates ?? collect()) as $update)
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 flex flex-col md:flex-row md:items-center gap-4 shadow-sm">
        <div class="text-2xl">🕘</div>
        <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-black text-slate-900">Pembaruan Jadwal Pengiriman</h3>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white text-amber-800">{{ $update->shipment->awb_number }}</span>
            </div>
            <p class="text-sm font-bold text-slate-700 mt-1">ETA {{ $update->previous_eta?->format('d M Y') ?? 'belum diisi' }} → {{ $update->revised_eta->format('d M Y') }} <span class="text-amber-700">({{ $update->change_days > 0 ? '+' : '' }}{{ $update->change_days }} hari)</span></p>
            <p class="text-sm text-slate-600 mt-1">{{ $update->customer_message }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('customer.shipment.show', $update->shipment_id) }}" class="px-3 py-2 text-xs font-bold text-blue-800 border border-blue-200 rounded-lg bg-white">Lihat Detail</a>
            <button wire:click="acknowledgeEtaRevision({{ $update->id }})" class="px-3 py-2 text-xs font-bold text-white bg-blue-900 rounded-lg">Saya Sudah Membaca</button>
        </div>
    </div>
    @endforeach

    {{-- ⭐ Ajakan isi testimoni --}}
    @if(($testimonialCta ?? null) === 'invite')
    <a href="{{ route('customer.testimonial') }}" class="block group">
        <div class="relative overflow-hidden rounded-xl border border-yellow-200 bg-gradient-to-r from-yellow-50 via-white to-white shadow-sm hover:shadow-md transition p-5 flex items-center gap-4">
            <div class="shrink-0 text-3xl">⭐</div>
            <div class="flex-1 min-w-0">
                <h3 class="font-black text-gray-800">Puas dengan layanan M2B? Bagikan pengalaman Anda</h3>
                <p class="text-sm text-gray-500">Testimoni Anda hanya butuh 1 menit — dan bisa tampil di halaman utama M2B.</p>
            </div>
            <span class="shrink-0 inline-flex items-center gap-1.5 bg-m2b-primary group-hover:bg-blue-900 text-white text-sm font-bold px-4 py-2 rounded-lg transition">Tulis Testimoni →</span>
        </div>
    </a>
    @elseif(($testimonialCta ?? null) === 'review')
    <div class="rounded-xl border border-blue-100 bg-blue-50/60 shadow-sm p-4 flex items-center gap-3">
        <span class="text-xl">⏳</span>
        <p class="text-sm text-blue-800">Terima kasih! Testimoni Anda sedang ditinjau tim M2B. <a href="{{ route('customer.testimonial') }}" class="font-bold underline">Lihat status</a></p>
    </div>
    @endif

    {{-- ⚠️ NOTIFIKASI KUAT: Dokumen yang diminta staf M2B (lintas semua shipment) --}}
    @php $totalReq = $docRequests->flatten()->count(); @endphp
    @if($totalReq > 0)
    <div class="relative overflow-hidden rounded-xl border-2 border-red-300 bg-gradient-to-r from-red-50 via-white to-white shadow-md ring-1 ring-red-100">
        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-m2b-accent animate-pulse"></div>
        <div class="p-5 md:p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 relative">
                    <span class="absolute -right-1 -top-1 flex h-5 w-5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex items-center justify-center rounded-full h-5 w-5 bg-m2b-accent text-white text-[10px] font-black">{{ $totalReq }}</span>
                    </span>
                    <div class="p-3 bg-red-100 rounded-xl text-m2b-accent">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-black text-red-800 flex items-center gap-2 flex-wrap">
                        Tim M2B menunggu dokumen dari Anda
                    </h3>
                    <p class="text-sm text-red-700/90 mt-0.5">Ada <span class="font-bold">{{ $totalReq }} dokumen</span> yang diminta agar proses pengiriman Anda dapat berlanjut. Mohon segera dilengkapi. 🙏</p>

                    <div class="mt-4 space-y-3">
                        @foreach($docRequests as $shipmentId => $reqs)
                            @php $sh = $reqs->first()->shipment; @endphp
                            <div class="bg-white rounded-lg border border-red-100 p-3.5">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <span class="font-black text-m2b-primary text-sm font-mono">{{ $sh->awb_number ?? 'Shipment #'.$shipmentId }}</span>
                                    <a href="{{ route('customer.shipment.show', $shipmentId) }}"
                                       class="inline-flex items-center gap-1.5 bg-m2b-accent hover:bg-red-700 text-white text-xs font-bold px-3.5 py-2 rounded-lg transition shadow-sm">
                                        Lengkapi Sekarang
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                                <ul class="mt-2.5 flex flex-wrap gap-2">
                                    @foreach($reqs as $req)
                                        <li class="inline-flex items-center gap-1.5 bg-red-50 border border-red-200 text-red-800 text-xs font-semibold px-2.5 py-1 rounded-full">
                                            <svg class="w-3 h-3 text-m2b-accent" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-6L8 3H4z"/></svg>
                                            {{ $req->doc_type }}
                                            @if($req->due_date)
                                                <span class="text-[10px] font-bold {{ \Carbon\Carbon::parse($req->due_date)->isPast() ? 'text-red-600' : 'text-red-500/80' }}">
                                                    • tenggat {{ \Carbon\Carbon::parse($req->due_date)->format('d M') }}
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @php $notes = $reqs->pluck('note')->filter()->unique(); @endphp
                                @if($notes->isNotEmpty())
                                    <p class="mt-2 text-xs text-gray-500 italic">📝 {{ $notes->implode(' · ') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Statistik Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Total --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 transition hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Shipments</p>
                    <h3 class="text-4xl font-black text-m2b-primary mt-2">{{ $stats['total'] }}</h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl text-m2b-primary">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h8a1 1 0 001-1v-1z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 16H13M21 16v-1a1 1 0 00-1-1h-6v1a1 1 0 001 1h6z"></path></svg>
                </div>
            </div>
        </div>

        {{-- Active --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 transition hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active / In Transit</p>
                    <h3 class="text-4xl font-black text-m2b-accent mt-2">{{ $stats['active'] }}</h3>
                </div>
                <div class="p-3 bg-red-50 rounded-xl text-m2b-accent">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 transition hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Completed</p>
                    <h3 class="text-4xl font-black text-green-600 mt-2">{{ $stats['completed'] }}</h3>
                </div>
                <div class="p-3 bg-green-50 rounded-xl text-green-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Recent Shipments (Full Width) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Recent Activity
            </h3>
            <a href="{{ route('customer.shipments.index') }}" class="text-xs text-m2b-primary hover:text-m2b-accent font-bold uppercase tracking-wide flex items-center gap-1 border border-blue-100 px-3 py-1.5 rounded-lg hover:bg-white transition">
                View All Shipments <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-white text-gray-500 font-bold uppercase text-[10px] tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Reference No</th>
                        <th class="px-6 py-4">Route Info</th>
                        <th class="px-6 py-4">Est. Arrival</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($shipments as $shipment)
                    <tr class="hover:bg-blue-50/50 transition duration-150 group">
                        <td class="px-6 py-4">
                            <span class="font-black text-m2b-primary text-sm block">{{ $shipment->awb_number }}</span>
                            <span class="text-[10px] text-gray-400">{{ $shipment->service_type }} • {{ $shipment->container_mode }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 text-xs font-medium text-gray-700">
                                <span>{{ $shipment->origin }}</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                <span>{{ $shipment->destination }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($shipment->estimated_arrival)
                                <span class="font-mono text-gray-600 text-xs">{{ \Carbon\Carbon::parse($shipment->estimated_arrival)->format('d M Y') }}</span>
                                @if(($latestEta = $shipment->etaRevisions->first()))
                                <span class="block text-[10px] font-bold text-amber-700 mt-1">🕘 {{ $shipment->etaRevisions->count() }}× revisi · {{ $latestEta->change_days > 0 ? '+' : '' }}{{ $latestEta->change_days }} hari</span>
                                @endif
                            @else
                                <span class="text-gray-400 text-xs italic">TBA</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                @if($shipment->status == 'completed') bg-green-100 text-green-700 border-green-200
                                @elseif($shipment->status == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                                @else bg-blue-50 text-blue-700 border-blue-200 @endif">
                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('customer.shipment.show', $shipment->id) }}" class="text-xs font-bold text-gray-400 group-hover:text-m2b-accent hover:underline transition flex items-center justify-end gap-1">
                                Track Details <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm italic">
                            Belum ada data pengiriman terbaru.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
