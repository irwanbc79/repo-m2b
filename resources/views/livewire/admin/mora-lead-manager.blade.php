<div class="space-y-6">

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-lg">🔥</div>
            <div>
                <div class="text-2xl font-black text-gray-800">{{ $totalHot }}</div>
                <div class="text-xs text-gray-500 font-medium">Hot Lead Baru</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-lg">📩</div>
            <div>
                <div class="text-2xl font-black text-gray-800">{{ $totalUnread }}</div>
                <div class="text-xs text-gray-500 font-medium">Belum Dibaca</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-lg">📊</div>
            <div>
                <div class="text-2xl font-black text-gray-800">{{ \App\Models\MoraLeadNotification::whereDate('created_at', today())->count() }}</div>
                <div class="text-xs text-gray-500 font-medium">Lead Hari Ini</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-lg">📅</div>
            <div>
                <div class="text-2xl font-black text-gray-800">{{ \App\Models\MoraLeadNotification::whereMonth('created_at', now()->month)->count() }}</div>
                <div class="text-xs text-gray-500 font-medium">Bulan Ini</div>
            </div>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex flex-col md:flex-row gap-3 items-start md:items-center justify-between">
            <div class="flex gap-2 flex-wrap">
                @foreach(['all' => 'Semua', 'unread' => '📩 Belum Dibaca', 'hot' => '🔥 Hot', 'warm' => '⚡ Warm'] as $key => $label)
                <button wire:click="setFilter('{{ $key }}')"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $filter === $key ? 'bg-blue-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <div class="flex gap-2 items-center w-full md:w-auto">
                <input wire:model.live.debounce.300ms="search"
                    type="text" placeholder="Cari nama, perusahaan, nomor..."
                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 w-full md:w-56 focus:outline-none focus:ring-2 focus:ring-blue-300">
                @if($totalUnread > 0)
                <button wire:click="markAllRead"
                    wire:confirm="Tandai semua lead sebagai sudah dibaca?"
                    class="text-xs px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-lg whitespace-nowrap transition">
                    ✓ Baca Semua
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Lead List --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        @if($leads->isEmpty())
            <div class="py-16 text-center text-gray-400">
                <div class="text-4xl mb-3">🤖</div>
                <p class="font-bold text-gray-500">Belum ada lead masuk</p>
                <p class="text-sm mt-1">Lead dari MORA Chat dan CS Form akan muncul di sini</p>
            </div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($leads as $lead)
                <div class="p-4 hover:bg-gray-50 transition {{ $lead->isUnread() ? 'bg-blue-50/40 border-l-4 border-blue-400' : '' }}">
                    <div class="flex items-start gap-4">

                        {{-- Score Badge --}}
                        <div class="shrink-0 mt-1">
                            @if($lead->score === 'hot')
                                <span class="text-xl">🔥</span>
                            @elseif($lead->score === 'warm')
                                <span class="text-xl">⚡</span>
                            @else
                                <span class="text-xl">📩</span>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-gray-900 text-sm">{{ $lead->name }}</span>
                                @if($lead->company)
                                    <span class="text-xs text-gray-500">· {{ $lead->company }}</span>
                                @endif
                                @if($lead->serviceLabel())
                                    <span class="text-[10px] bg-green-100 text-green-700 font-bold px-2 py-0.5 rounded-full">{{ $lead->serviceLabel() }}</span>
                                @endif
                                @if($lead->source === 'mora_chat')
                                    <span class="text-[10px] bg-blue-100 text-blue-600 font-bold px-2 py-0.5 rounded-full">🤖 MORA Chat</span>
                                @else
                                    <span class="text-[10px] bg-gray-100 text-gray-600 font-bold px-2 py-0.5 rounded-full">📋 CS Form</span>
                                @endif
                            </div>

                            @if($lead->summary)
                                <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ \Illuminate\Support\Str::limit($lead->summary, 150) }}</p>
                            @endif

                            <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                                <span>📞 {{ $lead->phone }}</span>
                                @if($lead->email)<span>✉️ {{ $lead->email }}</span>@endif
                                <span>⏱ {{ $lead->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="shrink-0 flex flex-col gap-2 items-end">
                            <a href="{{ $lead->waUrl() }}" target="_blank"
                                class="flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Hubungi
                            </a>
                            @if($lead->isUnread())
                                <button wire:click="markRead({{ $lead->id }})"
                                    class="text-[10px] text-blue-500 hover:text-blue-700 font-bold transition">
                                    ✓ Tandai dibaca
                                </button>
                            @else
                                <span class="text-[10px] text-gray-300">Sudah dibaca</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="px-4 py-3 border-t border-gray-100">
                {{ $leads->links() }}
            </div>
        @endif
    </div>

</div>
