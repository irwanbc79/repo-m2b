<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>M2B Admin Panel</title>

    {{-- Preferensi "Sembunyikan otomatis" dibaca SEBELUM halaman dilukis,
         supaya sidebar tidak sempat berkedip lebar-lalu-menyempit. --}}
    <script>
        try {
            if (localStorage.getItem('m2b_sidebar_autohide') === 'true') {
                document.documentElement.classList.add('sb-auto');
            }
        } catch (e) {}
    </script>

    {{-- PWA / Add to Home Screen (iPhone & Android) --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="M2B Portal">
    <meta name="theme-color" content="#0F2C59">
    <link rel="apple-touch-icon" href="{{ asset('images/m2b-logo.png') }}">

    <link rel="icon" href="{{ asset('images/m2b-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
[x-cloak] { display: none !important; }
.shipment-table { table-layout: fixed; width: 100%; }
.shipment-table th:nth-child(1), .shipment-table td:nth-child(1) { width: 64px; }
.shipment-table th:nth-child(2), .shipment-table td:nth-child(2) { width: 160px; }
.shipment-table th:nth-child(3), .shipment-table td:nth-child(3) { width: 260px; }
.shipment-table th:nth-child(4), .shipment-table td:nth-child(4) { width: 320px; }
.shipment-table th:nth-child(5), .shipment-table td:nth-child(5) { width: 96px; }
.shipment-table th:nth-child(6), .shipment-table td:nth-child(6) { width: 160px; }
.shipment-table th:nth-child(7), .shipment-table td:nth-child(7) { width: 160px; }
.shipment-table td { vertical-align: top; word-break: break-word; }

/* ══ Sidebar: mode "Sembunyikan otomatis" ═══════════════════════════════════
   Hanya berlaku di layar lg ke atas — di mobile sidebar memang sudah
   off-canvas, jadi tidak ada yang perlu disembunyikan lagi.

   Saat aktif, sidebar menyusut jadi rel ikon 4rem dan mengembang ke 16rem
   ketika disentuh kursor / difokus keyboard. Sidebar dijadikan `fixed` dan
   <main> diberi margin 4rem supaya isi halaman TIDAK ikut bergeser tiap kali
   rel mengembang (tabel shipment yang lebar paling terasa kalau reflow).

   Aturan di bawah sengaja ditulis sebagai CSS biasa (tanpa layer), bukan
   utility Tailwind, supaya menang cascade atas class `lg:static`/`w-64` dan
   tidak perlu `npm run build` untuk class baru. */
.sb-toggle { display: none; }
.sb-brand-mini { display: none; }
/* Ikon & label tiap menu dipisah supaya labelnya bisa disembunyikan utuh
   saat mode rel — bukan dipotong setengah huruf. */
.sb-ico { flex: none; display: inline-block; width: 1.35rem; text-align: center; }
.sb-txt { margin-left: .4rem; }

@media (min-width: 1024px) {
    .sb-toggle {
        display: flex; align-items: center; gap: .5rem;
        margin: .5rem .75rem 0; padding: .5rem .625rem;
        border-radius: .5rem; cursor: pointer;
        color: #9ca3af; font-size: 11px; font-weight: 600;
        white-space: nowrap; overflow: hidden; user-select: none;
        transition: background-color .15s, color .15s;
    }
    .sb-toggle:hover { background: rgba(31,41,55,.9); color: #e5e7eb; }
    .sb-toggle input { flex: none; width: .9rem; height: .9rem; cursor: pointer; accent-color: #e11d48; }

    html.sb-auto #sidebar {
        position: fixed; top: 0; bottom: 0; left: 0;
        width: 4rem; z-index: 60; transition: width .18s ease;
    }
    html.sb-auto #sidebar:hover,
    html.sb-auto #sidebar:focus-within {
        width: 16rem; transition-delay: .12s;
        box-shadow: 0 10px 40px rgba(0,0,0,.45);
    }
    html.sb-auto main { margin-left: 4rem; }

    /* Keadaan menyempit: sisakan emoji-nya saja, teks dipotong. */
    html.sb-auto #sidebar:not(:hover):not(:focus-within) nav a,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) nav > div,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-toggle,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) form button {
        position: relative; overflow: hidden; white-space: nowrap;
    }
    /* Label & tanda panah menghilang; ikon dibiarkan di tengah rel. */
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-txt,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) nav a > svg { display: none; }
    html.sb-auto #sidebar:not(:hover):not(:focus-within) nav,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-footer {
        padding-left: .5rem; padding-right: .5rem;
    }
    html.sb-auto #sidebar:not(:hover):not(:focus-within) nav a,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) form button {
        padding-left: 0; padding-right: 0; justify-content: center;
    }
    html.sb-auto #sidebar:not(:hover):not(:focus-within) nav a > span.flex-1 {
        flex: none; text-align: center;
    }
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-brand-mini {
        font-size: 1rem; letter-spacing: 0;
    }
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-toggle {
        justify-content: center; padding-left: 0; padding-right: 0;
        margin-left: .25rem; margin-right: .25rem;
    }
    /* Badge angka tetap terlihat — justru itu alasan orang melirik sidebar. */
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-badge {
        position: absolute; top: .15rem; right: .15rem; margin-left: 0;
        transform: scale(.8); transform-origin: top right;
    }
    /* Judul grup jadi garis pemisah tipis. */
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-section {
        height: 1px; padding: 0; margin: 1rem .5rem; background: #374151; font-size: 0;
    }
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-hide-rail,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-brand-full,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-brand-sub,
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-toggle span { display: none; }
    html.sb-auto #sidebar:not(:hover):not(:focus-within) .sb-brand-mini { display: block; }
}

@media (prefers-reduced-motion: reduce) {
    html.sb-auto #sidebar { transition: none; }
}
    </style>
    
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    @stack('styles')
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100">
    
    <div class="min-h-screen flex"
         x-data="{
            sidebarOpen: false,
            autoHide: document.documentElement.classList.contains('sb-auto'),
         }"
         x-init="$watch('autoHide', v => {
            document.documentElement.classList.toggle('sb-auto', v);
            try { localStorage.setItem('m2b_sidebar_autohide', v ? 'true' : 'false'); } catch (e) {}
         })"
         @toggle-sidebar.window="sidebarOpen = !sidebarOpen"
         @keydown.window.ctrl.b.prevent="autoHide = !autoHide"
         @keydown.window.meta.b.prevent="autoHide = !autoHide">

        <aside id="sidebar"
                class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 flex flex-col shrink-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            <div class="flex flex-col items-center justify-center h-24 bg-black/20 border-b border-gray-800 shrink-0">
                <h1 class="text-2xl font-black italic tracking-tighter text-white">
                    <span class="sb-brand-full">M2B <span class="text-m2b-accent">ADMIN</span></span>
                    <span class="sb-brand-mini">M<span class="text-m2b-accent">2B</span></span>
                </h1>
                <span class="sb-brand-sub text-[10px] tracking-widest uppercase text-gray-400">Control Center</span>
            </div>

            {{-- Sembunyikan otomatis: sidebar menyusut jadi rel ikon dan hanya
                 mengembang saat disentuh kursor. Pilihannya tersimpan di
                 localStorage per browser, jadi tiap staf punya preferensinya
                 sendiri tanpa menambah kolom di tabel users. --}}
            <label class="sb-toggle shrink-0" title="Sidebar menyusut jadi ikon, mengembang saat disentuh kursor (Ctrl+B)">
                <input type="checkbox" x-model="autoHide">
                <span>Sembunyikan otomatis</span>
            </label>

            <nav class="flex-1 px-4 space-y-2 overflow-y-auto py-6 custom-scrollbar">

                @if(auth()->user()->hasRole('auditor'))
                <div class="sb-hide-rail mx-1 mb-3 px-3 py-2.5 bg-amber-900/40 border border-amber-600/50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-400 text-base leading-none">🔍</span>
                        <div>
                            <div class="text-xs font-black text-amber-400 uppercase tracking-widest leading-none">Auditor Mode</div>
                            <div class="text-[10px] text-amber-600 mt-0.5">Akses baca saja</div>
                        </div>
                    </div>
                </div>
                @endif

                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🏠</span><span class="sb-txt">Dashboard</span></a>

                @if(auth()->user()->hasPermission('dashboard.view') && !auth()->user()->hasRole(['auditor', 'konsultan_pajak']))
                <div class="sb-section px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Communication</div>

                {{-- ── Pusat Email ──────────────────────────────────────────
                     Empat halaman email (Masuk, Terkirim, Status Keluar,
                     Statistik) berbagi satu menu, lalu dipilah lewat bilah tab
                     di dalam halaman. Sidebar sempat punya empat baris email
                     dan mulai sesak.

                     Menu ini mengarah ke INBOX — tujuan yang selama ini dihafal
                     staf. Route lama tidak ada yang diubah, jadi bookmark &
                     tautan lama tetap berfungsi.

                     Kedua hitungan dipakai lagi oleh bilah tab, karena itu
                     dideklarasikan di sini.
                --}}
                @php
                    $unreadInboxCount = \DB::table('emails')->where('is_read', false)->count();
                    // Email mental = alamat customer salah; angkanya dimunculkan
                    // karena menuntut tindakan, bukan sekadar informasi.
                    $emailMental = \App\Models\EmailDelivery::whereIn('status', ['bounced', 'failed'])
                        ->where('sent_at', '>=', now()->subDays(30))->count();
                    $adaHalamanEmail = request()->routeIs('inbox.*')
                        || request()->routeIs('sent-emails.*')
                        || request()->routeIs('admin.email-keluar')
                        || request()->routeIs('admin.email-statistik');
                @endphp
                <a href="{{ route('inbox.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group {{ $adaHalamanEmail ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1"><span class="sb-ico">📬</span><span class="sb-txt">Pusat Email</span></span>
                    @if($unreadInboxCount + $emailMental > 0)
                        <span class="sb-badge ml-2 min-w-5 h-5 px-1.5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse">
                            {{ ($unreadInboxCount + $emailMental) > 99 ? '99+' : $unreadInboxCount + $emailMental }}
                        </span>
                    @endif
                </a>

                @php $moraLeadCount = \App\Models\MoraLeadNotification::whereNull('read_at')->count(); @endphp
                <a href="{{ route('admin.mora-leads') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group {{ request()->routeIs('admin.mora-leads') ? 'bg-orange-600 text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1"><span class="sb-ico">🔥</span><span class="sb-txt">MORA Leads</span></span>
                    @if($moraLeadCount > 0)
                        <span class="sb-badge ml-2 min-w-5 h-5 px-1.5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse">
                            {{ $moraLeadCount > 9 ? '9+' : $moraLeadCount }}
                        </span>
                    @endif
                </a>
                @endif

                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <div class="sb-section px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Operations</div>

                @if(auth()->user()->hasPermission('shipment.view'))
                <a href="{{ route('admin.shipments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.shipments*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📦</span><span class="sb-txt">Manage Shipments</span></a>

                @php $custMsgUnread = \App\Models\ShipmentMessage::unreadForAdmin()->count(); @endphp
                <a href="{{ route('admin.customer-messages') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.customer-messages') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1"><span class="sb-ico">💬</span><span class="sb-txt">Pesan Customer</span></span>
                    @if($custMsgUnread > 0)
                        <span class="sb-badge ml-2 min-w-5 h-5 px-1.5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse">
                            {{ $custMsgUnread > 9 ? '9+' : $custMsgUnread }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.calculator') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.calculator') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🧮</span><span class="sb-txt">Kalkulator Pabean</span></a>

                <a href="{{ route('hs-codes.explorer') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('hs-codes*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📋</span><span class="sb-txt">HS Code Explorer</span></a>

                <a href="{{ route('admin.field-docs.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.field-docs*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📸</span><span class="sb-txt">Dokumentasi Lapangan</span></a>
                @endif
                @endunless

                @if(auth()->user()->hasPermission('customer.view'))
                <a href="{{ route('admin.customers.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.customers*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">👥</span><span class="sb-txt">Manage Customers</span></a>
                
                <a href="{{ route('admin.vendors.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.vendors*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🤝</span><span class="sb-txt">Manage Vendors</span></a>
                @endif

                @if(auth()->user()->hasPermission('invoice.view'))
                <div class="sb-section px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Sales & Finance</div>
                
                <a href="{{ route('admin.quotations.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.quotations*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📄</span><span class="sb-txt">Quotation / Penawaran</span></a>

                <a href="{{ route('admin.invoices.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.invoices*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🧾</span><span class="sb-txt">Invoicing / Tagihan</span></a>

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.bank-reconciliation') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.bank-reconciliation') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🏦</span><span class="sb-txt">Rekonsiliasi Bank</span></a>

                <a href="{{ route('finance.simple-invoice.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('finance.simple-invoice*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">💸</span><span class="sb-txt">Simple Invoice</span></a>
                @endunless

                @if(auth()->user()->hasPermission('job_costing.view'))
                <a href="{{ route('admin.job-costing.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.job-costing*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">💼</span><span class="sb-txt">Job Costing</span></a>
                <a href="{{ route('admin.profit-report') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.profit-report*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📈</span><span class="sb-txt">Laba per Shipment</span></a>
                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.petty-cash') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.petty-cash*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">💰</span><span class="sb-txt">Kas Kecil</span></a>

                <a href="{{ route('admin.products') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.products*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🛍️</span><span class="sb-txt">Master Product/Service</span></a>
                <a href="{{ route('admin.lartas-references') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.lartas-references') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🧭</span><span class="sb-txt">Referensi Lartas</span></a>
                @endunless
                @endif
                @endif

                @if(auth()->user()->hasPermission('cashier.view') || auth()->user()->hasPermission('accounting.view'))
                <div class="sb-section px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Accounting</div>

                {{-- Input/transaksi: hanya staff keuangan, BUKAN auditor --}}
                @if(auth()->user()->hasPermission('cashier.view'))
                <a href="{{ route('accounting.coa') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('accounting.coa') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📊</span><span class="sb-txt">Chart of Accounts</span></a>
                <a href="{{ route('accounting.journal') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.journal') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">✍️</span><span class="sb-txt">Journal Entry</span></a>
                <a href="{{ route('simple-cashier') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('simple-cashier') ? 'bg-green-600 text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">💰</span><span class="sb-txt">Kasir (Simple)</span></a>
                @endif

                {{-- Laporan keuangan: bisa diakses auditor & staff keuangan --}}
                <a href="{{ route('accounting.ledger') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.ledger') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📚</span><span class="sb-txt">General Ledger</span></a>
                <a href="{{ route('accounting.trial_balance') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.trial_balance') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">⚖️</span><span class="sb-txt">Trial Balance</span></a>
                <a href="{{ route('accounting.profit_loss') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.profit_loss') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📈</span><span class="sb-txt">Profit & Loss</span></a>
                <a href="{{ route('accounting.balance_sheet') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.balance_sheet') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📋</span><span class="sb-txt">Balance Sheet</span></a>
                @endif

                {{-- HRD & PAYROLL --}}
                <div class="sb-section px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">HRD &amp; Payroll</div>

                <a href="{{ route('staff.attendance') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('staff.attendance') ? 'bg-emerald-600 text-white shadow-lg' : 'hover:bg-gray-800 text-emerald-300' }}"><span class="sb-ico">📍</span><span class="sb-txt">Presensi / Absensi Saya</span></a>

                @if(auth()->user()->hasRole(['admin', 'super_admin', 'director', 'finance', 'konsultan_pajak']))
                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <a href="{{ route('admin.hrd.employees') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.hrd.employees') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">👥</span><span class="sb-txt">Karyawan</span></a>

                <a href="{{ route('admin.hrd.jabatan') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.hrd.jabatan') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🏷️</span><span class="sb-txt">Jabatan</span></a>

                <a href="{{ route('admin.hrd.payroll-periods') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.hrd.payroll*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">💰</span><span class="sb-txt">Penggajian</span></a>

                <a href="{{ route('admin.attendance.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.attendance*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📊</span><span class="sb-txt">Rekap Absensi Mobile</span></a>

                <a href="{{ route('admin.visits') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.visits') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🚗</span><span class="sb-txt">Kunjungan Karyawan</span></a>
                @endunless

                <a href="{{ route('admin.tax-notes.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.tax-notes*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">🗒️</span><span class="sb-txt">Catatan Pajak</span></a>
                @endif

                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <div class="sb-section px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Settings</div>

                @if(auth()->user()->hasPermission('report.view_basic'))
                <a href="{{ route('admin.reports') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reports') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📑</span><span class="sb-txt">Laporan / Reports</span></a>
                @endif

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.survey.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.survey*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📋</span><span class="sb-txt">Customer Survey</span></a>
                @endunless

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.testimonial.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.testimonial*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">⭐</span><span class="sb-txt">Moderasi Testimoni</span></a>
                @endunless

                @if(auth()->user()->hasPermission('user.view'))
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">👤</span><span class="sb-txt">User Management</span></a>
                @endif

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.user-requests.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.user-requests*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📋</span><span class="sb-txt">User Requests</span></a>
                @endunless

                @if(auth()->user()->hasPermission('audit_log.view') || auth()->user()->hasPermission('cashier.view'))
                <a href="{{ route('audit-logs') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('audit-logs') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">📝</span><span class="sb-txt">Audit Logs</span></a>
                @endif

                @if(!in_array('auditor', auth()->user()->roles ?? []))
                <a href="https://medsos.m2b.co.id" target="_blank" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors hover:bg-blue-900 text-blue-400 hover:text-blue-200 border border-blue-800 mt-2"><span class="sb-ico">📱</span><span class="sb-txt">Media Sosial</span><svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-3 w-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                @endif
                @endunless

                <a href="{{ route('admin.profile') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.profile') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}"><span class="sb-ico">⚙️</span><span class="sb-txt">Admin Profile</span></a>

            </nav>

            <div class="sb-footer p-4 border-t border-gray-800 bg-gray-900 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-red-900 rounded-lg transition-colors"><span class="sb-ico">🚪</span><span class="sb-txt">Logout</span></button>
                </form>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-h-screen w-0 overflow-hidden">
            @livewire('admin.header', ['title' => View::hasSection('header') ? View::getSection('header') : 'Admin Dashboard'])

            {{-- Bilah tab Pusat Email — hanya muncul di keempat halaman email.
                 Ditaruh di sini, bukan di tiap view, supaya keempat halaman
                 tidak perlu disunting satu per satu. --}}
            @if ($adaHalamanEmail ?? false)
                @include('partials.email-tabs')
            @endif

            <div class="flex-1 overflow-x-hidden overflow-y-auto p-6 w-full">
                @yield('content')
                {{ $slot ?? '' }}
            </div>
        </main>
    </div>

    {{-- Chat internal (tombol mengambang). Komponen sendiri yang memutuskan
         tampil atau tidak — auditor & konsultan pajak tidak melihat apa pun. --}}
    @livewire('admin.internal-chat')

    {{-- Global Document Viewer Modal --}}
    <div x-data="{ show: false, url: '', title: '' }"
         x-on:open-doc-viewer.window="show = true; url = $event.detail.url; title = $event.detail.title ?? ''"
         x-show="show"
         x-cloak
         @keydown.escape.window="show = false"
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm"
         style="display: none;">
        <div class="relative w-full h-full max-w-5xl mx-auto p-4 flex flex-col">
            <div class="flex items-center justify-between mb-3 bg-gray-900/80 rounded-xl px-5 py-3">
                <span class="text-white font-bold text-sm truncate max-w-lg" x-text="title || 'Dokumen'"></span>
                <div class="flex items-center gap-2">
                    <a :href="url" target="_blank" class="bg-gray-700 hover:bg-gray-600 text-white rounded-lg px-3 py-1.5 text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        Tab Baru
                    </a>
                    <button @click="show = false; url = ''" class="text-gray-400 hover:text-red-400 text-2xl leading-none px-1">&times;</button>
                </div>
            </div>
            <div class="flex-1 bg-gray-900/30 rounded-xl overflow-hidden">
                <template x-if="url">
                    <iframe :src="url" class="w-full h-full border-0 rounded-xl"></iframe>
                </template>
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
