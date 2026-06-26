<div class="space-y-6" x-data="{ showModal: @entangle('showCreateModal') }">

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

    {{-- Premium Stats & Metrics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-700 text-white rounded-2xl shadow-md p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg flex flex-col justify-between h-28">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-100">Baru (New)</span>
                <span class="text-2xl">📥</span>
            </div>
            <div>
                <div class="text-3xl font-black">{{ $totalNew }}</div>
                <div class="text-[10px] text-indigo-100 font-semibold mt-0.5">Lead perlu ditindaklanjuti</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-2xl shadow-md p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg flex flex-col justify-between h-28">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-100">Hot Leads</span>
                <span class="text-2xl">🔥</span>
            </div>
            <div>
                <div class="text-3xl font-black">{{ $totalHot }}</div>
                <div class="text-[10px] text-rose-100 font-semibold mt-0.5">Prospek minat tinggi</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl shadow-md p-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg flex flex-col justify-between h-28">
            <div class="flex justify-between items-start">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-100">Follow-Up Pending</span>
                <span class="text-2xl">⏰</span>
            </div>
            <div>
                <div class="text-3xl font-black">{{ $totalFollowUp }}</div>
                <div class="text-[10px] text-amber-100 font-semibold mt-0.5">Jadwal follow-up aktif</div>
            </div>
        </div>
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
            <div class="flex gap-2 items-center justify-end w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <input wire:model.live.debounce.300ms="search"
                        type="text" placeholder="Cari nama, perusahaan..."
                        class="text-xs border border-gray-200 rounded-lg pl-8 pr-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <span class="absolute left-2.5 top-2.5 text-gray-400">🔍</span>
                </div>
                
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
            <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center shrink-0">
                <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Daftar Leads</span>
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
                                    @if($lead->source === 'mora_chat')
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

                            {{-- Row 4: Status Indicator --}}
                            <div class="flex justify-between items-center mt-1 text-[10px] text-gray-400">
                                <div class="flex items-center gap-1.5">
                                    <span>📞 {{ $lead->phone }}</span>
                                    @if($lead->email)
                                        <span>· ✉️ {{ $lead->email }}</span>
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

        {{-- Right: CRM Lead Details --}}
        <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[650px]">
            @if(!$selectedLead)
                <div class="m-auto text-center p-8 max-w-sm text-gray-400">
                    <div class="text-6xl mb-4 text-indigo-100">📁</div>
                    <h3 class="font-bold text-gray-700 text-base">Detail CRM Pipeline</h3>
                    <p class="text-xs mt-2 leading-relaxed">Pilih salah satu lead dari daftar di sebelah kiri untuk melihat transkrip MORA Chat, memperbarui stage penjualan, menambahkan catatan sales, atau mem-follow up via WhatsApp.</p>
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

                        <div class="flex gap-2 mt-2 pt-2 border-t border-gray-200/60">
                            @if($selectedLead->hasValidPhone())
                            <a href="{{ $whatsappUrl }}" target="_blank"
                                class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold py-2 rounded-lg transition shadow-sm">
                                💬 WhatsApp Chat
                            </a>
                            @endif
                            @if($selectedLead->email)
                                <a href="mailto:{{ $selectedLead->email }}"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2 rounded-lg transition border border-slate-200">
                                    ✉️ Email Client
                                </a>
                            @endif
                        </div>
                    </div>

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
                                                <div class="bg-indigo-600 text-white text-xs px-3 py-2 rounded-2xl rounded-tr-sm leading-relaxed shadow-sm">
                                                    {{ $msg['content'] ?? '' }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex justify-start">
                                            <div class="max-w-[80%]">
                                                <span class="text-[8px] text-gray-400 block font-semibold mb-0.5">🤖 MORA AI</span>
                                                <div class="bg-white border border-gray-200 text-gray-700 text-xs px-3 py-2 rounded-2xl rounded-tl-sm leading-relaxed shadow-sm">
                                                    {{ $msg['content'] ?? '' }}
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

</div>
