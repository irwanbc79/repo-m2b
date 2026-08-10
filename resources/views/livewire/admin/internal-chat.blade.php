{{--
    Tombol mengambang chat internal.

    z-index 9000 — sengaja DI BAWAH penampil dokumen (9999) supaya tombol ini
    tidak pernah menutupi modal yang sedang dibuka staf.

    Denyut polling melambat saat tertutup; Livewire sendiri sudah berhenti
    memanggil saat tab tidak aktif.
--}}
<div @if($aktif) wire:poll.{{ $terbuka ? '10s' : '60s' }}="denyut" @endif>
    @if($aktif)

    {{-- ══════════ PANEL ══════════ --}}
    @if($terbuka)
    {{-- Posisi ditulis inline, BUKAN lewat class Tailwind: build v4 di project
         ini tidak meng-generate bottom-24/right-6 (terverifikasi nol di
         public/build), sehingga panel akan melayang tanpa posisi. --}}
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 flex flex-col"
         style="position: fixed; bottom: 6rem; right: 1.5rem; z-index: 9000;
                width: 22rem; max-width: calc(100vw - 3rem);
                height: 30rem; max-height: calc(100vh - 8rem);">

        {{-- Kepala + pemilih penerima --}}
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-900 rounded-t-xl">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-white">💬 Chat Internal</span>
                <button wire:click="toggle" class="text-gray-400 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <div class="mt-2 flex gap-1 overflow-x-auto pb-1">
                <button wire:click="pilihLawan"
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap transition
                    {{ $lawan === null ? 'bg-white text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    Semua
                </button>

                @foreach($peserta as $p)
                <button wire:click="pilihLawan({{ $p['id'] }})"
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap transition flex items-center gap-1
                    {{ $lawan === $p['id'] ? 'bg-white text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    {{ Str::limit($p['name'], 14) }}
                    @if($p['unread'] > 0)
                        <span class="min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-black rounded-full flex items-center justify-center">
                            {{ $p['unread'] > 9 ? '9+' : $p['unread'] }}
                        </span>
                    @endif
                </button>
                @endforeach
            </div>
        </div>

        {{-- Daftar pesan --}}
        <div class="flex-1 overflow-y-auto px-3 py-3 space-y-2 bg-gray-50" id="m2b-chat-scroll">
            @forelse($pesan as $m)
                @php($milikSaya = (int) $m->sender_id === (int) auth()->id())
                <div class="flex {{ $milikSaya ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-lg px-3 py-2 {{ $milikSaya ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-800' }}">
                        @unless($milikSaya)
                            <p class="text-[10px] font-bold text-gray-500 mb-0.5">{{ $m->sender_name }}</p>
                        @endunless
                        @if($m->punyaLampiran())
                            <a href="{{ route('admin.chat.lampiran', $m->id) }}" target="_blank" rel="noopener"
                               class="block mb-1 rounded overflow-hidden">
                                @if($m->lampiranGambar())
                                    {{-- onload menggulir ulang: gambar selesai dimuat SETELAH
                                         livewire:updated, jadi tanpa ini tampilan berhenti di
                                         tengah dan pesan terbaru tidak terlihat. --}}
                                    <img src="{{ route('admin.chat.lampiran', $m->id) }}"
                                         alt="{{ $m->attachment_name }}"
                                         onload="this.closest('#m2b-chat-scroll')?.scrollTo(0, 1e6)"
                                         class="rounded max-h-40 w-auto" loading="lazy">
                                @else
                                    <span class="flex items-center gap-2 px-2 py-1.5 rounded {{ $milikSaya ? 'bg-blue-700' : 'bg-gray-100' }}">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        <span class="text-xs truncate">{{ Str::limit($m->attachment_name, 22) }}</span>
                                        <span class="text-[10px] opacity-70 shrink-0">{{ $m->ukuranTerbaca() }}</span>
                                    </span>
                                @endif
                            </a>
                        @endif

                        @if(filled($m->body))
                            <p class="text-sm whitespace-pre-wrap break-words">{{ $m->body }}</p>
                        @endif

                        <p class="text-[10px] mt-0.5 {{ $milikSaya ? 'text-blue-200' : 'text-gray-400' }}">
                            {{ $m->created_at->format('d/m H:i') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="h-full flex items-center justify-center text-center px-4">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">
                            {{ $lawan === null ? 'Belum ada obrolan.' : 'Belum ada percakapan dengan orang ini.' }}
                        </p>
                        <p class="text-[11px] text-gray-400 mt-1">Pesan dihapus otomatis setelah 90 hari.</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Kotak ketik --}}
        <form wire:submit.prevent="kirim" class="p-3 border-t border-gray-200 bg-white rounded-b-xl">
            @error('isi') <p class="text-[11px] text-red-600 mb-1">{{ $message }}</p> @enderror
            @error('berkas') <p class="text-[11px] text-red-600 mb-1">{{ $message }}</p> @enderror

            {{-- Pratinjau file terpilih, sebelum dikirim --}}
            @if($berkas)
            <div class="flex items-center gap-2 mb-2 px-2 py-1.5 bg-blue-50 border border-blue-200 rounded-lg">
                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span class="text-[11px] text-blue-900 truncate flex-1">{{ $berkas->getClientOriginalName() }}</span>
                <button type="button" wire:click="batalBerkas" class="text-blue-400 hover:text-red-600 text-sm leading-none">&times;</button>
            </div>
            @endif

            <div wire:loading wire:target="berkas" class="text-[11px] text-gray-500 mb-1">Mengunggah…</div>

            <div class="flex gap-2 items-center">
                {{-- Tombol klip: input file disembunyikan, labelnya yang diklik --}}
                <label class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-blue-600 cursor-pointer"
                       title="Lampirkan gambar atau PDF (maks 5 MB gambar / 10 MB PDF)">
                    <input type="file" wire:model="berkas" class="hidden"
                           accept="image/jpeg,image/png,image/webp,image/gif,application/pdf">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </label>

                <input type="text" wire:model="isi" maxlength="2000"
                    placeholder="{{ $lawan === null ? 'Pesan ke semua…' : 'Pesan japri…' }}"
                    class="flex-1 min-w-0 border-gray-300 rounded-lg text-sm py-2">

                <button type="submit" wire:loading.attr="disabled" wire:target="berkas"
                    class="shrink-0 px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 disabled:opacity-50">
                    Kirim
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- ══════════ TOMBOL MENGAMBANG ══════════ --}}
    <button wire:click="toggle"
        class="w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-2xl flex items-center justify-center transition"
        style="position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9000;"
        title="Chat Internal">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>

        @if($total > 0 && ! $terbuka)
            {{-- Posisi lencana juga inline, alasan sama dengan tombolnya. --}}
            <span class="min-w-6 h-6 px-1.5 bg-red-500 text-white text-[11px] font-black rounded-full flex items-center justify-center border-2 border-white animate-pulse"
                  style="position: absolute; top: -0.25rem; right: -0.25rem;">
                {{ $total > 9 ? '9+' : $total }}
            </span>
        @endif
    </button>

    {{-- Gulir ke pesan terbaru setiap panel diperbarui. --}}
    <script>
        document.addEventListener('livewire:updated', () => {
            const el = document.getElementById('m2b-chat-scroll');
            if (el) el.scrollTop = el.scrollHeight;
        });
    </script>

    @endif
</div>
