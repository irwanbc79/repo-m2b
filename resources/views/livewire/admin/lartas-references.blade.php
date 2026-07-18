<div class="container mx-auto px-4 py-8 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">🧭 Referensi Lartas <span class="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full uppercase tracking-wide">Basis Data INSW</span></h1>
            <p class="text-gray-500 text-sm">Data izin/lartas otoritatif per HS (impor & ekspor). Jadi acuan di semua shipment — mengalahkan perkiraan AI.</p>
        </div>
        <button wire:click="newReference" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-bold shadow-sm transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Referensi
        </button>
    </div>

    @if(session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">✓ {{ session('message') }}</div>
    @endif

    {{-- HS sering muncul tapi belum direkam --}}
    @if($this->unrecorded->isNotEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <p class="text-sm font-bold text-amber-800 mb-2">⚡ Sering muncul di shipment tapi belum direkam — prioritaskan:</p>
        <div class="flex flex-wrap gap-2">
            @foreach($this->unrecorded as $row)
                <button wire:click="newReference('{{ $row->hs_code }}', '{{ $row->flow }}')"
                    class="inline-flex items-center gap-1.5 bg-white border border-amber-200 hover:border-amber-400 rounded-lg px-2.5 py-1.5 text-xs transition">
                    <span class="font-mono font-bold text-gray-700">{{ $row->hs_code }}</span>
                    <span class="text-[10px] text-gray-400 uppercase">{{ $row->flow }}</span>
                    <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 rounded-full">{{ $row->jml }}×</span>
                    <span class="text-emerald-600 font-bold">+ rekam</span>
                </button>
            @endforeach
        </div>
    </div>
    @endif

    @if($this->staleCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800 flex items-center gap-2 flex-wrap">
        ⏳ <strong>{{ $this->staleCount }} referensi</strong> berumur > {{ \App\Models\LartasReference::STALE_DAYS }} hari — peraturan bisa berubah (bebas↔wajib). Sebaiknya ditinjau ulang ke INSW.
        <button wire:click="$set('onlyStale', true)" class="text-xs font-bold text-amber-900 underline">Tampilkan yang perlu ditinjau</button>
    </div>
    @endif

    {{-- Search --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 flex-wrap">
        <div class="relative flex-1 min-w-[200px]">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari HS code, nama izin, atau komoditi…" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-emerald-500 transition">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap">
            <input type="checkbox" wire:model.live="onlyStale" class="rounded border-gray-300 text-amber-600">
            Hanya yang perlu ditinjau
        </label>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-xs border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3">HS Code</th>
                        <th class="px-5 py-3">Arah</th>
                        <th class="px-5 py-3">Status / Izin</th>
                        <th class="px-5 py-3">Dokumen</th>
                        <th class="px-5 py-3">Diperbarui</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($refs as $r)
                    <tr class="hover:bg-emerald-50/40 transition">
                        <td class="px-5 py-3 font-mono font-bold text-gray-800">{{ $r->hs_code }}</td>
                        <td class="px-5 py-3"><span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $r->trade_flow==='export' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">{{ $r->trade_flow }}</span></td>
                        <td class="px-5 py-3">
                            @if($r->is_free)
                                <span class="text-[11px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">✓ Bebas lartas</span>
                            @else
                                <div class="font-semibold text-gray-800">{{ $r->izin_names ?: '—' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $r->izin_code ? 'Kode '.$r->izin_code : '' }}{{ $r->komoditi_group ? ' · '.$r->komoditi_group : '' }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if(!empty($r->doc_types))
                                <div class="flex flex-wrap gap-1">@foreach($r->doc_types as $dt)<span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ $dt }}</span>@endforeach</div>
                            @else <span class="text-gray-300">—</span> @endif
                        </td>
                        <td class="px-5 py-3 text-xs">
                            <div class="text-gray-500">{{ optional($r->checked_at)->format('d M Y') ?: '—' }}</div>
                            @if($r->isStale())
                                <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full">⏳ perlu ditinjau</span>
                            @else
                                <span class="text-[10px] text-emerald-600">{{ $r->ageDays() }} hari lalu</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            @if($r->isStale())<button wire:click="refreshChecked({{ $r->id }})" class="text-xs font-bold text-emerald-600 hover:underline" title="Sudah saya cek ulang di INSW, masih berlaku">✓ Dicek ulang</button>@endif
                            <button wire:click="edit({{ $r->id }})" class="text-xs font-bold text-blue-600 hover:underline ml-2">Edit</button>
                            <button wire:click="delete({{ $r->id }})" wire:confirm="Hapus referensi HS {{ $r->hs_code }}?" class="text-xs font-bold text-red-500 hover:underline ml-2">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400 text-sm italic">Belum ada referensi. Klik "Tambah Referensi" atau pilih dari daftar prioritas di atas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 bg-gray-50">{{ $refs->links() }}</div>
    </div>

    {{-- MODAL FORM --}}
    @if($showForm)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="$set('showForm', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" style="position:relative; z-index:10;">
            <h3 class="font-black text-gray-800 mb-1">{{ $editingId ? 'Edit' : 'Tambah' }} Referensi Lartas</h3>
            <p class="text-xs text-gray-500 mb-4">Salin dari <a href="https://insw.go.id/intr" target="_blank" rel="noopener" class="text-blue-600 underline">insw.go.id/intr</a>. Perhatikan arah (impor = BC 2.0/PIB, ekspor = BC 3.0/PEB) — lartas bisa beda per arah.</p>
            <form wire:submit.prevent="save" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-[11px] font-bold text-gray-500 uppercase">HS Code</label><input wire:model="f_hs_code" class="w-full border-gray-200 rounded-lg text-sm mt-1 font-mono" placeholder="23066010">@error('f_hs_code')<span class="text-[10px] text-red-500">{{ $message }}</span>@enderror</div>
                    <div><label class="text-[11px] font-bold text-gray-500 uppercase">Arah</label>
                        <select wire:model="f_trade_flow" class="w-full border-gray-200 rounded-lg text-sm mt-1">
                            <option value="import">Impor (BC 2.0 / PIB)</option>
                            <option value="export">Ekspor (BC 3.0 / PEB)</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2">
                    <input type="checkbox" wire:model.live="f_is_free" class="rounded border-gray-300 text-emerald-600">
                    <span class="font-semibold text-emerald-700">Barang BEBAS lartas (tidak ada izin)</span>
                </label>
                @unless($f_is_free)
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-[11px] font-bold text-gray-500 uppercase">Nama Izin</label><input wire:model="f_izin_names" class="w-full border-gray-200 rounded-lg text-sm mt-1" placeholder="KT.2, KT.9, SP-5..."></div>
                    <div><label class="text-[11px] font-bold text-gray-500 uppercase">Kode Izin</label><input wire:model="f_izin_code" class="w-full border-gray-200 rounded-lg text-sm mt-1" placeholder="940"></div>
                </div>
                <div><label class="text-[11px] font-bold text-gray-500 uppercase">Komoditi</label><input wire:model="f_komoditi_group" class="w-full border-gray-200 rounded-lg text-sm mt-1" placeholder="Tumbuhan"></div>
                <div><label class="text-[11px] font-bold text-gray-500 uppercase">Regulasi</label><input wire:model="f_regulation" class="w-full border-gray-200 rounded-lg text-sm mt-1" placeholder="PP 14 Tahun 2002..."></div>
                <div>
                    <label class="text-[11px] font-bold text-gray-500 uppercase">Dokumen wajib (petakan ke katalog)</label>
                    <div class="mt-1 grid grid-cols-2 gap-1.5 max-h-40 overflow-y-auto border border-gray-100 rounded-lg p-2">
                        @foreach($this->docOptions as $opt)
                            <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" wire:model="f_doc_types" value="{{ $opt }}" class="rounded border-gray-300 text-emerald-600"><span>{{ $opt }}</span></label>
                        @endforeach
                    </div>
                </div>
                @endunless
                <div><label class="text-[11px] font-bold text-gray-500 uppercase">Catatan/Deskripsi</label><textarea wire:model="f_description" rows="2" class="w-full border-gray-200 rounded-lg text-sm mt-1"></textarea></div>
                <div class="flex gap-2 justify-end pt-2">
                    <button type="button" wire:click="$set('showForm', false)" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
