<div class="space-y-4" wire:key="customer-messages">
    @section('header', 'Pesan Customer')

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">💬 Pesan Customer</h1>
            <p class="text-sm text-gray-500">Pertanyaan customer tentang shipment mereka.</p>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model.live="onlyUnread" class="rounded border-gray-300 text-blue-600">
            Hanya belum dibaca
        </label>
    </div>

    @if (session()->has('reply_sent'))
    <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
        <span>✅</span> {{ session('reply_sent') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Daftar percakapan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-1">
            <div class="divide-y divide-gray-100 max-h-[70vh] overflow-y-auto">
                @forelse($threads as $t)
                <button wire:click="selectThread({{ $t->id }})"
                    class="w-full text-left px-4 py-3 hover:bg-blue-50 transition flex items-start gap-3 {{ $selectedShipmentId === $t->id ? 'bg-blue-50 border-l-4 border-blue-600' : '' }}">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold text-gray-800 text-sm truncate">{{ $t->customer->company_name ?? 'N/A' }}</p>
                            @if($t->unread_count > 0)
                            <span class="shrink-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $t->unread_count }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-blue-600 font-mono">{{ $t->awb_number ?? ('SHIP-' . $t->id) }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ \Illuminate\Support\Carbon::parse($t->messages_max_created_at)->diffForHumans() }}</p>
                    </div>
                </button>
                @empty
                <div class="px-4 py-12 text-center text-gray-400">
                    <div class="text-3xl mb-2">📭</div>
                    <p class="text-sm">Belum ada pesan customer.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Thread terpilih --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-2 flex flex-col">
            @if($selected)
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <div>
                    <p class="font-bold text-gray-800 text-sm">{{ $selected->customer->company_name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">
                        <span class="font-mono text-blue-600">{{ $selected->awb_number ?? ('SHIP-' . $selected->id) }}</span>
                        • {{ $selected->origin }} → {{ $selected->destination }}
                    </p>
                </div>
                <a href="{{ route('admin.shipments.show', $selected->id) }}" class="text-xs font-semibold text-blue-600 hover:underline">Buka shipment →</a>
            </div>

            <div class="p-5 space-y-3 flex-1 overflow-y-auto max-h-[55vh]">
                @foreach($selected->messages as $msg)
                <div class="flex {{ $msg->sender_type === 'admin' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%]">
                        <div class="rounded-2xl px-4 py-2.5 text-sm {{ $msg->sender_type === 'admin' ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm' }}">
                            {!! nl2br(e($msg->body)) !!}
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 {{ $msg->sender_type === 'admin' ? 'text-right' : 'text-left' }}">
                            {{ $msg->sender_type === 'admin' ? ('🏢 ' . ($msg->sender->name ?? 'M2B')) : 'Customer' }} • {{ $msg->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>

            <form wire:submit.prevent="sendReply" class="p-4 border-t border-gray-100 bg-gray-50 flex items-end gap-2">
                <div class="flex-1">
                    <textarea wire:model="reply" rows="2" placeholder="Tulis balasan ke customer…"
                        class="w-full border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    @error('reply') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="sendReply"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl transition shrink-0 disabled:opacity-50">
                    Balas
                </button>
            </form>
            @else
            <div class="flex-1 flex items-center justify-center p-12 text-center text-gray-400">
                <div>
                    <div class="text-4xl mb-3">💬</div>
                    <p class="text-sm">Pilih percakapan di kiri untuk membaca & membalas.</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
