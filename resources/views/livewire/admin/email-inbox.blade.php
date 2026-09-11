{{-- Tinggi dipakai inline, bukan class arbitrary Tailwind: build v4 di project
     ini tidak selalu meng-generate class arbitrary yang baru, jadi mengganti
     angkanya lewat class berisiko tidak berefek sama sekali. Angka 150px =
     tinggi header + bilah tab Pusat Email di atasnya. --}}
<div class="flex flex-col" style="height: calc(100vh - 150px);">
    @section('header', 'Communication Center')

    {{-- Floating Executive Toast Notifications (Anti-Layout Shift) --}}
    <div class="fixed top-6 right-6 z-[9999] pointer-events-none space-y-2 max-w-md w-full">
        @if (session()->has('message'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 4000)"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="pointer-events-auto bg-slate-900/95 text-white p-3.5 rounded-2xl shadow-2xl border border-slate-700/80 backdrop-blur-md flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-slate-100 leading-snug">{{ session('message') }}</span>
                </div>
                <button @click="show = false" class="text-slate-400 hover:text-white text-base leading-none">&times;</button>
            </div>
        @endif
        @if (session()->has('error'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 6000)"
                 class="pointer-events-auto bg-red-950/95 text-white p-3.5 rounded-2xl shadow-2xl border border-red-800 backdrop-blur-md flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-red-100 leading-snug">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-300 hover:text-white text-base leading-none">&times;</button>
            </div>
        @endif
        @if (session()->has('warning'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 class="pointer-events-auto bg-amber-950/95 text-white p-3.5 rounded-2xl shadow-2xl border border-amber-800 backdrop-blur-md flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <span class="text-xs font-semibold text-amber-100 leading-snug">{{ session('warning') }}</span>
                </div>
                <button @click="show = false" class="text-amber-300 hover:text-white text-base leading-none">&times;</button>
            </div>
        @endif
    </div>

    <div class="flex-1 flex bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        
        {{-- 1. SIDEBAR MAILBOX --}}
        <div class="w-48 bg-slate-900 flex flex-col text-slate-300 border-r border-slate-800 pt-3 shrink-0">
            <div class="px-3 mb-3">
                <button type="button" 
                        wire:click="openComposeModal" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold px-3 py-2.5 rounded-xl shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2 transition active:scale-95 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tulis Email</span>
                </button>
            </div>

            <div class="px-4 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Mailboxes</div>
            
            <div class="px-3 mb-3">
                <button wire:click="syncNow" 
                        wire:loading.attr="disabled" 
                        wire:target="syncNow"
                        class="w-full bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs px-3 py-2 rounded-lg shadow-sm flex items-center justify-center gap-2 transition-all">
                    <span wire:loading.remove wire:target="syncNow">🔄 Sync Now</span>
                    <span wire:loading wire:target="syncNow">⏳ Syncing...</span>
                </button>
                @if($lastSyncedAt)
                    <p class="text-[9px] text-slate-400 text-center font-mono mt-1">
                        ● Sinkron: {{ $lastSyncedAt }} WIB
                    </p>
                @endif
            </div>

            @foreach($mailboxes as $acc)
            <button wire:click="switchAccount('{{ $acc }}')" 
               class="w-full text-left flex items-center justify-between px-4 py-3 text-sm font-medium hover:bg-slate-800 transition border-l-4 {{ $activeAccount == $acc ? 'bg-slate-800 text-white border-blue-500' : 'border-transparent text-slate-400' }}">
                <span class="flex items-center gap-2 capitalize relative">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 00-2-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span class="relative">
                        {{ $acc }}
                        @php $unread = $unreadCounts[$acc] ?? 0; @endphp
                        @if($unread > 0)
                        <span class="absolute -top-2 -right-6 bg-red-500 text-white text-[8px] min-w-[14px] h-[14px] px-1 rounded-full font-black flex items-center justify-center shadow-sm shadow-red-500/50 scale-90 leading-none">
                            {{ $unread }}
                        </span>
                        @endif
                    </span>
                </span>
            </button>
            @endforeach
        </div>

        {{-- 2. EMAIL LIST --}}
        <div class="w-80 border-r border-gray-200 flex flex-col bg-gray-50/50 shrink-0">
            <div class="p-3 border-b border-gray-200 bg-white flex flex-col gap-2.5">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 min-w-0">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" 
                               wire:model.live.debounce.300ms="search" 
                               placeholder="Cari subjek, pengirim..." 
                               class="w-full pl-8 pr-7 py-1.5 border border-gray-200 rounded-xl text-xs bg-gray-50/80 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                        @if(!empty($search))
                            <button type="button" wire:click="clearSearch" class="absolute inset-y-0 right-0 pr-2 flex items-center text-gray-400 hover:text-gray-600 text-xs font-bold">
                                ✕
                            </button>
                        @endif
                    </div>
                    <button type="button" wire:click="toggleSelectMode" title="{{ $selectMode ? 'Batal pilih' : 'Pilih beberapa email' }}"
                            class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center text-xs transition-colors {{ $selectMode ? 'bg-red-50 text-red-600 border border-red-200 font-bold' : 'bg-gray-100 text-gray-500 hover:text-blue-600 hover:bg-blue-50' }}">
                        {{ $selectMode ? '✕' : '☑' }}
                    </button>
                </div>

                {{-- Quick Filter Pills --}}
                <div class="flex items-center gap-1.5 overflow-x-auto text-[11px] font-medium no-scrollbar">
                    <button type="button" wire:click="setFilter('all')"
                            class="px-2.5 py-1 rounded-lg transition whitespace-nowrap {{ $filter === 'all' ? 'bg-blue-600 text-white font-bold shadow-sm shadow-blue-500/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Semua
                    </button>
                    <button type="button" wire:click="setFilter('unread')"
                            class="px-2.5 py-1 rounded-lg transition whitespace-nowrap flex items-center gap-1 {{ $filter === 'unread' ? 'bg-blue-600 text-white font-bold shadow-sm shadow-blue-500/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $filter === 'unread' ? 'bg-white' : 'bg-blue-500' }}"></span>
                        <span>Belum Dibaca</span>
                    </button>
                    <button type="button" wire:click="setFilter('attachments')"
                            class="px-2.5 py-1 rounded-lg transition whitespace-nowrap flex items-center gap-1 {{ $filter === 'attachments' ? 'bg-blue-600 text-white font-bold shadow-sm shadow-blue-500/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        <span>Ada Lampiran</span>
                    </button>
                </div>
            </div>

            @if($selectMode)
            <div class="px-3 py-2 border-b border-gray-100 bg-red-50/60 flex items-center justify-between gap-2">
                <label class="flex items-center gap-2 text-xs font-bold text-gray-600 cursor-pointer">
                    <input type="checkbox" wire:click="toggleSelectAll" @checked(count($selectedEmailIds) > 0 && count($selectedEmailIds) >= count($emails))
                           class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                    {{ count($selectedEmailIds) }} dipilih
                </label>
                <button type="button" wire:click="bulkDeleteSelected" wire:confirm="Hapus {{ count($selectedEmailIds) }} email terpilih secara permanen dari portal & server?"
                        @disabled(empty($selectedEmailIds))
                        class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-[11px] font-black uppercase tracking-wide hover:bg-red-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                    🗑️ Hapus
                </button>
            </div>
            @endif

            <div class="overflow-y-auto flex-1 divide-y divide-gray-100 relative">
                <!-- Loading Overlay -->
                <div wire:loading wire:target="selectEmail" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                @forelse($emails as $email)
                <div wire:key="email-{{ $email['db_id'] }}"
                   wire:click="{{ $selectMode ? 'toggleOneSelection(' . $email['db_id'] . ')' : 'selectEmail(' . $email['db_id'] . ')' }}"
                   wire:loading.attr="disabled"
                   class="w-full text-left block p-4 hover:bg-blue-50/50 transition border-l-4 group relative cursor-pointer {{ $selectedEmail && $selectedEmail['db_id'] == $email['db_id'] ? 'bg-blue-50 border-l-blue-600' : 'bg-white border-l-transparent' }}">

                    {{-- Loading Indicator per Item --}}
                    <span wire:loading wire:target="selectEmail({{ $email['db_id'] }})" class="absolute right-2 top-2">
                        <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>

                    <div class="flex items-start gap-3">
                        @if($selectMode)
                        <input type="checkbox" wire:model="selectedEmailIds" value="{{ $email['db_id'] }}" @click.stop
                               class="mt-1.5 shrink-0 w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        @else
                        <div class="mt-1.5 shrink-0">
                            @if(!$email['is_read'])
                            <span class="block w-2 h-2 bg-blue-600 rounded-full ring-2 ring-blue-100"></span>
                            @else
                            <span class="block w-2 h-2 bg-transparent border border-gray-200 rounded-full"></span>
                            @endif
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-1 gap-2">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-sm truncate leading-snug {{ !$email['is_read'] ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $email['name'] }}
                                    </h4>
                                    @if(isset($email['sender_badge']) && !empty($email['sender_badge']['label']))
                                        <div class="mt-0.5">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-semibold border {{ $email['sender_badge']['bg'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $email['sender_badge']['dot'] }}"></span>
                                                {{ $email['sender_badge']['label'] }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0 ml-1">
                                    @if(isset($email['attachments']) && $email['attachments'] > 0)
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    @endif
                                    <span class="text-[9px] text-gray-400 font-bold uppercase {{ !$selectMode ? 'group-hover:opacity-0' : '' }} transition-opacity">{{ $email['date'] }}</span>
                                    @if(!$selectMode)
                                    <button type="button" wire:click.stop="deleteEmail({{ $email['db_id'] }})" wire:confirm="Hapus email ini secara permanen dari portal & server?"
                                            title="Hapus email"
                                            class="absolute right-3 top-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-gray-300 hover:text-red-500">
                                        🗑️
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs truncate text-gray-500 group-hover:text-gray-700 transition-colors">{{ $email['subject'] }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center p-12 text-center">
                    <svg class="w-12 h-12 text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    <p class="text-gray-400 text-sm font-medium">Inbox Kosong</p>
                    <button wire:click="syncNow" class="mt-4 text-xs font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wide">Sync Sekarang</button>
                </div>
                @endforelse
            </div>
        </div>

        {{-- 3. EMAIL CONTENT --}}
        <div class="flex-1 flex flex-col bg-white overflow-hidden shadow-inner">
            @if($selectedEmail)
                <div class="p-6 border-b border-gray-100 bg-white shrink-0 shadow-sm z-10">
                    {{-- Subjek (lebar penuh) --}}
                    <h2 class="text-xl font-black text-gray-900 leading-tight mb-4">{{ $selectedEmail['subject'] }}</h2>

                    {{-- Baris: pengirim (kiri) + toolbar aksi (kanan, boleh turun baris) --}}
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-md shrink-0">
                                {{ strtoupper(substr($selectedEmail['name'] ?? 'U', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ $selectedEmail['name'] }}</p>
                                    @if(isset($selectedEmail['sender_badge']) && !empty($selectedEmail['sender_badge']['label']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $selectedEmail['sender_badge']['bg'] }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $selectedEmail['sender_badge']['dot'] }}"></span>
                                            {{ $selectedEmail['sender_badge']['label'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 italic font-medium tracking-tight truncate">&lt;{{ $selectedEmail['from'] }}&gt; • {{ $selectedEmail['date'] }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                data-preview-url="{{ route('admin.inbox.body', $selectedEmail['db_id']) }}?v=2"
                                data-preview-subject="{{ $selectedEmail['subject'] }}"
                                data-preview-from="{{ $selectedEmail['from'] }}"
                                data-preview-date="{{ $selectedEmail['date'] }}"
                                onclick="openEmailPreviewFromEl(this)"
                                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl font-bold text-xs transition flex items-center gap-1.5 whitespace-nowrap shrink-0"
                                title="Perbesar isi email (layar penuh) — bisa langsung Print">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                <span>Perbesar</span>
                            </button>
                            <button wire:click="openReplyModal" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl font-bold text-xs transition shadow-sm flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                <span>Reply</span>
                            </button>
                            <button wire:click="openForwardModal" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-3.5 py-2 rounded-xl font-bold text-xs transition shadow-sm flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6-6m6 6l-6 6"/></svg>
                                <span>Forward</span>
                            </button>
                            <button wire:click="openConvertModal" title="Convert to Shipment Order" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-4 py-2 rounded-xl font-bold text-xs transition shadow-md shadow-blue-500/25 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span>Convert to Shipment</span>
                            </button>
                            <button wire:click="deleteEmail({{ $selectedEmail['db_id'] }})" wire:confirm="Hapus email ini secara permanen dari portal & server?"
                                    title="Hapus email"
                                    class="bg-white text-red-600 border border-red-200 px-3 py-2 rounded-xl font-bold text-xs hover:bg-red-50 transition shadow-sm flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-8 bg-gray-50/30">
                    <div class="relative bg-white p-4 rounded-2xl shadow-sm border border-gray-100 min-h-full group">
                        {{-- Tombol perbesar melayang di pojok kartu isi email --}}
                        <button type="button"
                            data-preview-url="{{ route('admin.inbox.body', $selectedEmail['db_id']) }}?v=2"
                            data-preview-subject="{{ $selectedEmail['subject'] }}"
                            data-preview-from="{{ $selectedEmail['from'] }}"
                            data-preview-date="{{ $selectedEmail['date'] }}"
                            onclick="openEmailPreviewFromEl(this)"
                            class="absolute top-3 right-3 z-20 flex items-center gap-1.5 bg-white/90 backdrop-blur border border-gray-200 text-gray-500 hover:text-slate-900 hover:border-slate-300 hover:shadow-md px-3 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-widest transition opacity-60 group-hover:opacity-100"
                            title="Perbesar isi email (layar penuh)">
                            ⛶ Perbesar
                        </button>
                        {{-- IFRAME FOR EMAIL BODY --}}
                        <iframe
                            src="{{ route('admin.inbox.body', $selectedEmail['db_id']) }}?v=2"
                            class="w-full border-0"
                            onload="this.style.height = this.contentWindow.document.body.scrollHeight + 'px'; this.contentWindow.document.body.style.overflow = 'hidden';"
                            style="min-height: 400px;">
                        </iframe>
                    </div>

                    @if(count($selectedEmail['attachments'] ?? []) > 0)
                    <div class="mt-8 pt-6 border-t border-gray-100">

                        {{-- Banner: file hilang dari server --}}
                        @if($hasMissingAttachments)
                        <div class="mb-4 flex items-center justify-between bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-amber-500 text-lg">⚠️</span>
                                <div>
                                    <p class="text-xs font-bold text-amber-800">File lampiran sudah terhapus dari server</p>
                                    <p class="text-[10px] text-amber-600">File akan diunduh ulang dari server email sebelum bisa di-convert.</p>
                                </div>
                            </div>
                            <button wire:click="redownloadAttachments"
                                wire:loading.attr="disabled"
                                wire:target="redownloadAttachments"
                                class="shrink-0 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-2">
                                <span wire:loading.remove wire:target="redownloadAttachments">🔄 Download Ulang</span>
                                <span wire:loading wire:target="redownloadAttachments">⏳ Mengunduh...</span>
                            </button>
                        </div>
                        @endif

                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-xs font-bold text-gray-500 uppercase flex items-center gap-2">
                                Lampiran ({{ count($selectedEmail['attachments']) }})
                            </h4>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-400">
                                    <span class="font-bold text-blue-600">{{ count($selectedAttachments) }}</span>/{{ count($selectedEmail['attachments']) }} dipilih untuk Convert
                                </span>
                                <button wire:click="selectAllAttachments" class="text-[10px] font-bold text-blue-600 hover:underline">Pilih Semua</button>
                                <button wire:click="deselectAllAttachments" class="text-[10px] font-bold text-gray-400 hover:underline">Batal Semua</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($selectedEmail['attachments'] as $index => $att)
                                @php
                                    $attName    = data_get($att, 'name') ?? data_get($att, 'filename', 'Unknown');
                                    $attId      = data_get($att, 'id', 0);
                                    $attSize    = data_get($att, 'size', 0);
                                    $attPath    = data_get($att, 'file_path');
                                    $ext        = strtolower(pathinfo($attName, PATHINFO_EXTENSION));
                                    $isImage    = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isPdf      = $ext === 'pdf';
                                    $iconBg     = $isImage ? 'bg-emerald-50 text-emerald-600' : ($isPdf ? 'bg-rose-50 text-rose-600' : 'bg-blue-50 text-blue-600');
                                    $isChecked  = in_array($index, $selectedAttachments);
                                    $fileMissing = $attPath && !\Illuminate\Support\Facades\Storage::disk('public')->exists($attPath) && !\Illuminate\Support\Facades\Storage::disk('local')->exists($attPath);
                                @endphp
                                <label class="flex items-center gap-3 bg-white p-3 rounded-xl border-2 cursor-pointer transition group
                                    {{ $fileMissing ? 'border-amber-200 bg-amber-50/30 opacity-70' : ($isChecked ? 'border-blue-400 bg-blue-50/30 shadow-sm' : 'border-gray-200 hover:border-blue-200 hover:shadow-md') }}">
                                    {{-- Checkbox --}}
                                    <input type="checkbox"
                                        wire:model.live="selectedAttachments"
                                        value="{{ $index }}"
                                        class="w-4 h-4 rounded text-blue-600 border-gray-300 focus:ring-blue-500 shrink-0 cursor-pointer">

                                    {{-- Icon --}}
                                    <div class="{{ $iconBg }} p-2 rounded-lg shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>

                                    {{-- Info --}}
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-gray-800 truncate">{{ $attName }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-tight {{ $fileMissing ? 'text-amber-500' : 'text-gray-400' }}">
                                            {{ $fileMissing ? '⚠ File terhapus' : (is_numeric($attSize) ? number_format($attSize / 1024, 1) . ' KB' : $attSize) }}
                                        </p>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex items-center gap-1 shrink-0" onclick="event.preventDefault()">
                                        @if($isImage || $isPdf)
                                            <button type="button"
                                                    onclick="openPreviewModal('{{ route('admin.inbox.attachment', ['mailbox' => $activeAccount, 'id' => $attId]) . '?mode=inline' }}', '{{ addslashes($attName) }}', '{{ $isImage ? 'image' : 'pdf' }}')"
                                                    class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition" title="Preview">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.inbox.attachment', ['mailbox' => $activeAccount, 'id' => $attId]) }}"
                                           class="p-1.5 text-gray-300 hover:text-blue-600 transition" title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        </a>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            @else
                <div class="flex-1 overflow-y-auto p-8 bg-slate-50/60 flex flex-col items-center justify-center">
                    <div class="max-w-md w-full space-y-6 text-center">
                        {{-- Icon Hub --}}
                        <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-blue-500/25">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 00-2-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>

                        <div>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200/60 text-blue-700 text-xs font-semibold uppercase tracking-wider mb-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                <span>Mailbox {{ strtoupper($activeAccount) }}</span>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight">Communication Control Center</h3>
                            <p class="text-xs text-slate-500 mt-1 font-mono">{{ $this->activeMailboxStats['email_address'] }}</p>
                        </div>

                        {{-- Metric Cards Grid --}}
                        <div class="grid grid-cols-3 gap-3 text-left">
                            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-sm">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Email</p>
                                <p class="text-lg font-black text-slate-800 mt-0.5">{{ number_format($this->activeMailboxStats['total']) }}</p>
                            </div>
                            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-sm">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-blue-500">Belum Dibaca</p>
                                <p class="text-lg font-black text-blue-600 mt-0.5">{{ number_format($this->activeMailboxStats['unread']) }}</p>
                            </div>
                            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-sm">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Ada Lampiran</p>
                                <p class="text-lg font-black text-emerald-600 mt-0.5">{{ number_format($this->activeMailboxStats['attachments']) }}</p>
                            </div>
                        </div>

                        {{-- Quick Action Buttons --}}
                        <div class="flex items-center justify-center gap-3 pt-2">
                            <button type="button" wire:click="openComposeModal" 
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Tulis Email Baru</span>
                            </button>
                            <button type="button" wire:click="setFilter('unread')" 
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold shadow-sm transition">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <span>Lihat Unread</span>
                            </button>
                        </div>

                        {{-- Operational Tip Card --}}
                        <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100 text-left text-xs flex items-start gap-2.5">
                            <span class="text-blue-500 text-base leading-none">💡</span>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                <strong class="text-slate-800 font-semibold">1-Click Convert to Shipment:</strong> Klik tombol <strong>Convert to Shipment</strong> pada email pesanan/inquiry untuk otomatis membuat order operasional baru beserta lampiran dokumen B/L &amp; Invoice.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL CONVERT --}}
    @if($showConvertModal)
    <div class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showConvertModal', false)"></div>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg sm:w-full relative z-[10000]">
                <div class="bg-white">
                    <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-black text-gray-800 uppercase text-sm tracking-widest">Konversi ke Shipment</h3>
                        <button wire:click="$set('showConvertModal', false)" class="text-gray-400 hover:text-red-500 transition-colors text-2xl leading-none">&times;</button>
                    </div>
                    <form wire:submit.prevent="convertToShipment">
                        <div class="p-8 space-y-5">

                            {{-- TOGGLE MODE --}}
                            <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-xl">
                                <button type="button" wire:click="$set('convertMode', 'new')"
                                    class="py-2 rounded-lg text-xs font-black uppercase tracking-wider transition
                                        {{ $convertMode === 'new' ? 'bg-white text-blue-700 shadow' : 'text-gray-400 hover:text-gray-600' }}">
                                    ✨ Shipment Baru
                                </button>
                                <button type="button" wire:click="$set('convertMode', 'existing')"
                                    class="py-2 rounded-lg text-xs font-black uppercase tracking-wider transition
                                        {{ $convertMode === 'existing' ? 'bg-white text-indigo-700 shadow' : 'text-gray-400 hover:text-gray-600' }}">
                                    📦 Shipment Berjalan
                                </button>
                            </div>

                            {{-- SMART AUTO-DETECTION BADGE --}}
                            @if($autoDetectedContext)
                            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3 text-left">
                                <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-emerald-900">Sistem Mendeteksi Otomatis:</p>
                                    <p class="text-xs text-emerald-700 mt-0.5">
                                        Customer: <strong class="font-bold text-emerald-950">{{ $autoDetectedContext['customer_name'] }}</strong><br>
                                        Layanan: <strong>{{ $autoDetectedContext['service'] }}</strong> &bull; Moda: <strong>{{ $autoDetectedContext['transport'] }}</strong>
                                    </p>
                                </div>
                            </div>
                            @endif

                            {{-- MODE: BUAT BARU --}}
                            @if($convertMode === 'new')
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Customer Profile</label>
                                <select wire:model="customer_id" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-3 shadow-sm">
                                    <option value="">-- Pilih Customer --</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Service</label>
                                    <select wire:model="service_type" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 py-3">
                                        <option value="import">IMPORT</option>
                                        <option value="export">EXPORT</option>
                                        <option value="domestic">DOMESTIC</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Transport</label>
                                    <select wire:model="shipment_type" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 py-3">
                                        <option value="sea">SEA FREIGHT</option>
                                        <option value="air">AIR FREIGHT</option>
                                    </select>
                                </div>
                            </div>
                            @endif

                            {{-- MODE: SHIPMENT BERJALAN --}}
                            @if($convertMode === 'existing')
                            <div x-data="{ open: false }">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Cari Shipment</label>
                                <input type="text"
                                    wire:model.live.debounce.300ms="existingShipmentSearch"
                                    @focus="open = true" @click.away="open = false"
                                    placeholder="Ketik No. Shipment atau nama customer..."
                                    class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-indigo-500 py-3 shadow-sm">

                                @if($existingShipmentId)
                                    @php $sel = \App\Models\Shipment::with('customer')->find($existingShipmentId); @endphp
                                    @if($sel)
                                    <div class="mt-2 flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3">
                                        <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-indigo-800">{{ $sel->awb_number }}</p>
                                            <p class="text-xs text-indigo-500">{{ $sel->customer->company_name ?? '-' }} · {{ strtoupper($sel->service_type) }} · {{ strtoupper($sel->status) }}</p>
                                        </div>
                                        <button type="button" wire:click="$set('existingShipmentId', null)" class="ml-auto text-indigo-300 hover:text-red-500 text-xl">&times;</button>
                                    </div>
                                    @endif
                                @endif

                                @if(strlen($existingShipmentSearch) >= 2 && !$existingShipmentId)
                                <div class="mt-1 border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white">
                                    @forelse($this->existingShipments as $s)
                                    <button type="button" wire:click="$set('existingShipmentId', {{ $s->id }}); $set('existingShipmentSearch', '')"
                                        class="w-full flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 border-b border-gray-100 last:border-0 text-left transition">
                                        <div class="shrink-0 w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"></path></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-800">{{ $s->awb_number }}</p>
                                            <p class="text-xs text-gray-400">{{ $s->customer->company_name ?? '-' }} · {{ strtoupper($s->service_type) }} · <span class="font-bold text-amber-500">{{ strtoupper($s->status) }}</span></p>
                                        </div>
                                    </button>
                                    @empty
                                    <p class="px-4 py-3 text-sm text-gray-400 text-center">Tidak ada shipment ditemukan</p>
                                    @endforelse
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- PILIH DOKUMEN ATTACHMENT --}}
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">📎 Pilih Dokumen Attachment</label>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="selectAllAttachments" class="text-[10px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wide">Select All</button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" wire:click="deselectAllAttachments" class="text-[10px] font-bold text-gray-500 hover:text-gray-700 uppercase tracking-wide">Deselect All</button>
                                    </div>
                                </div>
                                @if($selectedEmail && isset($selectedEmail['attachments']) && count($selectedEmail['attachments']) > 0)
                                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                                        <div class="max-h-48 overflow-y-auto">
                                            @foreach($selectedEmail['attachments'] as $index => $attachment)
                                                <label class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors group">
                                                    <input type="checkbox" wire:model="selectedAttachments" value="{{ $index }}"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                                    <div class="ml-3 flex-1">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-sm font-bold text-gray-800 group-hover:text-blue-600 transition-colors truncate">
                                                                {{ $attachment['filename'] ?? '-' }}
                                                            </span>
                                                            <span class="text-xs text-gray-400 ml-2 shrink-0">
                                                                {{ number_format(($attachment['size'] ?? 0) / 1024, 1) }} KB
                                                            </span>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 ml-1">
                                        <span class="font-bold text-blue-600">{{ count($selectedAttachments) }}</span> dari
                                        <span class="font-bold">{{ count($selectedEmail['attachments']) }}</span> dokumen dipilih
                                    </p>
                                @else
                                    <div class="border border-dashed border-gray-300 rounded-xl px-4 py-8 text-center">
                                        <p class="text-sm text-gray-400 font-medium">Tidak ada attachment pada email ini</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="px-8 py-5 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                            <button type="button" wire:click="$set('showConvertModal', false)" class="px-6 py-2.5 text-xs font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-all">Batal</button>
                            <button type="submit"
                                class="px-8 py-2.5 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg transition-all
                                    {{ $convertMode === 'existing' ? 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-100' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-100' }}">
                                {{ $convertMode === 'existing' ? '+ Tambah ke Shipment' : 'Create Shipment' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- REPLY / FORWARD / COMPOSE MODAL --}}
    @if($showReplyModal)
    @php
        $isReply = $emailMode === 'reply';
        $isForward = $emailMode === 'forward';
        $isCompose = $emailMode === 'compose';
        $modeAccent = $isCompose ? 'blue' : ($isReply ? 'emerald' : 'indigo');
        $totalAttachCount = count($newAttachments)
            + ($attachQuotationId ? 1 : 0)
            + ($attachInvoiceId ? 1 : 0)
            + ($isForward ? count($forwardAttachments ?? []) : 0);
    @endphp
    <div class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="closeReplyModal"></div>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-2xl sm:w-full relative z-[10000]">
                <div class="bg-white" x-data="{ attachTab: 'upload' }">
                    {{-- Header --}}
                    <div class="px-8 py-5 border-b border-gray-100 flex items-center justify-between {{ $isCompose ? 'bg-gradient-to-r from-blue-900 via-indigo-900 to-blue-800 text-white' : 'bg-gray-50/50' }}">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-base shrink-0
                                {{ $isCompose ? 'bg-white/10 text-white border border-white/20' : ($isReply ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600') }}">
                                {{ $isCompose ? '✏️' : ($isReply ? '↩️' : '➡️') }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-black text-sm tracking-widest uppercase {{ $isCompose ? 'text-white' : 'text-gray-800' }}">
                                    {{ $isCompose ? 'Tulis Email Baru' : ($isReply ? 'Reply Email' : 'Forward Email') }}
                                </h3>
                                @if($isCompose)
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[11px] text-blue-200">Dari Akun Mailbox:</span>
                                    <select wire:model.live="activeAccount" class="text-xs font-bold text-gray-900 bg-white/95 rounded-lg px-2.5 py-0.5 border-0 shadow-sm focus:ring-2 focus:ring-blue-400">
                                        @foreach($mailboxes as $acc)
                                            <option value="{{ $acc }}">{{ ucfirst($acc) }} ({{ $mailboxEmails[$acc] ?? $acc.'@m2b.co.id' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                @elseif($selectedEmail)
                                <p class="text-xs text-gray-400 font-medium truncate mt-0.5">{{ $selectedEmail['subject'] ?? '' }}</p>
                                @endif
                            </div>
                        </div>
                        <button wire:click="closeReplyModal" class="{{ $isCompose ? 'text-white/70 hover:text-white hover:bg-white/10' : 'text-gray-400 hover:text-red-500' }} p-1.5 rounded-xl transition-colors text-2xl leading-none">&times;</button>
                    </div>

                    <form wire:submit.prevent="sendReply">
                        <div class="p-6 md:p-8 space-y-5">

                            {{-- Recipient card --}}
                            <div class="border border-gray-200 rounded-2xl divide-y divide-gray-100 overflow-hidden bg-white shadow-sm">
                                <div class="flex items-center gap-3 px-4 py-3">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest w-9 shrink-0">To</span>
                                    <input type="email" wire:model="replyTo" placeholder="penerima@perusahaan.com"
                                           class="flex-1 border-0 focus:ring-0 text-sm font-bold text-gray-800 py-1 px-0 placeholder:text-gray-300">
                                </div>
                                <div class="flex flex-col gap-1 px-4 py-2.5 bg-gray-50/40">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest w-9 shrink-0">Cc</span>
                                        <input type="text" wire:model="replyCc" placeholder="staf@m2b.co.id, finance@m2b.co.id"
                                               class="flex-1 border-0 focus:ring-0 text-xs font-mono text-gray-800 py-1 px-0 bg-transparent placeholder:text-gray-300">
                                    </div>
                                    {{-- Quick CC presets --}}
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1.5 border-t border-gray-100">
                                        <span class="text-[10px] text-gray-400 font-semibold">CC Cepat:</span>
                                        @if(auth()->check() && auth()->user()->email)
                                            @php
                                                $myEmail = auth()->user()->email;
                                                $isMyEmailActive = str_contains(strtolower($replyCc ?? ''), strtolower($myEmail));
                                            @endphp
                                            <button type="button" 
                                                    wire:click="toggleReplyCcPreset('{{ $myEmail }}')"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold transition {{ $isMyEmailActive ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100' }}">
                                                <span>{{ $isMyEmailActive ? '✓' : '+' }}</span> Email Saya
                                            </button>
                                        @endif
                                        @foreach(['sales@m2b.co.id' => 'Sales', 'finance@m2b.co.id' => 'Finance', 'import@m2b.co.id' => 'Import', 'export@m2b.co.id' => 'Export', 'shipping@m2b.co.id' => 'Shipping'] as $emailPreset => $labelPreset)
                                            @php
                                                $isActive = str_contains(strtolower($replyCc ?? ''), strtolower($emailPreset));
                                            @endphp
                                            <button type="button" 
                                                    wire:click="toggleReplyCcPreset('{{ $emailPreset }}')"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium transition {{ $isActive ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100' }}">
                                                <span>{{ $isActive ? '✓' : '+' }}</span> {{ $labelPreset }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('replyTo') <span class="text-xs text-red-500 -mt-3 block ml-1">{{ $message }}</span> @enderror
                            @error('replyCc') <span class="text-xs text-red-500 -mt-3 block ml-1">{{ $message }}</span> @enderror

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Subjek Email</label>
                                <input type="text" wire:model="replySubject" placeholder="Tulis subjek email di sini..." class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-2.5 px-4">
                                @error('replySubject') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Template Selector -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest">⚡ Quick Template</label>
                                    <button type="button" wire:click="switchTemplateLang" class="px-3 py-1 text-xs font-bold rounded-lg transition-all {{ $templateLang === 'ID' ? 'bg-red-500 text-white' : 'bg-blue-500 text-white' }}">
                                        {{ $templateLang === 'ID' ? '🇮🇩 ID' : '🇬🇧 EN' }}
                                    </button>
                                </div>
                                <div class="flex gap-2">
                                    <select wire:model="selectedTemplate" class="flex-1 border-blue-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 py-2 px-3 bg-white">
                                        <option value="">-- Pilih Template --</option>
                                        @foreach($this->templates as $category => $templates)
                                            <optgroup label="{{ config('email_templates.categories.' . $category, $category) }}">
                                                @foreach($templates as $key => $template)
                                                    <option value="{{ $key }}">{{ $template['name'] }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="applyTemplate" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition-all shadow-md">
                                        Terapkan
                                    </button>
                                </div>
                            </div>

                            {{-- LAMPIRAN: chip rail + tabbed sources --}}
                            <div class="border border-violet-100 bg-violet-50/40 rounded-2xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="text-[10px] font-black text-violet-600 uppercase tracking-widest">📎 Lampiran</label>
                                    @if($totalAttachCount > 0)
                                    <span class="text-[10px] font-black text-violet-600 bg-violet-100 rounded-full px-2 py-0.5">{{ $totalAttachCount }} terpasang</span>
                                    @endif
                                </div>

                                {{-- Chip rail: semua yang sudah terpasang, apapun sumbernya --}}
                                @if($totalAttachCount > 0)
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @if(!$isReply)
                                        @foreach(($selectedEmail['attachments'] ?? []) as $index => $attachment)
                                            @if(in_array((string)$index, $forwardAttachments ?? []))
                                            <span class="inline-flex items-center gap-1.5 bg-white border border-gray-200 rounded-full px-3 py-1 text-xs font-bold text-gray-700" title="Kelola di tab &quot;Email Asli&quot;">
                                                📨 {{ Str::limit($attachment['filename'], 20) }}
                                            </span>
                                            @endif
                                        @endforeach
                                    @endif
                                    @foreach($newAttachments as $index => $file)
                                    <span class="inline-flex items-center gap-1.5 bg-white border border-blue-200 rounded-full pl-3 pr-1.5 py-1 text-xs font-bold text-blue-700">
                                        📎 {{ Str::limit($file->getClientOriginalName(), 20) }}
                                        <button type="button" wire:click="removeNewAttachment({{ $index }})" class="w-4 h-4 rounded-full hover:bg-blue-100 text-blue-400 hover:text-red-500 leading-none">&times;</button>
                                    </span>
                                    @endforeach
                                    @if($attachQuotationId)
                                    <span class="inline-flex items-center gap-1.5 bg-white border border-indigo-200 rounded-full pl-3 pr-1.5 py-1 text-xs font-bold text-indigo-700">
                                        🧾 {{ Str::limit($attachQuotationLabel, 24) }}
                                        <button type="button" wire:click="removeQuotationAttachment" class="w-4 h-4 rounded-full hover:bg-indigo-100 text-indigo-400 hover:text-red-500 leading-none">&times;</button>
                                    </span>
                                    @endif
                                    @if($attachInvoiceId)
                                    <span class="inline-flex items-center gap-1.5 bg-white border border-emerald-200 rounded-full pl-3 pr-1.5 py-1 text-xs font-bold text-emerald-700">
                                        🧾 {{ Str::limit($attachInvoiceLabel, 24) }}
                                        <button type="button" wire:click="removeInvoiceAttachment" class="w-4 h-4 rounded-full hover:bg-emerald-100 text-emerald-400 hover:text-red-500 leading-none">&times;</button>
                                    </span>
                                    @endif
                                </div>
                                @endif

                                {{-- Tab switcher (client-side, no round trip) --}}
                                <div class="flex gap-1 bg-white/70 border border-violet-100 rounded-xl p-1 mb-3">
                                    @if(!$isReply && $selectedEmail && count($selectedEmail['attachments'] ?? []) > 0)
                                    <button type="button" @click="attachTab = 'original'"
                                            :class="attachTab === 'original' ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-500 hover:text-violet-600'"
                                            class="flex-1 text-[11px] font-bold rounded-lg py-1.5 transition-all">📨 Email Asli</button>
                                    @endif
                                    <button type="button" @click="attachTab = 'upload'"
                                            :class="attachTab === 'upload' ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-500 hover:text-violet-600'"
                                            class="flex-1 text-[11px] font-bold rounded-lg py-1.5 transition-all">📎 Upload</button>
                                    <button type="button" @click="attachTab = 'quotation'"
                                            :class="attachTab === 'quotation' ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-500 hover:text-violet-600'"
                                            class="flex-1 text-[11px] font-bold rounded-lg py-1.5 transition-all">🧾 Quotation</button>
                                    <button type="button" @click="attachTab = 'invoice'"
                                            :class="attachTab === 'invoice' ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-500 hover:text-violet-600'"
                                            class="flex-1 text-[11px] font-bold rounded-lg py-1.5 transition-all">🧾 Invoice</button>
                                </div>

                                {{-- Panel: email asli (forward only) --}}
                                @if(!$isReply && $selectedEmail && count($selectedEmail['attachments'] ?? []) > 0)
                                <div x-show="attachTab === 'original'" x-cloak>
                                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-white max-h-32 overflow-y-auto divide-y divide-gray-100">
                                        @foreach($selectedEmail['attachments'] as $index => $attachment)
                                        <label class="flex items-center px-4 py-2.5 hover:bg-violet-50 cursor-pointer transition-colors group">
                                            <input type="checkbox" wire:model="forwardAttachments" value="{{ $index }}" class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500">
                                            <span class="ml-3 text-xs font-bold text-gray-800 truncate">{{ $attachment['filename'] }}</span>
                                            <span class="ml-auto text-xs text-gray-400 font-medium">{{ number_format(($attachment['size'] ?? 0) / 1024, 1) }} KB</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                {{-- Panel: upload file baru --}}
                                <div x-show="attachTab === 'upload'" x-cloak>
                                    <input type="file" wire:model="newAttachments" multiple
                                           class="w-full text-xs border border-gray-200 rounded-xl py-2.5 px-4 bg-white file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                                    <div wire:loading wire:target="newAttachments" class="text-xs text-violet-600 mt-1 ml-1">Mengupload...</div>
                                    @error('newAttachments.*') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                    <p class="text-[10px] text-gray-400 mt-1.5 ml-1">Max 10MB/file — PDF, gambar, Office, ZIP.</p>
                                </div>

                                {{-- Panel: pilih quotation dari sistem --}}
                                <div x-show="attachTab === 'quotation'" x-cloak>
                                    @if($attachQuotationId)
                                    <div class="flex items-center justify-between bg-white border border-indigo-200 rounded-xl px-4 py-2.5">
                                        <span class="text-xs font-bold text-indigo-800">✓ {{ $attachQuotationLabel }}</span>
                                        <button type="button" wire:click="removeQuotationAttachment" class="text-red-400 hover:text-red-600 text-xs font-bold ml-2">Ganti</button>
                                    </div>
                                    @else
                                    <div class="relative">
                                        <input type="text" wire:model.live.debounce.400ms="attachQuotationSearch" placeholder="Cari nomor quotation / nama customer..."
                                               class="w-full border-gray-200 rounded-xl text-sm bg-white focus:ring-violet-500 focus:border-violet-500 py-2.5 px-4">
                                        @if(count($attachQuotationResults) > 0)
                                        <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-40 overflow-y-auto">
                                            @foreach($attachQuotationResults as $row)
                                            <button type="button" wire:click="selectQuotationToAttach({{ $row['id'] }})" class="w-full text-left px-4 py-2 text-xs font-bold text-gray-700 hover:bg-indigo-50 border-b border-gray-50 last:border-0">
                                                {{ $row['label'] }}
                                            </button>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>

                                {{-- Panel: pilih invoice dari sistem --}}
                                <div x-show="attachTab === 'invoice'" x-cloak>
                                    @if($attachInvoiceId)
                                    <div class="flex items-center justify-between bg-white border border-emerald-200 rounded-xl px-4 py-2.5">
                                        <span class="text-xs font-bold text-emerald-800">✓ {{ $attachInvoiceLabel }}</span>
                                        <button type="button" wire:click="removeInvoiceAttachment" class="text-red-400 hover:text-red-600 text-xs font-bold ml-2">Ganti</button>
                                    </div>
                                    @else
                                    <div class="relative">
                                        <input type="text" wire:model.live.debounce.400ms="attachInvoiceSearch" placeholder="Cari nomor invoice / nama customer..."
                                               class="w-full border-gray-200 rounded-xl text-sm bg-white focus:ring-violet-500 focus:border-violet-500 py-2.5 px-4">
                                        @if(count($attachInvoiceResults) > 0)
                                        <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-40 overflow-y-auto">
                                            @foreach($attachInvoiceResults as $row)
                                            <button type="button" wire:click="selectInvoiceToAttach({{ $row['id'] }})" class="w-full text-left px-4 py-2 text-xs font-bold text-gray-700 hover:bg-emerald-50 border-b border-gray-50 last:border-0">
                                                {{ $row['label'] }}
                                            </button>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Message</label>
                                <textarea wire:model="replyBody" rows="10" class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4" placeholder="Type your message here..."></textarea>
                                @error('replyBody') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="px-8 py-5 bg-gray-50 flex justify-end items-center gap-3 border-t border-gray-100">
                            <button type="button" wire:click="closeReplyModal" class="px-6 py-2.5 text-xs font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-all">Batal</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="sendReply"
                                    class="px-8 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg transition-all text-white disabled:opacity-60 disabled:cursor-not-allowed
                                    {{ $isCompose ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-200' : ($isReply ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-100' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-100') }}">
                                <span wire:loading.remove wire:target="sendReply" class="flex items-center gap-2">
                                    <span>{{ $isCompose ? '🚀 Kirim Email' : ($isReply ? 'Kirim Balasan' : 'Kirim Forward') }}</span>
                                    @if($totalAttachCount > 0)
                                        <span class="bg-white/20 px-2 py-0.5 rounded-full text-[10px]">📎 {{ $totalAttachCount }}</span>
                                    @endif
                                </span>
                                <span wire:loading wire:target="sendReply" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Mengirim...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- PREVIEW MODAL --}}
    <div id="previewModal" class="fixed inset-0 bg-slate-900/80 z-[10000] hidden items-center justify-center p-6 backdrop-blur-sm" onclick="if(event.target === this) closePreviewModal()">
        <div class="bg-white rounded-3xl shadow-2xl max-w-6xl w-full max-h-[92vh] flex flex-col overflow-hidden animate-zoom-in">
            <div class="flex items-center justify-between px-8 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800" id="previewFileName">File Preview</h3>
                <button onclick="closePreviewModal()" class="p-2 text-gray-400 hover:text-red-500 transition-colors">&times;</button>
            </div>
            <div class="flex-1 overflow-auto bg-gray-100 p-6" id="previewContent"></div>
        </div>
    </div>

    {{-- FULLSCREEN EMAIL PREVIEW MODAL --}}
    <div id="emailPreviewModal" class="fixed inset-0 bg-slate-900/80 z-[10001] hidden items-center justify-center p-4 backdrop-blur-sm" onclick="if(event.target === this) closeEmailPreview()">
        <div class="bg-white rounded-3xl shadow-2xl w-[96vw] h-[95vh] max-w-[1600px] flex flex-col overflow-hidden animate-zoom-in" style="position: relative; z-index: 10;">
            {{-- Header modal --}}
            <div class="flex items-start justify-between gap-4 px-8 py-5 border-b border-gray-100 shrink-0 bg-white">
                <div class="min-w-0">
                    <h3 class="font-black text-gray-900 text-lg leading-tight truncate" id="emailPreviewSubject">Isi Email</h3>
                    <p class="text-xs text-gray-400 italic font-medium mt-1 truncate" id="emailPreviewMeta"></p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <button type="button" onclick="printEmailPreview()"
                        class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center gap-2">
                        🖨️ Print
                    </button>
                    <button type="button" onclick="closeEmailPreview()"
                        class="bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-500 w-10 h-10 rounded-xl font-black text-2xl leading-none flex items-center justify-center transition" title="Tutup (Esc)">&times;</button>
                </div>
            </div>
            {{-- Isi email --}}
            <div class="flex-1 overflow-auto bg-gray-50 p-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 max-w-5xl mx-auto min-h-full">
                    <iframe id="emailPreviewFrame" src="about:blank" class="w-full border-0 rounded-2xl"
                        onload="fitEmailPreviewFrame(this)" style="min-height: calc(95vh - 160px);"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openPreviewModal(url, filename, type) {
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    document.getElementById('previewFileName').textContent = filename;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    content.innerHTML = '<div class="flex items-center justify-center h-full min-h-[400px]"><svg class="animate-spin h-10 w-10 text-blue-600" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>';
    
    setTimeout(() => {
        if (type === 'image') {
            content.innerHTML = '<div class="flex items-center justify-center min-h-[400px]"><img src="' + url + '" class="max-w-full max-h-[75vh] object-contain rounded-2xl shadow-lg border-4 border-white"></div>';
        } else if (type === 'pdf') {
            content.innerHTML = '<iframe src="' + url + '#toolbar=1&navpanes=0" class="w-full h-[75vh] rounded-2xl border border-gray-200 bg-white" frameborder="0"></iframe>';
        }
    }, 400);
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

/* ===== Fullscreen Email Preview + Print ===== */
function openEmailPreviewFromEl(el) {
    openEmailPreview(el.dataset.previewUrl, el.dataset.previewSubject || 'Isi Email', el.dataset.previewFrom || '', el.dataset.previewDate || '');
}

function openEmailPreview(url, subject, from, date) {
    const modal = document.getElementById('emailPreviewModal');
    const frame = document.getElementById('emailPreviewFrame');
    document.getElementById('emailPreviewSubject').textContent = subject;
    document.getElementById('emailPreviewMeta').textContent = [from ? '<' + from + '>' : '', date].filter(Boolean).join('  •  ');
    frame.dataset.subject = subject;
    frame.dataset.from = from;
    frame.dataset.date = date;
    frame.src = url;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fitEmailPreviewFrame(frame) {
    try { frame.contentWindow.document.body.style.overflow = 'auto'; } catch (e) {}
}

function closeEmailPreview() {
    const modal = document.getElementById('emailPreviewModal');
    const frame = document.getElementById('emailPreviewFrame');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    frame.src = 'about:blank';
    document.body.style.overflow = '';
}

function printEmailPreview() {
    const frame = document.getElementById('emailPreviewFrame');
    try {
        const win = frame.contentWindow;
        const doc = win.document;
        // Sisipkan kop (subjek + pengirim + tanggal) khusus untuk cetakan
        let header = doc.getElementById('__m2b_print_header');
        if (!header) {
            header = doc.createElement('div');
            header.id = '__m2b_print_header';
            header.style.cssText = 'font-family: Arial, Helvetica, sans-serif; border-bottom:2px solid #0f172a; margin:0 0 16px; padding:0 0 12px;';
            header.innerHTML =
                '<h2 style="margin:0 0 6px; font-size:18px; color:#0f172a;">' + (frame.dataset.subject || '') + '</h2>' +
                '<div style="font-size:12px; color:#64748b;">' +
                    (frame.dataset.from ? '&lt;' + frame.dataset.from + '&gt;' : '') +
                    (frame.dataset.date ? ' &bull; ' + frame.dataset.date : '') +
                '</div>';
            doc.body.insertBefore(header, doc.body.firstChild);
        }
        win.onafterprint = function () { header.remove(); win.onafterprint = null; };
        win.focus();
        win.print();
    } catch (e) {
        // Fallback bila iframe tak bisa diakses langsung
        window.open(frame.src, '_blank');
    }
}

// Tutup modal preview email dengan tombol Esc
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('emailPreviewModal');
        if (modal && !modal.classList.contains('hidden')) closeEmailPreview();
    }
});
</script>