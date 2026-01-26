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
                <span class="flex items-center gap-2 capitalize">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 00-2-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    {{ $acc }}
                </span>
                @php $unread = $this->getUnreadCount($acc); @endphp
                @if($unread > 0)
                <span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">{{ $unread }}</span>
                @endif
            </button>
            @endforeach
        </div>

        {{-- 2. EMAIL LIST --}}
        <div class="w-80 border-r border-gray-200 flex flex-col bg-gray-50/50 shrink-0">
            <div class="p-3 border-b border-gray-100 bg-white">
                <input type="text" placeholder="Search mail..." class="w-full pl-3 pr-4 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500/20">
            </div>
            
            <div class="overflow-y-auto flex-1 divide-y divide-gray-100">
                @forelse($emails as $email)
                <button wire:click="selectEmail({{ $email['db_id'] }})" 
                   class="w-full text-left block p-4 hover:bg-blue-50/50 transition border-l-4 {{ $selectedEmail && $selectedEmail['db_id'] == $email['db_id'] ? 'bg-blue-50 border-l-blue-600' : 'bg-white border-l-transparent' }}">
                    <div class="flex items-start gap-3">
                        <div class="mt-1.5 shrink-0">
                            @if(!$email['is_read'])
                            <span class="block w-2 h-2 bg-blue-600 rounded-full"></span>
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
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                    @endif
                                    <span class="text-[9px] text-gray-400 font-bold uppercase">{{ $email['date'] }}</span>
                                </div>
                            </div>
                            <p class="text-xs truncate text-gray-500">{{ $email['subject'] }}</p>
                        </div>
                    </div>
                </button>
                @empty
                <div class="p-12 text-center text-gray-400 text-sm">Inbox Kosong</div>
                @endforelse
            </div>
        </div>

        {{-- 3. EMAIL CONTENT --}}
        <div class="flex-1 flex flex-col bg-white overflow-hidden shadow-inner">
            @if($selectedEmail)
                <div class="p-8 border-b border-gray-100 bg-white shrink-0 shadow-sm z-10 flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 leading-tight mb-4">{{ $selectedEmail['subject'] }}</h2>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-xl shadow-lg shrink-0">
                                {{ strtoupper(substr($selectedEmail['name'] ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-base font-bold text-gray-900">{{ $selectedEmail['name'] }}</p>
                                <p class="text-xs text-gray-400 italic font-medium tracking-tight">&lt;{{ $selectedEmail['from'] }}&gt; • {{ $selectedEmail['date'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="openReplyModal" class="bg-green-600 text-white px-5 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-green-700 transition shadow-xl shadow-green-100 flex items-center gap-2">
                            ✉️ Reply
                        </button>
                        <button onclick="document.getElementById('convertModal').classList.remove('hidden')" class="bg-blue-600 text-white px-5 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100 flex items-center gap-2">
                            📦 Convert to Shipment
                        </button>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-8 bg-gray-50/30">
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 min-h-full">
                        <div class="text-sm text-gray-700 leading-relaxed prose prose-sm prose-blue max-w-none">
                            {!! $selectedEmail['body'] !!}
                        </div>
                    </div>

                    @if(count($selectedEmail['attachments'] ?? []) > 0)
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-4 flex items-center gap-2">Lampiran ({{ count($selectedEmail['attachments']) }})</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($selectedEmail['attachments'] as $att)
                                @php
                                    // FIX: Menggunakan data_get agar aman membaca Object stdClass maupun Array
                                    $attName = data_get($att, 'name') ?? data_get($att, 'filename', 'Unknown');
                                    $attId = data_get($att, 'id', 0);
                                    $attSize = data_get($att, 'size', 0);
                                    $ext = strtolower(pathinfo($attName, PATHINFO_EXTENSION));
                                    
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isPdf = $ext === 'pdf';
                                    $iconBg = $isImage ? 'bg-emerald-50 text-emerald-600' : ($isPdf ? 'bg-rose-50 text-rose-600' : 'bg-blue-50 text-blue-600');
                                @endphp
                                <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-gray-200 hover:shadow-md transition group">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="{{ $iconBg }} p-2.5 rounded-lg shrink-0"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg></div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-gray-800 truncate">{{ $attName }}</p>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tight">
                                                {{ is_numeric($attSize) ? number_format($attSize / 1024, 1) . ' KB' : $attSize }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($isImage || $isPdf)
                                            <button type="button" 
                                                    onclick="openPreviewModal('{{ route('admin.inbox.attachment', ['mailbox' => $activeAccount, 'id' => $attId]) . '?mode=inline' }}', '{{ addslashes($attName) }}', '{{ $isImage ? 'image' : 'pdf' }}')"
                                                    class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition shadow-sm" title="Preview Lampiran">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.inbox.attachment', ['mailbox' => $activeAccount, 'id' => $attId]) }}" class="p-2 text-slate-300 hover:text-blue-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                    </div>
                                </div>
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
    <div id="convertModal" class="hidden fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('convertModal').classList.add('hidden')"></div>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg sm:w-full relative z-[10000]">
                <div class="bg-white">
                    <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-black text-gray-800 uppercase text-sm tracking-widest">Konversi ke Shipment</h3>
                        <button onclick="document.getElementById('convertModal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition-colors text-2xl leading-none">&times;</button>
                    </div>
                    <form wire:submit.prevent="convertToShipment">
                        <div class="p-8 space-y-6">
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

                            {{-- PILIH DOKUMEN ATTACHMENT --}}
                            <div class="mt-6">
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
                                        <div class="max-h-64 overflow-y-auto">
                                            @foreach($selectedEmail['attachments'] as $index => $attachment)
                                                <label class="flex items-center px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors group">
                                                    <input 
                                                        type="checkbox" 
                                                        wire:model="selectedAttachments" 
                                                        value="{{ $index }}"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                                    >
                                                    <div class="ml-3 flex-1">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-sm font-bold text-gray-800 group-hover:text-blue-600 transition-colors">
                                                                {{ $attachment->filename }}
                                                            </span>
                                                            <span class="text-xs font-semibold text-gray-400 ml-2">
                                                                {{ number_format($attachment->size / 1024, 1) }} KB
                                                            </span>
                                                        </div>
                                                        @if($attachment->mime_type)
                                                            <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                                                                {{ $attachment->mime_type }}
                                                            </span>
                                                        @endif
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
                            </div>
                        </div>
                        <div class="px-8 py-5 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                            <button type="button" onclick="document.getElementById('convertModal').classList.add('hidden')" class="px-6 py-2.5 text-xs font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-all">Batal</button>
                            <button type="submit" class="px-8 py-2.5 bg-blue-600 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">Create Shipment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- REPLY MODAL --}}
    @if($showReplyModal)
    <div class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="closeReplyModal"></div>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-2xl sm:w-full relative z-[10000]">
                <div class="bg-white">
                    <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-black text-gray-800 uppercase text-sm tracking-widest">✉️ Reply Email</h3>
                        <button wire:click="closeReplyModal" class="text-gray-400 hover:text-red-500 transition-colors text-2xl leading-none">&times;</button>
                    </div>
                    <form wire:submit.prevent="sendReply">
                        <div class="p-8 space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">To</label>
                                <input type="email" wire:model="replyTo" readonly class="w-full border-gray-200 rounded-xl text-sm font-bold bg-gray-50 py-3 px-4">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Subject</label>
                                <input type="text" wire:model="replySubject" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-3 px-4">
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

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Message</label>
                                <textarea wire:model="replyBody" rows="10" class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4" placeholder="Type your reply here..."></textarea>
                                @error('replyBody') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="px-8 py-5 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                            <button type="button" wire:click="closeReplyModal" class="px-6 py-2.5 text-xs font-black text-gray-400 uppercase tracking-widest hover:text-gray-600 transition-all">Cancel</button>
                            <button type="submit" class="px-8 py-2.5 bg-green-600 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-green-100 hover:bg-green-700 transition-all">Send Reply</button>
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
</script>