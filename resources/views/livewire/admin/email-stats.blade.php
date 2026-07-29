@php
    // Ubah menit jadi bentuk yang enak dibaca staf ("4j 20m", bukan "260").
    $formatMenit = function (?float $menit) {
        if ($menit === null) return null;
        if ($menit < 60) return round($menit) . ' menit';
        $jam = floor($menit / 60);
        $sisa = round($menit - ($jam * 60));
        return $sisa > 0 ? "{$jam}j {$sisa}m" : "{$jam} jam";
    };
@endphp

<div class="space-y-6">
    @section('header', 'Statistik Email')

    {{-- PERIODE --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Periode</span>
        @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $hari => $label)
            <button wire:click="setPeriode({{ $hari }})"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $periode === $hari ? 'bg-blue-900 text-white' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
        <a href="{{ route('admin.email-keluar') }}" class="ml-auto text-xs font-bold text-blue-900 hover:underline">
            Lihat daftar email keluar →
        </a>
    </div>

    {{-- ══════════ KESEHATAN KANAL ══════════ --}}
    <div>
        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Kesehatan Kanal</p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Email Keluar</p>
                <p class="text-2xl font-black text-gray-900 mt-1 tabular-nums">{{ number_format($kanal['total_keluar'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $periode }} hari terakhir</p>
            </div>

            <div class="bg-white rounded-xl shadow border p-5 {{ $kanal['mental'] > 0 ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider {{ $kanal['mental'] > 0 ? 'text-red-500' : 'text-gray-400' }}">Mental / Gagal</p>
                <p class="text-2xl font-black mt-1 tabular-nums {{ $kanal['mental'] > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $kanal['mental'] }}</p>
                <p class="text-[11px] mt-0.5 {{ $kanal['mental'] > 0 ? 'text-red-500' : 'text-gray-400' }}">
                    {{ $kanal['mental'] > 0 ? 'Alamat customer perlu diperbaiki' : 'Semua alamat valid' }}
                </p>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Berhasil Sampai</p>
                <p class="text-2xl font-black mt-1 tabular-nums {{ $kanal['rasio_sampai'] !== null && $kanal['rasio_sampai'] < 95 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ $kanal['rasio_sampai'] !== null ? number_format($kanal['rasio_sampai'], 1, ',', '.') . '%' : '—' }}
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">Di bawah 95% = reputasi domain bermasalah</p>
            </div>

            <div class="bg-white rounded-xl shadow border p-5 {{ $kanal['mangkrak'] > 0 ? 'border-amber-300 bg-amber-50' : 'border-gray-200' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider {{ $kanal['mangkrak'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">Mangkrak</p>
                <p class="text-2xl font-black mt-1 tabular-nums {{ $kanal['mangkrak'] > 0 ? 'text-amber-700' : 'text-gray-900' }}">{{ $kanal['mangkrak'] }}</p>
                <p class="text-[11px] mt-0.5 {{ $kanal['mangkrak'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">Tak ada kabar &gt; 1 jam</p>
            </div>
        </div>
    </div>

    {{-- ══════════ OPERASIONAL ══════════ --}}
    <div>
        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Operasional Harian</p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Email Masuk</p>
                <p class="text-2xl font-black text-gray-900 mt-1 tabular-nums">{{ number_format($operasional['masuk_periode'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $periode }} hari terakhir</p>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Belum Dibalas</p>
                <p class="text-2xl font-black text-gray-900 mt-1 tabular-nums">{{ $operasional['belum_dibalas'] }}</p>
                @if (!empty($operasional['per_akun']))
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        @foreach ($operasional['per_akun'] as $akun => $jumlah)
                            {{ $akun }} {{ $jumlah }}@if (!$loop->last) · @endif
                        @endforeach
                    </p>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Rata-rata Waktu Balas</p>
                <p class="text-2xl font-black mt-1 tabular-nums {{ $operasional['menit_balas'] !== null && $operasional['menit_balas'] > 120 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ $formatMenit($operasional['menit_balas']) ?? '—' }}
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">Target wajar forwarding: di bawah 2 jam</p>
            </div>

            <div class="bg-white rounded-xl shadow border p-5 {{ $operasional['menggantung'] > 0 ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider {{ $operasional['menggantung'] > 0 ? 'text-red-500' : 'text-gray-400' }}">Menggantung &gt; 24 Jam</p>
                <p class="text-2xl font-black mt-1 tabular-nums {{ $operasional['menggantung'] > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $operasional['menggantung'] }}</p>
                <p class="text-[11px] mt-0.5 {{ $operasional['menggantung'] > 0 ? 'text-red-500' : 'text-gray-400' }}">Kandidat jadi keluhan</p>
            </div>
        </div>
    </div>

    {{-- ══════════ CORONG BISNIS ══════════ --}}
    <div>
        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Corong Bisnis</p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Invoice Terkirim</p>
                <p class="text-2xl font-black text-gray-900 mt-1 tabular-nums">{{ $corong['invoice_terkirim'] }}</p>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Invoice Dibuka</p>
                <p class="text-2xl font-black text-gray-900 mt-1 tabular-nums">
                    {{ $corong['rasio_invoice_dibuka'] !== null ? number_format($corong['rasio_invoice_dibuka'], 1, ',', '.') . '%' : '—' }}
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">Sisanya jangan ditunggu — telepon</p>
            </div>

            <div class="bg-white rounded-xl shadow border p-5 {{ $corong['quotation_panas'] > 0 ? 'border-emerald-300 bg-emerald-50' : 'border-gray-200' }}">
                <p class="text-[11px] font-bold uppercase tracking-wider {{ $corong['quotation_panas'] > 0 ? 'text-emerald-600' : 'text-gray-400' }}">Quotation Panas</p>
                <p class="text-2xl font-black mt-1 tabular-nums {{ $corong['quotation_panas'] > 0 ? 'text-emerald-700' : 'text-gray-900' }}">{{ $corong['quotation_panas'] }}</p>
                <p class="text-[11px] mt-0.5 {{ $corong['quotation_panas'] > 0 ? 'text-emerald-600' : 'text-gray-400' }}">Dibuka ≥3× tanpa balasan</p>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Sampai Tapi Diabaikan</p>
                <p class="text-2xl font-black text-gray-900 mt-1 tabular-nums">{{ $corong['terkirim_belum_dibuka'] }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">Susul lewat telepon atau WhatsApp</p>
            </div>
        </div>
    </div>

    {{-- ══════════ PERLU TINDAKAN ══════════ --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-bold text-gray-800">Perlu Tindakan</h3>
            <p class="text-[11px] text-gray-400 mt-0.5">Angka di atas bermuara ke sini — supaya tidak berhenti sebagai statistik.</p>
        </div>

        @php
            $adaTindakan = $perluTindakan['mental']->isNotEmpty()
                || $perluTindakan['menggantung']->isNotEmpty()
                || $perluTindakan['mangkrak']->isNotEmpty();
        @endphp

        @if (! $adaTindakan)
            <div class="px-4 py-10 text-center">
                <p class="text-emerald-700 text-sm font-bold">Tidak ada yang perlu ditindaklanjuti.</p>
                <p class="text-gray-400 text-xs mt-1">Tidak ada email mental, mangkrak, atau menggantung lebih dari 24 jam.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach ($perluTindakan['mental'] as $item)
                    <div class="px-4 py-3 flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700">mental</span>
                        <span class="text-sm text-gray-800">{{ $item->recipient_email }}</span>
                        <span class="text-[12px] text-gray-500">{{ Str::limit($item->subject, 55) }}</span>
                        <span class="ml-auto text-[11px] text-gray-400 tabular-nums">{{ optional($item->sent_at)->format('d M H:i') }}</span>
                    </div>
                @endforeach

                @foreach ($perluTindakan['menggantung'] as $item)
                    <div class="px-4 py-3 flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700">menggantung</span>
                        <span class="text-sm text-gray-800">{{ $item->from_email }}</span>
                        <span class="text-[12px] text-gray-500">{{ Str::limit($item->subject ?? '(tanpa subjek)', 55) }}</span>
                        <span class="ml-auto text-[11px] text-gray-400 tabular-nums">
                            {{ optional($item->email_date)->diffForHumans() }}
                        </span>
                    </div>
                @endforeach

                @foreach ($perluTindakan['mangkrak'] as $item)
                    <div class="px-4 py-3 flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">mangkrak</span>
                        <span class="text-sm text-gray-800">{{ $item->recipient_email }}</span>
                        <span class="text-[12px] text-gray-500">{{ Str::limit($item->subject, 55) }}</span>
                        <span class="ml-auto text-[11px] text-gray-400 tabular-nums">{{ optional($item->sent_at)->format('d M H:i') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
