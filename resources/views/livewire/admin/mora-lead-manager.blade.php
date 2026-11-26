<div class="space-y-6" x-data="{ showModal: @entangle('showCreateModal') }" x-on:open-wa-url.window="window.open($event.detail.url, '_blank')">
    @section('header', 'MORA Leads — Sales CRM')

    {{-- Session Flash Alert --}}
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">✅</span>
                <span class="text-sm font-semibold">{{ session('message') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">⚠️</span>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Premium Stats & Metrics Cards (Clickable Filter) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <button type="button" wire:click="setFilter('new')" class="text-left bg-gradient-to-br from-indigo-500 to-indigo-700 text-white rounded-2xl shadow-md p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg flex flex-col justify-between h-28 {{ $filter === 'new' ? 'ring-2 ring-indigo-300 ring-offset-2' : '' }}">
            <div class="flex justify-between items-start w-full">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-100">Baru (New)</span>
                <span class="text-2xl">📥</span>
            </div>
            <div>
                <div class="text-3xl font-black">{{ $totalNew }}</div>
                <div class="text-[10px] text-indigo-100 font-semibold mt-0.5">Klik untuk filter lead baru</div>
            </div>
        </button>
        <button type="button" wire:click="setFilter('hot')" class="text-left bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-2xl shadow-md p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg flex flex-col justify-between h-28 {{ $filter === 'hot' ? 'ring-2 ring-rose-300 ring-offset-2' : '' }}">
            <div class="flex justify-between items-start w-full">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-100">Hot Leads</span>
                <span class="text-2xl">🔥</span>
            </div>
            <div>
                <div class="text-3xl font-black">{{ $totalHot }}</div>
                <div class="text-[10px] text-rose-100 font-semibold mt-0.5">Klik untuk filter prospek panas</div>
            </div>
        </button>
        <button type="button" wire:click="setFilter('followup')" class="text-left bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl shadow-md p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg flex flex-col justify-between h-28 {{ $filter === 'followup' ? 'ring-2 ring-amber-300 ring-offset-2' : '' }}">
            <div class="flex justify-between items-start w-full">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-100">Follow-Up Pending</span>
                <span class="text-2xl">⏰</span>
            </div>
            <div>
                <div class="text-3xl font-black">{{ $totalFollowUp }}</div>
                <div class="text-[10px] text-amber-100 font-semibold mt-0.5">Klik untuk filter jadwal aktif</div>
            </div>
        </button>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-2xl shadow-md p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg flex flex-col justify-between h-28">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-100">Pipeline Value</span>
                <span class="text-2xl">💰</span>
            </div>
            <div>
                <div class="text-2xl font-black">Rp {{ number_format($potentialPipeline, 0, ',', '.') }}</div>
                <div class="text-[10px] text-emerald-100 font-semibold mt-0.5">Potensi nominal transaksi aktif</div>
            </div>
        </div>
    </div>

    {{-- Filter & Actions Bar --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col gap-4">
        <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            {{-- Tabs Filter --}}
            <div class="flex gap-1.5 overflow-x-auto pb-1.5 md:pb-0 scrollbar-none flex-nowrap shrink-0">
                @foreach([
                    'all' => 'Semua', 
                    'unread' => '📩 Belum Dibaca', 
                    'new' => 'Baru',
                    'contacted' => '📞 Hubungi',
                    'qualified' => 'Qualified',
                    'negotiating' => 'Negosiasi',
                    'won' => 'Won Deal',
                    'lost' => 'Lost'
                ] as $key => $label)
                    <button wire:click="setFilter('{{ $key }}')"
                        class="px-3 py-1.5 text-xs font-extrabold rounded-lg whitespace-nowrap transition-all duration-150 {{ $filter === $key ? 'bg-indigo-950 text-white shadow-sm' : 'bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Search & Controls --}}
            <div class="flex flex-wrap gap-2 items-center justify-end w-full md:w-auto">
                <div class="relative w-full sm:w-52">
                    <input wire:model.live.debounce.300ms="search"
                        type="text" placeholder="Cari nama, perusahaan..."
                        class="text-xs border border-gray-200 rounded-lg pl-8 pr-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <span class="absolute left-2.5 top-2.5 text-gray-400">🔍</span>
                </div>

                <select wire:model.live="filterPic" class="text-xs border border-gray-200 rounded-lg px-2.5 py-2 bg-white text-gray-700 font-semibold">
                    <option value="">👤 Semua PIC</option>
                    <option value="unassigned">⚠️ Belum Ada PIC ({{ $unassignedCount }})</option>
                    <option value="mine">🙋 Lead Saya</option>
                    @foreach($salesReps as $rep)
                        <option value="{{ $rep->id }}">{{ $rep->name }}</option>
                    @endforeach
                </select>

                @if($waDevices->isNotEmpty())
                <select wire:model.live="filterDevice" class="text-xs border border-gray-200 rounded-lg px-2.5 py-2 bg-white text-gray-700 font-semibold">
                    <option value="">📱 Semua Nomor WA</option>
                    @foreach($waDevices as $dev)
                        <option value="{{ $dev->id }}">{{ $dev->label }}</option>
                    @endforeach
                </select>
                @endif

                @if($this->canManageDevices())
                <button wire:click="openDevicesModal"
                    class="text-xs px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold rounded-lg whitespace-nowrap transition flex items-center gap-1">
                    📱 Perangkat WA ({{ $waDevices->count() }})
                </button>
                @endif

                <button wire:click="exportCsv"
                    class="text-xs px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg whitespace-nowrap transition flex items-center gap-1.5">
                    📥 Export CSV
                </button>

                <button wire:click="openCreateModal"
                    class="text-xs px-3 py-2 bg-indigo-950 hover:bg-indigo-900 text-white font-bold rounded-lg whitespace-nowrap transition flex items-center gap-1">
                    ➕ Tambah Lead
                </button>
            </div>
        </div>
    </div>

    {{-- Main Split-Screen CRM Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Left: Lead List --}}
        <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[650px]">
            <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center gap-2 shrink-0 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Daftar Leads</span>
                    @if($waCloudConfigured)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">🟢 WA Cloud API 2-Arah</span>
                    @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200" title="Isi WHATSAPP_CLOUD_ENABLED=true & token Meta di .env untuk sync 2-arah otomatis">🟡 Siap Sambung Meta WA API</span>
                    @endif
                </div>
                @if($totalUnread > 0)
                    <button wire:click="markAllRead" wire:confirm="Tandai semua lead sebagai sudah dibaca?"
                        class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold transition">
                        ✓ Baca Semua
                    </button>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @if($leads->isEmpty())
                    <div class="py-24 text-center text-gray-400">
                        <div class="text-5xl mb-3">🤖</div>
                        <p class="font-bold text-gray-500">Tidak ada lead ditemukan</p>
                        <p class="text-xs mt-1">Ganti filter atau cari kata kunci lain</p>
                    </div>
                @else
                    @foreach($leads as $lead)
                        <div wire:click="selectLead({{ $lead->id }})"
                            class="p-4 hover:bg-slate-50 cursor-pointer transition duration-150 flex flex-col gap-2 relative {{ $selectedLeadId === $lead->id ? 'bg-indigo-50/40 border-l-4 border-indigo-600' : '' }} {{ $lead->isUnread() ? 'bg-blue-50/20' : '' }}">
                            
                            {{-- Row 1: Badges & Time --}}
                            <div class="flex items-center justify-between">
                                <div class="flex gap-1.5 items-center">
                                    {{-- Score Badge --}}
                                    @if($lead->score === 'hot')
                                        <span class="text-xs bg-red-100 text-red-700 font-extrabold px-1.5 py-0.5 rounded">🔥 HOT</span>
                                    @elseif($lead->score === 'warm')
                                        <span class="text-xs bg-amber-100 text-amber-700 font-extrabold px-1.5 py-0.5 rounded">⚡ WARM</span>
                                    @else
                                        <span class="text-xs bg-slate-100 text-slate-500 font-bold px-1.5 py-0.5 rounded">COLD</span>
                                    @endif

                                    {{-- Service Badge --}}
                                    @if($lead->serviceLabel())
                                        <span class="text-[10px] bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded-full">{{ $lead->serviceLabel() }}</span>
                                    @endif

                                    {{-- Source Badge --}}
                                    @if($lead->source === 'whatsapp_direct')
                                        <span class="text-[10px] bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-full border border-emerald-200">💬 WA Masuk</span>
                                    @elseif($lead->source === 'mora_chat')
                                        <span class="text-[10px] bg-cyan-50 text-cyan-600 font-bold px-2 py-0.5 rounded-full">🤖 MORA Chat</span>
                                    @elseif($lead->source === 'manual')
                                        <span class="text-[10px] bg-purple-50 text-purple-600 font-bold px-2 py-0.5 rounded-full">👤 Manual</span>
                                    @elseif($lead->source === 'cs_form_whatsapp')
                                        <span class="text-[10px] bg-green-50 text-green-600 font-bold px-2 py-0.5 rounded-full">🟢 CS WhatsApp</span>
                                    @elseif($lead->source === 'cs_form_telegram')
                                        <span class="text-[10px] bg-sky-50 text-sky-600 font-bold px-2 py-0.5 rounded-full">🔵 CS Telegram</span>
                                    @elseif($lead->source === 'portal_signup')
                                        <span class="text-[10px] bg-indigo-50 text-indigo-600 font-bold px-2 py-0.5 rounded-full">🌐 Portal Signup</span>
                                    @else
                                        <span class="text-[10px] bg-slate-50 text-slate-600 font-bold px-2 py-0.5 rounded-full">📋 Form</span>
                                    @endif

                                    {{-- Unread dot --}}
                                    @if($lead->isUnread())
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block" title="Unread Lead"></span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-gray-400 font-medium">{{ $lead->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- Row 2: Client Info & Deal Value --}}
                            <div class="flex justify-between items-start gap-4">
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm leading-tight">{{ $lead->name }}</h4>
                                    @if($lead->company)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $lead->company }}</p>
                                    @endif
                                </div>
                                @if($lead->deal_value)
                                    <span class="text-xs font-black text-slate-800 whitespace-nowrap bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-lg border border-emerald-100">
                                        Rp {{ number_format($lead->deal_value, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Row 3: Summary Snippet --}}
                            @if($lead->summary)
                                <p class="text-xs text-gray-500 leading-snug line-clamp-1 mt-0.5">{{ $lead->summary }}</p>
                            @endif

                            {{-- Row 4: Status Indicator, PIC, Device, & SLA --}}
                            <div class="flex flex-wrap justify-between items-center gap-2 mt-1 text-[10px] text-gray-400">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span>📞 {{ $lead->phone }}</span>
                                    @if($lead->assignee)
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold border border-indigo-100">👤 {{ $lead->assignee->name }}</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-bold border border-amber-200">⚠️ Belum Ada PIC</span>
                                    @endif
                                    @if($lead->waDevice)
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-100">📱 {{ $lead->waDevice->label }}</span>
                                    @endif
                                    @if(($lead->wa_messages_count ?? 0) > 0)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-bold">💬 {{ $lead->wa_messages_count }}</span>
                                    @endif
                                    @if($lead->isSlaOverdue())
                                        <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 font-extrabold border border-rose-200">🔴 &gt;24j belum direspons</span>
                                    @endif
                                </div>
                                
                                {{-- Stage Badge --}}
                                @php
                                    $stageColors = [
                                        'new' => 'bg-indigo-100 text-indigo-800',
                                        'contacted' => 'bg-amber-100 text-amber-800',
                                        'qualified' => 'bg-emerald-100 text-emerald-800',
                                        'negotiating' => 'bg-purple-100 text-purple-800',
                                        'won' => 'bg-green-100 text-green-800 border border-green-200',
                                        'lost' => 'bg-rose-100 text-rose-800',
                                    ];
                                    $stageColor = $stageColors[$lead->status] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                <span class="font-extrabold px-2 py-0.5 rounded-full {{ $stageColor }}">
                                    {{ $lead->stageLabel() }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="p-3 bg-gray-50 border-t border-gray-100 shrink-0">
                {{ $leads->links() }}
            </div>
        </div>

        {{-- Right: CRM Lead Details / Executive Monitor --}}
        <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[650px]">
            @if(!$selectedLead)
                <div class="flex-1 overflow-y-auto p-5 space-y-5">
                    <div class="bg-slate-900 text-white rounded-xl p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-emerald-300">📡 Executive Monitor — MORA Leads &amp; WA</p>
                        <h3 class="font-black text-base mt-1">Ringkasan Antrean &amp; Perangkat WA</h3>
                        <p class="text-[11px] text-slate-300 mt-0.5">Klik salah satu lead di kiri untuk membuka percakapan &amp; pipeline CRM.</p>

                        <div class="grid grid-cols-2 gap-2.5 mt-3">
                            <button type="button" wire:click="$set('filterPic', 'unassigned')" class="text-left bg-white/10 hover:bg-white/15 rounded-lg p-2.5 transition">
                                <span class="text-[10px] text-amber-300 font-bold uppercase block">Belum Ada PIC</span>
                                <span class="text-xl font-black text-white">{{ $unassignedCount }}</span>
                            </button>
                            <button type="button" wire:click="setFilter('new')" class="text-left bg-white/10 hover:bg-white/15 rounded-lg p-2.5 transition">
                                <span class="text-[10px] text-rose-300 font-bold uppercase block">&gt;24 Jam Belum Respon</span>
                                <span class="text-xl font-black text-white">{{ $overdueCount }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Monitor Perangkat WA Coexistence (Batas Aktif 13 Hari) --}}
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-xs font-black text-slate-800">📱 Status Perangkat WA (Coexistence)</p>
                                <p class="text-[10px] text-slate-500">Pantau aturan aktif 13 hari Meta (1 Kantor + 7 Marketing)</p>
                            </div>
                            @if($this->canManageDevices())
                                <button type="button" wire:click="openDevicesModal" class="text-[11px] font-bold bg-indigo-950 hover:bg-indigo-900 text-white px-2.5 py-1.5 rounded-lg transition shrink-0">
                                    ⚙️ Kelola
                                </button>
                            @endif
                        </div>

                        <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                            @forelse($waDevices as $dev)
                                @php $health = $dev->coexistenceHealth(); @endphp
                                <div class="px-4 py-2.5 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-bold text-slate-800 truncate">{{ $dev->label }}</span>
                                            @if($dev->is_primary)
                                                <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">UTAMA</span>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-slate-400 font-mono truncate">+{{ $dev->phone_number }} {{ $dev->user ? '· ' . $dev->user->name : '· Kantor' }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        @if(!$dev->is_active)
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Nonaktif</span>
                                        @elseif($health === 'healthy')
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">🟢 Aktif ({{ $dev->daysSinceLastSync() }}h lalu)</span>
                                        @elseif($health === 'warning')
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">🟡 {{ $dev->daysSinceLastSync() }}h — Buka WA HP!</span>
                                        @elseif($health === 'critical')
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">🔴 ≥13h — Cek Koneksi</span>
                                        @else
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700">⏳ Menunggu Sync</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="p-5 text-center space-y-2">
                                    <p class="text-xs text-slate-500">Belum ada nomor WA terdaftar di Portal M2B.</p>
                                    <p class="text-[11px] text-slate-400">Setelah verifikasi Meta Business selesai, daftarkan 1 WA Utama + 7 WA Marketing di sini agar semua chat &amp; echo otomatis masuk.</p>
                                    @if($this->canManageDevices())
                                        <button type="button" wire:click="openDevicesModal" class="mt-1 inline-flex items-center gap-1 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition">
                                            ➕ Daftarkan Nomor WA
                                        </button>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                {{-- Detail Header --}}
                <div class="p-4 bg-indigo-950 text-white flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-black text-sm">{{ $selectedLead->name }}</h3>
                        <p class="text-[10px] text-indigo-200">{{ $selectedLead->company ?? 'No Company' }}</p>
                    </div>
                    <button wire:click="closeDetail" class="text-indigo-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Detail Body (Scrollable) --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-5">
                    
                    {{-- Contact Info Panel --}}
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 flex flex-col gap-2">
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[9px] uppercase font-bold">Telepon</span>
                                <span class="font-bold text-gray-800">{{ $selectedLead->phone }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[9px] uppercase font-bold">Email</span>
                                <span class="font-bold text-gray-800 truncate block">{{ $selectedLead->email ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[9px] uppercase font-bold">Layanan Pilihan</span>
                                <span class="font-bold text-gray-800 block">{{ $selectedLead->serviceLabel() ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[9px] uppercase font-bold">Sumber Lead</span>
                                <span class="font-bold text-gray-800 block capitalize">{{ $selectedLead->source === 'mora_chat' ? 'Mora Chat' : ($selectedLead->source === 'manual' ? 'Manual' : ($selectedLead->source === 'cs_form_whatsapp' ? 'CS WhatsApp' : ($selectedLead->source === 'cs_form_telegram' ? 'CS Telegram' : ($selectedLead->source === 'portal_signup' ? 'Portal Signup' : 'CS Form')))) }}</span>
                            </div>
                            @if($selectedLead->product_links)
                            <div class="col-span-2 border-t border-gray-200/50 pt-2 mt-1">
                                <span class="text-gray-400 block text-[9px] uppercase font-bold">Link Produk Supplier</span>
                                <div class="space-y-1 mt-1">
                                    @foreach(explode(', ', $selectedLead->product_links) as $index => $url)
                                        <a href="{{ $url }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 hover:underline font-semibold flex items-center gap-1 text-[11px] break-all leading-normal">
                                            🔗 Link #{{ $index + 1 }}: {{ $url }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-gray-200/60">
                            @if(!$selectedLead->assigned_to)
                                <button type="button" wire:click="claimLead({{ $selectedLead->id }})"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-bold py-2 px-3 rounded-lg transition shadow-sm">
                                    🙋 Ambil Lead (Claim)
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-800 border border-indigo-200">
                                    👤 PIC: {{ $selectedLead->assignee?->name ?? 'Staf' }}
                                    @if($sla = $selectedLead->firstResponseSlaLabel())
                                        <span class="ml-1 text-[10px] text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">⚡ {{ $sla }}</span>
                                    @endif
                                </span>
                            @endif
                            @if($selectedLead->hasValidPhone())
                            <a href="{{ $whatsappUrl }}" target="_blank"
                                class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold py-2 px-3 rounded-lg transition shadow-sm">
                                💬 Buka WA
                            </a>
                            @endif
                            @if($selectedLead->email)
                                <a href="mailto:{{ $selectedLead->email }}"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2 px-3 rounded-lg transition border border-slate-200">
                                    ✉️ Email
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($selectedLead->hasValidPhone())
                    {{-- SHARED WHATSAPP LIVE INBOX (2-Arah Meta Cloud API & Coexistence) --}}
                    <div wire:poll.15s class="bg-slate-50 rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-3.5 py-2.5 bg-slate-900 text-white flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">💬</span>
                                <div>
                                    <p class="text-xs font-black tracking-wide">Shared WhatsApp Inbox</p>
                                    <p class="text-[10px] text-slate-300">
                                        {{ $waCloudConfigured ? 'Live 2-arah via nomor resmi M2B (Coexistence)' : 'Mode log portal + kirim via WA HP' }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/10 text-emerald-300">
                                {{ $selectedLead->waMessages->count() }} pesan
                            </span>
                        </div>

                        {{-- Chat Thread --}}
                        <div class="p-3 space-y-2.5 max-h-60 overflow-y-auto bg-[#efeae2]/60">
                            @forelse($selectedLead->waMessages as $msg)
                                <div class="flex {{ $msg->isInbound() ? 'justify-start' : 'justify-end' }}">
                                    <div class="max-w-[85%] rounded-xl px-3 py-2 text-xs shadow-sm {{ $msg->isInbound() ? 'bg-white text-slate-800 border border-slate-200' : 'bg-emerald-700 text-white' }}">
                                        <div class="flex items-center justify-between gap-2 mb-0.5">
                                            <span class="text-[10px] font-bold {{ $msg->isInbound() ? 'text-indigo-700' : 'text-emerald-200' }}">
                                                {{ $msg->isInbound() ? ($msg->sender_name ?: $selectedLead->name) : ($msg->sender?->name ?: $msg->sender_name ?: 'Tim M2B') }}
                                            </span>
                                            @if($msg->isFromBusinessAppEcho())
                                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-white/20 text-white">📱 HP WA M2B</span>
                                            @elseif($msg->origin === \App\Models\WaMessage::ORIGIN_PORTAL_MANUAL)
                                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-white/20 text-emerald-100">Log Portal</span>
                                            @endif
                                        </div>
                                        <p class="whitespace-pre-line leading-relaxed">{{ $msg->body }}</p>
                                        <div class="flex items-center justify-end gap-1 mt-1 text-[9px] {{ $msg->isInbound() ? 'text-slate-400' : 'text-emerald-200' }}">
                                            <span>{{ ($msg->wa_timestamp ?? $msg->created_at)?->format('d M H:i') }}</span>
                                            @if(!$msg->isInbound())
                                                <span>
                                                    @if($msg->status === 'read') ✓✓
                                                    @elseif($msg->status === 'delivered') ✓✓
                                                    @elseif($msg->status === 'failed') ⚠️
                                                    @else ✓ @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-[11px] text-slate-500 py-5 italic">
                                    Belum ada riwayat percakapan WhatsApp tercatat. Pilih template atau tulis balasan di bawah.
                                </p>
                            @endforelse
                        </div>

                        {{-- Reply Composer --}}
                        <form wire:submit.prevent="sendWaReply" class="p-2.5 bg-white border-t border-slate-200 space-y-2">
                            <div class="flex gap-1.5">
                                <select wire:model.live="selectedTemplateKey"
                                    class="text-[11px] border border-slate-200 rounded-lg px-2 py-1.5 flex-1 focus:outline-none bg-slate-50">
                                    <option value="">⚡ Isi cepat dari Template...</option>
                                    <option value="intro">👋 Perkenalan Tim M2B</option>
                                    @if($selectedLead->source === 'portal_signup')
                                        <option value="portal">🌐 Sambutan Daftar Portal</option>
                                    @endif
                                    <option value="service">📦 Tanya Detail Layanan</option>
                                    <option value="followup">⏰ Follow-up Penawaran</option>
                                    <option value="deal">🎉 Konfirmasi Deal</option>
                                </select>
                            </div>
                            <div class="flex gap-2 items-end">
                                <textarea wire:model="waReplyText" rows="2" placeholder="Tulis balasan WhatsApp ke {{ $selectedLead->name }}..."
                                    class="text-xs border border-slate-200 rounded-lg p-2 flex-1 focus:outline-none focus:ring-2 focus:ring-emerald-400 resize-none"></textarea>
                                <button type="submit" wire:loading.attr="disabled"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2.5 rounded-lg transition shrink-0 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="sendWaReply">{{ $waCloudConfigured ? 'Kirim 🚀' : 'Log & WA 🚀' }}</span>
                                    <span wire:loading wire:target="sendWaReply">...</span>
                                </button>
                            </div>
                            @error('waReplyText') <p class="text-[10px] text-rose-600 font-semibold">{{ $message }}</p> @enderror
                        </form>
                    </div>
                    @endif

                    {{-- CRM Status & Pipeline Controller --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide block">CRM Stage Pipeline</label>
                        <div class="grid grid-cols-3 gap-1">
                            @foreach(\App\Models\MoraLeadNotification::STAGES as $key => $label)
                                <button wire:click="$set('leadStatus', '{{ $key }}')"
                                    class="text-[10px] font-extrabold py-1.5 rounded-lg border transition-all duration-150 {{ $leadStatus === $key ? 'bg-indigo-950 text-white border-indigo-950 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Assignment & Financials & Follow-up Form --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide block mb-1">Assign Sales Rep</label>
                            <select wire:model="leadAssignedTo"
                                class="text-xs border border-gray-200 rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="">-- Pilih Sales --</option>
                                @foreach($salesReps as $rep)
                                    <option value="{{ $rep->id }}">{{ $rep->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide block mb-1">Jadwal Follow-Up</label>
                            <input wire:model="leadFollowUpAt" type="datetime-local"
                                class="text-xs border border-gray-200 rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div class="col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide block mb-1">Potential Deal Value (IDR)</label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-2 text-xs font-bold text-gray-400">Rp</span>
                                <input wire:model="leadDealValue" type="number" placeholder="Nominal deal"
                                    class="text-xs border border-gray-200 rounded-lg pl-8 pr-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            </div>
                        </div>
                    </div>

                    @if($selectedLead->hasValidPhone())
                    {{-- WhatsApp Assistant Replies (lead punya nomor HP) --}}
                    <div class="bg-indigo-50/50 rounded-xl p-3 border border-indigo-100 space-y-2">
                        <label class="text-[10px] font-black text-indigo-950 uppercase tracking-wide block">WhatsApp Quick Reply Assistant</label>
                        <div class="flex gap-2">
                            <select wire:model.live="selectedTemplateKey"
                                class="text-xs border border-indigo-200 rounded-lg p-2 flex-1 focus:outline-none bg-white">
                                <option value="">-- Pilih Template Pesan --</option>
                                <option value="intro">👋 Perkenalan</option>
                                @if($selectedLead->source === 'portal_signup')
                                <option value="portal">🌐 Sambutan Daftar Portal</option>
                                @endif
                                <option value="service">📦 Layanan Tertarik</option>
                                <option value="followup">⏰ Follow-up Penawaran</option>
                                <option value="deal">🎉 Konfirmasi Deal</option>
                            </select>
                            @if($selectedTemplateKey)
                                <a href="{{ $whatsappUrl }}" target="_blank"
                                    class="bg-indigo-950 hover:bg-indigo-900 text-white text-xs font-bold px-3 py-2 rounded-lg transition flex items-center shrink-0">
                                    Kirim 🚀
                                </a>
                            @endif
                        </div>
                    </div>
                    @else
                    {{-- Tidak ada nomor HP (mis. signup portal via Google) -> follow-up via Email --}}
                    <div class="bg-amber-50 rounded-xl p-3 border border-amber-200 space-y-2">
                        <label class="text-[10px] font-black text-amber-800 uppercase tracking-wide block">Follow-up via Email</label>
                        <p class="text-[11px] text-amber-700 leading-relaxed">Lead ini belum punya nomor WhatsApp. Kirim email onboarding untuk meminta rencana &amp; nomor WA aktif.</p>
                        @if($selectedLead->email)
                        <button wire:click="sendFollowupEmail"
                            wire:confirm="Kirim email follow-up ke {{ $selectedLead->email }}?"
                            wire:loading.attr="disabled"
                            class="w-full bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3 py-2 rounded-lg transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="sendFollowupEmail">✉️ Kirim Email Follow-up ke {{ $selectedLead->email }}</span>
                            <span wire:loading wire:target="sendFollowupEmail">Mengirim…</span>
                        </button>
                        @else
                        <p class="text-[11px] text-red-600 font-semibold">Lead ini juga tidak punya email — perlu data kontak manual.</p>
                        @endif
                    </div>
                    @endif

                    {{-- Sales interaction Notes (Timeline) --}}
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide block">Jurnal Interaksi Sales</label>
                        
                        {{-- Notes list --}}
                        <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                            @if(empty($selectedLead->sales_notes))
                                <p class="text-xs text-gray-400 italic">Belum ada catatan interaksi.</p>
                            @else
                                @foreach(array_reverse($selectedLead->sales_notes) as $note)
                                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-2.5 space-y-1">
                                        <div class="flex justify-between items-center text-[9px] text-gray-400 font-bold uppercase">
                                            <span>👤 {{ $note['user'] }}</span>
                                            <span>⏱ {{ \Carbon\Carbon::parse($note['date'])->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-gray-700 font-medium leading-relaxed">{{ $note['text'] }}</p>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        {{-- Add note field --}}
                        <div class="space-y-2 pt-2 border-t border-gray-100">
                            <textarea wire:model="newNoteText" placeholder="Ketik catatan aktivitas/interaction log baru..." rows="2"
                                class="text-xs border border-gray-200 rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-indigo-300"></textarea>
                            <button wire:click="saveLeadUpdate"
                                class="w-full bg-indigo-950 hover:bg-indigo-900 text-white text-xs font-bold py-2 rounded-lg transition shadow-sm">
                                Simpan Catatan & Update Lead 💾
                            </button>
                        </div>
                    </div>

                    {{-- MORA Chat Transcript (if source is chatbot) --}}
                    @if($selectedLead->source === 'mora_chat' && !empty($selectedLead->chat_history))
                        <div class="space-y-2 pt-2 border-t border-gray-100">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide block">Transkrip Percakapan MORA Chat</label>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 space-y-2 max-h-[300px] overflow-y-auto">
                                @foreach($selectedLead->chat_history as $msg)
                                    @if(($msg['role'] ?? '') === 'user')
                                        <div class="flex justify-end">
                                            <div class="max-w-[80%]">
                                                <span class="text-[8px] text-right text-gray-400 block font-semibold mb-0.5">Pengunjung</span>
                                                <div class="bg-indigo-600 text-white text-xs px-3.5 py-2.5 rounded-2xl rounded-tr-sm leading-relaxed shadow-sm">
                                                    <div class="mora-chat-markdown text-white">
                                                        {!! Illuminate\Support\Str::markdown(e($msg['content'] ?? '')) !!}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex justify-start">
                                            <div class="max-w-[80%]">
                                                <span class="text-[8px] text-gray-400 block font-semibold mb-0.5">🤖 MORA AI</span>
                                                <div class="bg-white border border-gray-200 text-gray-700 text-xs px-3.5 py-2.5 rounded-2xl rounded-tl-sm leading-relaxed shadow-sm">
                                                    <div class="mora-chat-markdown text-gray-700">
                                                        {!! Illuminate\Support\Str::markdown(e($msg['content'] ?? '')) !!}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            @endif
        </div>

    </div>

    {{-- Manual Lead Creation Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none" style="display: none;">
        {{-- Backdrop --}}
        <div @click="showModal = false" class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity"></div>

        {{-- Dialog --}}
        <div class="relative w-full max-w-lg mx-auto my-6 z-50 px-4">
            <div class="border-0 rounded-2xl shadow-2xl relative flex flex-col w-full bg-white outline-none focus:outline-none overflow-hidden animate-scale-up">
                
                {{-- Header --}}
                <div class="flex items-center justify-between p-4 bg-indigo-950 text-white">
                    <h3 class="text-sm font-black">➕ Input Lead Baru Secara Manual</h3>
                    <button @click="showModal = false" class="text-indigo-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="createManualLead" class="p-4 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-gray-600 font-bold mb-1">Nama Kontak *</label>
                            <input wire:model="newLeadName" type="text" required placeholder="Nama lengkap prospek"
                                class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-gray-600 font-bold mb-1">Nomor Telepon *</label>
                            <input wire:model="newLeadPhone" type="text" required placeholder="08xxxxxxxx"
                                class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-gray-600 font-bold mb-1">Email</label>
                            <input wire:model="newLeadEmail" type="email" placeholder="client@email.com"
                                class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-gray-600 font-bold mb-1">Nama Perusahaan / Institusi</label>
                            <input wire:model="newLeadCompany" type="text" placeholder="Nama PT / CV"
                                class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-gray-600 font-bold mb-1">Layanan Diminati</label>
                            <select wire:model="newLeadService"
                                class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                @foreach(\App\Models\MoraLeadNotification::SERVICES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-600 font-bold mb-1">Score Lead</label>
                            <select wire:model="newLeadScore"
                                class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="cold">❄️ Cold</option>
                                <option value="warm">⚡ Warm</option>
                                <option value="hot">🔥 Hot</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-gray-600 font-bold mb-1">Potential Deal Value (IDR)</label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-2 font-bold text-gray-400">Rp</span>
                                <input wire:model="newLeadDealValue" type="number" placeholder="Nominal deal"
                                    class="w-full border border-gray-200 rounded-lg pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-gray-600 font-bold mb-1">Ringkasan Kebutuhan / Keterangan</label>
                            <textarea wire:model="newLeadSummary" rows="3" placeholder="Tulis catatan ringkas kebutuhan client..."
                                class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-300"></textarea>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-500 font-bold rounded-lg transition border border-gray-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-950 hover:bg-indigo-900 text-white font-bold rounded-lg transition shadow-md">
                            Simpan Lead
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- Modal: Kelola Perangkat WA (Multi-Number Coexistence: 1 Kantor + 7 Marketing) --}}
    @if($showDevicesModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 max-h-[88vh] flex flex-col">
            <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between shrink-0">
                <div>
                    <h3 class="font-black text-sm">📱 Kelola Perangkat WhatsApp (Multi-Number Coexistence)</h3>
                    <p class="text-[11px] text-slate-300 mt-0.5">Petakan Phone Number ID dari Meta WhatsApp Manager ke WA Utama Kantor &amp; 7 Staf Marketing.</p>
                </div>
                <button wire:click="$set('showDevicesModal', false)" class="text-slate-300 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <div class="p-6 overflow-y-auto space-y-5">
                {{-- Form Tambah / Edit Perangkat --}}
                <form wire:submit.prevent="saveDevice" class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-black text-slate-800 uppercase tracking-wide">
                            {{ $editingDeviceId ? '✏️ Edit Perangkat WA' : '➕ Daftarkan Nomor WA Baru' }}
                        </p>
                        @if($editingDeviceId)
                            <button type="button" wire:click="resetDeviceForm" class="text-[11px] font-bold text-slate-500 hover:text-slate-700 underline">Batal Edit</button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Label Perangkat *</label>
                            <input type="text" wire:model="deviceLabel" placeholder="Contoh: WA Utama M2B / Marketing - Nurul" class="w-full rounded-lg border-slate-300 text-xs">
                            @error('deviceLabel') <span class="text-[10px] text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Nomor WA (628...) *</label>
                            <input type="text" wire:model="devicePhone" placeholder="0812xxxxxxxx" class="w-full rounded-lg border-slate-300 text-xs font-mono">
                            @error('devicePhone') <span class="text-[10px] text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Meta Phone Number ID *</label>
                            <input type="text" wire:model="devicePhoneNumberId" placeholder="Salin dari Meta WhatsApp Manager" class="w-full rounded-lg border-slate-300 text-xs font-mono">
                            @error('devicePhoneNumberId') <span class="text-[10px] text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Staf PIC Pemegang HP (Opsional)</label>
                            <select wire:model="deviceUserId" class="w-full rounded-lg border-slate-300 text-xs">
                                <option value="">-- Nomor Utama Kantor (Shared) --</option>
                                @foreach($salesReps as $rep)
                                    <option value="{{ $rep->id }}">{{ $rep->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <div class="flex items-center gap-4 text-xs">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:model="deviceIsPrimary" class="rounded text-indigo-600">
                                <span class="font-semibold text-slate-700">Jadikan WA Utama Kantor</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:model="deviceIsActive" class="rounded text-emerald-600">
                                <span class="font-semibold text-slate-700">Aktif</span>
                            </label>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-indigo-950 hover:bg-indigo-900 text-white text-xs font-bold rounded-lg transition">
                            {{ $editingDeviceId ? 'Simpan Perubahan' : 'Simpan Perangkat' }}
                        </button>
                    </div>
                </form>

                {{-- Daftar Perangkat Terdaftar --}}
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-700">
                        Perangkat Terdaftar ({{ $waDevices->count() }})
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($waDevices as $dev)
                            <div class="px-4 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-bold text-slate-900">{{ $dev->label }}</span>
                                        @if($dev->is_primary)
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">UTAMA</span>
                                        @endif
                                        @if(!$dev->is_active)
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-600">NONAKTIF</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-mono mt-0.5">+{{ $dev->phone_number }} • ID: {{ $dev->phone_number_id }} • {{ $dev->user ? 'PIC: ' . $dev->user->name : 'Shared Kantor' }}</p>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" wire:click="editDevice({{ $dev->id }})" class="text-[11px] font-bold px-2.5 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700">Edit</button>
                                    <button type="button" wire:click="deleteDevice({{ $dev->id }})" wire:confirm="Hapus perangkat WA ini?" class="text-[11px] font-bold px-2.5 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700">Hapus</button>
                                </div>
                            </div>
                        @empty
                            <p class="p-4 text-center text-xs text-slate-400 italic">Belum ada perangkat WA terdaftar.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
