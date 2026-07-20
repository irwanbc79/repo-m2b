<div class="max-w-2xl mx-auto space-y-6">
    @section('header', 'Testimoni')

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-xl flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Hero --}}
    <div class="bg-gradient-to-br from-m2b-primary to-blue-900 rounded-2xl shadow-sm p-8 text-white text-center">
        <div class="text-4xl mb-2">⭐</div>
        <h2 class="text-2xl font-black">Bagikan Pengalaman Anda</h2>
        <p class="text-blue-100 text-sm mt-1 max-w-md mx-auto">Ceritakan pengalaman Anda bekerja sama dengan M2B. Testimoni Anda membantu eksportir/importir lain — dan bisa tampil di halaman utama kami.</p>
    </div>

    @if($state === 'locked')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
            <div class="text-3xl mb-2">🚢</div>
            <h3 class="font-bold text-gray-800">Belum bisa mengisi testimoni</h3>
            <p class="text-gray-500 text-sm mt-1">Testimoni bisa diisi setelah Anda memiliki minimal satu pengiriman yang <strong>selesai</strong> bersama M2B. Terima kasih atas kepercayaan Anda!</p>
            <a href="{{ route('customer.shipments.index') }}" class="inline-block mt-4 text-sm font-bold text-m2b-primary hover:underline">Lihat pengiriman saya →</a>
        </div>

    @elseif($state === 'approved')
        <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-8 text-center">
            <div class="text-3xl mb-2">🎉</div>
            <h3 class="font-bold text-gray-800">Testimoni Anda sudah tayang!</h3>
            <p class="text-gray-500 text-sm mt-1">Terima kasih banyak — testimoni Anda telah disetujui dan tampil di halaman publik M2B.</p>
            <div class="mt-4 bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-left">
                <div class="flex gap-0.5 mb-1">@for($i=1;$i<=5;$i++)<span class="{{ $i <= $rating ? 'text-yellow-400' : 'text-gray-200' }}">★</span>@endfor</div>
                <p class="text-sm text-gray-700 italic">"{{ $content }}"</p>
                <p class="text-xs text-gray-400 mt-2">— {{ $display_name }}{{ $company_name ? ', '.$company_name : '' }}</p>
            </div>
        </div>

    @elseif($state === 'review')
        <div class="bg-white rounded-2xl shadow-sm border border-blue-100 p-8 text-center">
            <div class="text-3xl mb-2">⏳</div>
            <h3 class="font-bold text-gray-800">Sedang ditinjau tim M2B</h3>
            <p class="text-gray-500 text-sm mt-1">Testimoni Anda sudah kami terima dan sedang ditinjau. Setelah disetujui, testimoni akan tampil di halaman publik. Terima kasih! 🙏</p>
            <div class="mt-4 bg-blue-50 border border-blue-100 rounded-xl p-4 text-left">
                <div class="flex gap-0.5 mb-1">@for($i=1;$i<=5;$i++)<span class="{{ $i <= $rating ? 'text-yellow-400' : 'text-gray-200' }}">★</span>@endfor</div>
                <p class="text-sm text-gray-700 italic">"{{ $content }}"</p>
            </div>
        </div>

    @else
        {{-- FORM --}}
        <form wire:submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 space-y-5">
            {{-- Rating bintang interaktif --}}
            <div x-data="{ rating: @entangle('rating'), hover: 0 }" class="text-center">
                <label class="block text-sm font-bold text-gray-700 mb-2">Seberapa puas Anda?</label>
                <div class="flex justify-center gap-1.5" @mouseleave="hover = 0">
                    @for($i=1;$i<=5;$i++)
                        <button type="button" wire:key="star-{{ $i }}"
                                @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}"
                                class="text-4xl transition-transform hover:scale-110 focus:outline-none"
                                :class="(hover || rating) >= {{ $i }} ? 'text-yellow-400' : 'text-gray-200'">★</button>
                    @endfor
                </div>
                <p class="text-xs text-gray-400 mt-1" x-text="['','Kurang','Cukup','Baik','Sangat Baik','Luar Biasa'][hover || rating]"></p>
                @error('rating')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Testimoni Anda</label>
                <textarea wire:model="content" rows="4" maxlength="1000"
                          class="w-full border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Ceritakan pengalaman Anda: ketepatan waktu, kejelasan biaya, penanganan dokumen/kepabeanan, pelayanan tim, dll."></textarea>
                <div class="flex justify-between mt-1">
                    @error('content')<span class="text-xs text-red-500">{{ $message }}</span>@else<span class="text-[11px] text-gray-400">Minimal 10 karakter — jujur & apa adanya.</span>@enderror
                    <span class="text-[11px] text-gray-400">{{ strlen($content) }}/1000</span>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Anda</label>
                    <input wire:model="display_name" class="w-full border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nama PIC">
                    @error('display_name')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jabatan (opsional)</label>
                    <input wire:model="position" class="w-full border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="mis. Direktur, Manajer Logistik">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Perusahaan</label>
                <input wire:model="company_name" class="w-full border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nama perusahaan">
            </div>

            <div class="flex items-center justify-between pt-2">
                <p class="text-[11px] text-gray-400 max-w-xs">Dengan mengirim, Anda setuju testimoni ditampilkan publik setelah ditinjau tim M2B.</p>
                <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                        class="bg-m2b-primary hover:bg-blue-900 text-white font-bold px-6 py-2.5 rounded-xl shadow-md transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Kirim Testimoni</span>
                    <span wire:loading wire:target="submit">Mengirim…</span>
                </button>
            </div>
        </form>
    @endif
</div>
