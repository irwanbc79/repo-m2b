<div class="h-[calc(100vh-100px)] flex flex-col">
    @section('header', 'Communication Center')

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-4 flex items-center justify-between">
            <span class="font-bold">{{ session('message') }}</span>
            <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">&times;</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4 flex items-center justify-between">
            <span class="font-bold">{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">&times;</button>
        </div>
    @endif

    <div class="flex-1 flex bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        
        {{-- 1. SIDEBAR MAILBOX --}}
        <div class="w-48 bg-slate-900 flex flex-col text-slate-300 border-r border-slate-800 pt-4 shrink-0">
            <div class="px-4 mb-4 text-[10px] font-bold uppercase tracking-wider text-slate-500">Mailboxes</div>
            
            <div class="px-3 mb-4">
                <button wire:click="syncNow" 
                        wire:loading.attr="disabled" 
                        wire:target="syncNow"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded shadow flex items-center justify-center gap-2 transition-all">
                    <span wire:loading.remove wire:target="syncNow">🔄 Sync Now</span>
                    <span wire:loading wire:target="syncNow">⏳ Syncing...</span>
                </button>
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
            <div class="p-3 border-b border-gray-100 bg-white">
                <input type="text" placeholder="Search mail..." class="w-full pl-3 pr-4 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500/20">
            </div>
            
            <div class="overflow-y-auto flex-1 divide-y divide-gray-100 relative">
                <!-- Loading Overlay -->
                <div wire:loading wire:target="selectEmail" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                @forelse($emails as $email)
                <button wire:click="selectEmail({{ $email['db_id'] }})" 
                   wire:key="email-{{ $email['db_id'] }}"
                   wire:loading.attr="disabled"
                   class="w-full text-left block p-4 hover:bg-blue-50/50 transition border-l-4 group relative {{ $selectedEmail && $selectedEmail['db_id'] == $email['db_id'] ? 'bg-blue-50 border-l-blue-600' : 'bg-white border-l-transparent' }}">
                    
                    {{-- Loading Indicator per Item --}}
                    <span wire:loading wire:target="selectEmail({{ $email['db_id'] }})" class="absolute right-2 top-2">
                        <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>

                    <div class="flex items-start gap-3">
                        <div class="mt-1.5 shrink-0">
                            @if(!$email['is_read'])
                            <span class="block w-2 h-2 bg-blue-600 rounded-full ring-2 ring-blue-100"></span>
                            @else
                            <span class="block w-2 h-2 bg-transparent border border-gray-200 rounded-full"></span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-0.5">
                                <h4 class="text-sm truncate {{ !$email['is_read'] ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                    {{ $email['name'] }}
                                </h4>
                                <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                    @if(isset($email['attachments']) && $email['attachments'] > 0)
                                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    @endif
                                    <span class="text-[9px] text-gray-400 font-bold uppercase">{{ $email['date'] }}</span>
                                </div>
                            </div>
                            <p class="text-xs truncate text-gray-500 group-hover:text-gray-700 transition-colors">{{ $email['subject'] }}</p>
                        </div>
                    </div>
                </button>
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
                            <div class="w-11 h-11 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-lg shrink-0">
                                {{ strtoupper(substr($selectedEmail['name'] ?? 'U', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate">{{ $selectedEmail['name'] }}</p>
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
                                class="bg-slate-800 text-white px-4 py-2.5 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-slate-900 transition shadow-lg shadow-slate-200 flex items-center gap-1.5 whitespace-nowrap shrink-0"
                                title="Perbesar isi email (layar penuh) — bisa langsung Print">
                                ⛶ Perbesar
                            </button>
                            <button wire:click="openReplyModal" class="bg-green-600 text-white px-4 py-2.5 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-green-700 transition shadow-lg shadow-green-100 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                ✉️ Reply
                            </button>
                            <button wire:click="openForwardModal" class="bg-indigo-600 text-white px-4 py-2.5 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                ➡️ Forward
                            </button>
                            <button wire:click="$set('showConvertModal', true)" title="Convert to Shipment" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                📦 Convert
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
                <div class="flex-1 flex flex-col items-center justify-center text-gray-300 bg-gray-50/50">
                    <svg class="w-16 h-16 opacity-10 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <p class="text-xs font-black uppercase tracking-widest opacity-50">Pilih email untuk dibaca</p>
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

    {{-- REPLY MODAL --}}
    @if($showReplyModal)
    <div class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="closeReplyModal"></div>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-2xl sm:w-full relative z-[10000]">
                <div class="bg-white">
                    <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-black text-gray-800 uppercase text-sm tracking-widest">
                            @if($emailMode === 'reply') ✉️ Reply Email @else ➡️ Forward Email @endif
                        </h3>
                        <button wire:click="closeReplyModal" class="text-gray-400 hover:text-red-500 transition-colors text-2xl leading-none">&times;</button>
                    </div>
                    <form wire:submit.prevent="sendReply">
                        <div class="p-8 space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">To</label>
                                @if($emailMode === 'reply')
                                    <input type="email" wire:model="replyTo" readonly class="w-full border-gray-200 rounded-xl text-sm font-bold bg-gray-50 py-3 px-4">
                                @else
                                    <input type="email" wire:model="replyTo" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-3 px-4" placeholder="recipient@example.com">
                                    @error('replyTo') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                @endif
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Cc</label>
                                <input type="text" wire:model="replyCc" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-3 px-4" placeholder="cc1@example.com, cc2@example.com">
                                @error('replyCc') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Subject</label>
                                <input type="text" wire:model="replySubject" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-3 px-4">
                                @error('replySubject') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
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

                            @if($emailMode === 'forward' && $selectedEmail && count($selectedEmail['attachments'] ?? []) > 0)
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">📎 Attachments to Forward</label>
                                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white max-h-32 overflow-y-auto divide-y divide-gray-100">
                                    @foreach($selectedEmail['attachments'] as $index => $attachment)
                                    <label class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition-colors group">
                                        <input type="checkbox" wire:model="forwardAttachments" value="{{ $index }}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="ml-3 text-xs font-bold text-gray-800 truncate">{{ $attachment['filename'] }}</span>
                                        <span class="ml-auto text-xs text-gray-400 font-medium">{{ number_format(($attachment['size'] ?? 0) / 1024, 1) }} KB</span>
                                    </label>
                                    @endforeach
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1 ml-1">Semua lampiran secara default akan ikut dikirimkan saat di-forward.</p>
                            </div>
                            @endif

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Message</label>
                                <textarea wire:model="replyBody" rows="10" class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4" placeholder="Type your message here..."></textarea>
                                @error('replyBody') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="px-8 py-5 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                            <button type="button" wire:click="closeReplyModal" class="px-6 py-2.5 text-xs font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-all">Cancel</button>
                            <button type="submit" class="px-8 py-2.5 bg-green-600 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-green-100 hover:bg-green-700 transition-all">
                                @if($emailMode === 'reply') Send Reply @else Send Forward @endif
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