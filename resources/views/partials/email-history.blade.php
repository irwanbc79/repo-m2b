{{--
    Riwayat pengiriman email untuk satu entitas (Invoice / Quotation /
    Shipment / Customer).

    Dipakai dengan:
        @include('partials.email-history', ['entity' => $invoice])

    Tujuannya menjawab satu pertanyaan yang selama ini tidak bisa dijawab
    staf: "email ini benar-benar sampai dan dibaca, atau tidak?". Tanpa itu,
    penagihan piutang dikerjakan sambil menebak.

    Sengaja dibuat partial biasa (bukan komponen Livewire) supaya tidak
    menambah beban state pada halaman-halaman yang sudah besar.
--}}
@php
    $riwayat = \App\Models\EmailDelivery::where('related_type', get_class($entity))
        ->where('related_id', $entity->getKey())
        ->with(['events' => fn ($q) => $q->orderBy('occurred_at')])
        ->latest('sent_at')
        ->limit($limit ?? 5)
        ->get();

    $tone = [
        'ok'   => 'bg-emerald-50 text-emerald-700',
        'info' => 'bg-blue-50 text-blue-700',
        'warn' => 'bg-amber-50 text-amber-700',
        'crit' => 'bg-red-50 text-red-700',
        'mute' => 'bg-gray-100 text-gray-500',
    ];

    $namaPeristiwa = [
        'queued'    => 'Masuk antrean kirim',
        'sent'      => 'Dikirim',
        'delivered' => 'Diterima server penerima',
        'opened'    => 'Dibuka',
        'clicked'   => 'Tautan diklik',
        'deferred'  => 'Ditunda server penerima',
        'bounced'   => 'Mental',
        'failed'    => 'Gagal terkirim',
    ];
@endphp

<div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-800">Riwayat Pengiriman Email</h3>
        <a href="{{ route('admin.email-keluar') }}" class="text-[11px] font-bold text-blue-900 hover:underline">
            Lihat semua →
        </a>
    </div>

    @if ($riwayat->isEmpty())
        <div class="px-4 py-8 text-center">
            <p class="text-gray-500 text-sm">Belum ada email yang tercatat untuk data ini.</p>
            <p class="text-gray-400 text-xs mt-1">
                Pencatatan dimulai sejak fitur pelacakan aktif — kiriman sebelumnya tidak terdata.
            </p>
        </div>
    @else
        <div class="divide-y divide-gray-100">
            @foreach ($riwayat as $email)
                <div class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $tone[$email->statusTone()] }}">
                            {{ $email->statusLabel() }}
                        </span>
                        <span class="text-sm text-gray-800">{{ $email->recipient_email }}</span>
                        <span class="ml-auto text-[11px] text-gray-400 tabular-nums whitespace-nowrap">
                            {{ optional($email->sent_at)->format('d M Y H:i') }}
                        </span>
                    </div>

                    <p class="text-[12px] text-gray-500 mt-1">{{ $email->subject }}</p>

                    @if ($email->failure_reason)
                        <p class="text-[11px] text-red-600 mt-1">
                            Alasan: {{ Str::limit($email->failure_reason, 120) }}
                        </p>
                    @endif

                    @if ($email->events->isNotEmpty())
                        <ul class="mt-2 border-l-2 border-gray-100 pl-3 space-y-1">
                            @foreach ($email->events as $peristiwa)
                                <li class="text-[11px] text-gray-500 flex gap-2">
                                    <span class="tabular-nums text-gray-400 whitespace-nowrap">
                                        {{ $peristiwa->occurred_at->format('d M H:i') }}
                                    </span>
                                    <span>{{ $namaPeristiwa[$peristiwa->event_type] ?? $peristiwa->event_type }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
