{{--
    Bilah tab Pusat Email.

    Empat halaman email digabung di bawah satu menu sidebar, tapi masing-masing
    TETAP jadi halaman & route sendiri — tidak ada komponen yang digabung atau
    ditulis ulang. Inbox saja sudah 900+ baris; menyatukannya jadi satu
    komponen akan menukar kerapian menu dengan risiko yang tidak sepadan.

    Tab pertama sengaja Inbox, karena itulah tujuan yang selama ini dihafal
    staf saat mengklik menu email.

    Dirender dari layout (bukan dari tiap view) supaya keempat halaman tidak
    perlu disunting satu per satu.
--}}
@php
    $tabs = [
        [
            'label'  => 'Masuk',
            'icon'   => '📥',
            'route'  => 'inbox.index',
            'active' => request()->routeIs('inbox.*'),
            'badge'  => $unreadInboxCount ?? 0,
            'tone'   => 'merah',
        ],
        [
            'label'  => 'Terkirim',
            'icon'   => '📤',
            'route'  => 'sent-emails.index',
            'active' => request()->routeIs('sent-emails.*'),
            'badge'  => 0,
            'tone'   => 'merah',
        ],
        [
            'label'  => 'Status Keluar',
            'icon'   => '📊',
            'route'  => 'admin.email-keluar',
            'active' => request()->routeIs('admin.email-keluar'),
            'badge'  => $emailMental ?? 0,
            'tone'   => 'merah',
        ],
        [
            'label'  => 'Statistik',
            'icon'   => '📈',
            'route'  => 'admin.email-statistik',
            'active' => request()->routeIs('admin.email-statistik'),
            'badge'  => 0,
            'tone'   => 'merah',
        ],
    ];
@endphp

<div class="bg-white border-b border-gray-200 px-6">
    <nav class="flex gap-1 overflow-x-auto" aria-label="Pusat Email">
        @foreach ($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               @if($tab['active']) aria-current="page" @endif
               class="flex items-center gap-1.5 px-4 py-3 text-sm font-bold whitespace-nowrap border-b-[3px] transition-colors
               {{ $tab['active']
                    ? 'border-m2b-accent text-m2b-accent'
                    : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}">
                <span>{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
                @if ($tab['badge'] > 0)
                    <span class="ml-0.5 min-w-4 h-4 px-1 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center">
                        {{ $tab['badge'] > 99 ? '99+' : $tab['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
