@php
    // Nada warna lencana status — dipetakan sekali di sini supaya tabel di
    // bawah tetap enak dibaca.
    $tone = [
        'ok'   => 'bg-emerald-50 text-emerald-700',
        'info' => 'bg-blue-50 text-blue-700',
        'warn' => 'bg-amber-50 text-amber-700',
        'crit' => 'bg-red-50 text-red-700',
        'mute' => 'bg-gray-100 text-gray-500',
    ];
@endphp

<div class="space-y-6">
    @section('header', 'Email Keluar')

    {{-- RINGKASAN 30 HARI --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Terkirim 30 Hari</p>
            <p class="text-xl font-black text-gray-900 mt-1 tabular-nums">{{ number_format($ringkas['total'], 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded-xl shadow border p-5 {{ $ringkas['gagal'] > 0 ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
            <p class="text-[11px] font-bold uppercase tracking-wider {{ $ringkas['gagal'] > 0 ? 'text-red-500' : 'text-gray-400' }}">Mental / Gagal</p>
            <p class="text-xl font-black mt-1 tabular-nums {{ $ringkas['gagal'] > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $ringkas['gagal'] }}</p>
            @if ($ringkas['gagal'] > 0)
                <p class="text-[11px] text-red-500 mt-0.5">Alamat customer perlu diperbaiki</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Berhasil Sampai</p>
            <p class="text-xl font-black text-gray-900 mt-1 tabular-nums">
                {{ $ringkas['rasio_sampai'] !== null ? number_format($ringkas['rasio_sampai'], 1, ',', '.') . '%' : '—' }}
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">Di bawah 95% = reputasi domain bermasalah</p>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Dibuka Penerima</p>
            <p class="text-xl font-black text-gray-900 mt-1 tabular-nums">
                {{ $ringkas['rasio_dibuka'] !== null ? number_format($ringkas['rasio_dibuka'], 1, ',', '.') . '%' : '—' }}
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">Dari yang berhasil sampai</p>
        </div>
    </div>

    {{-- PERINGATAN: email yang tak pernah dikabarkan nasibnya. Pola kegagalan
         senyap seperti ini pernah menggigit portal di pembukuan kas. --}}
    @if ($ringkas['mangkrak'] > 0)
        <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 flex flex-wrap items-center gap-3">
            <span class="text-amber-700 text-sm font-bold">
                ⚠️ {{ $ringkas['mangkrak'] }} email belum dikabarkan nasibnya lebih dari 1 jam.
            </span>
            <span class="text-amber-600 text-xs">
                Portal mencatat niat kirim tapi tidak ada konfirmasi apa pun — periksa sisa kredit &amp; kredensial Kirim Email.
            </span>
            <button wire:click="setKondisi('stuck')"
                class="ml-auto px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-600 text-white hover:bg-amber-700">
                Lihat daftarnya
            </button>
        </div>
    @endif

    {{-- PENYARING --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 p-4 flex flex-wrap items-center gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari penerima / subjek…"
            class="flex-1 min-w-[200px] border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">

        <div class="flex flex-wrap gap-1.5">
            @foreach (['all' => 'Semua', 'invoice' => 'Invoice', 'quotation' => 'Quotation', 'shipment' => 'Shipment', 'sistem' => 'Sistem'] as $key => $label)
                <button wire:click="setJenis('{{ $key }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $jenis === $key ? 'bg-blue-900 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <span class="w-px h-5 bg-gray-200"></span>

        <div class="flex flex-wrap gap-1.5">
            @foreach (['all' => 'Semua kondisi', 'bounced' => 'Mental', 'unopened' => 'Belum dibuka', 'opened' => 'Sudah dibuka', 'stuck' => 'Mangkrak'] as $key => $label)
                <button wire:click="setKondisi('{{ $key }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $kondisi === $key ? 'bg-blue-900 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- DAFTAR --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[880px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-[11px] uppercase tracking-wider text-gray-400">
                        <th class="text-left font-bold px-4 py-3">Jenis</th>
                        <th class="text-left font-bold px-4 py-3">Penerima</th>
                        <th class="text-left font-bold px-4 py-3">Subjek / Dokumen</th>
                        <th class="text-left font-bold px-4 py-3">Status</th>
                        <th class="text-left font-bold px-4 py-3">Dikirim</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">
                                    {{ $item->jenisLabel() }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-gray-800">{{ $item->recipient_email }}</span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-gray-800">{{ $item->subject ?: '—' }}</span>
                                @if ($item->relatedLabel())
                                    <span class="block text-[11px] font-bold text-blue-900 mt-0.5 tabular-nums">
                                        {{ $item->relatedLabel() }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $tone[$item->statusTone()] }}">
                                    {{ $item->statusLabel() }}
                                </span>
                                @if ($item->failure_reason)
                                    <span class="block text-[11px] text-red-500 mt-0.5">
                                        {{ Str::limit($item->failure_reason, 60) }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-[12px] text-gray-500 tabular-nums whitespace-nowrap">
                                {{ optional($item->sent_at)->format('d M H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm font-medium">Belum ada email yang cocok dengan penyaring ini.</p>
                                <p class="text-gray-400 text-xs mt-1">
                                    Pencatatan dimulai sejak fitur ini aktif — email yang dikirim sebelumnya tidak ikut terdata.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
