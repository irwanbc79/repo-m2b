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
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
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
.sb-ico {
    flex: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 0.5rem;
    text-align: center;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.04);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    transition: all 0.15s ease;
}
.sb-txt { margin-left: .5rem; font-weight: 500; letter-spacing: -0.01em; }
a:hover .sb-ico { background: rgba(255, 255, 255, 0.12); transform: scale(1.05); }

@media (min-width: 1024px) {
    .sb-toggle {
        display: flex; align-items: center; gap: .5rem;
        margin: .5rem .75rem 0; padding: .5rem .625rem;
        border-radius: .625rem; cursor: pointer;
        color: #94a3b8; font-size: 11px; font-weight: 600;
        white-space: nowrap; overflow: hidden; user-select: none;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        transition: all .15s ease;
    }
    .sb-toggle:hover { background: rgba(255, 255, 255, 0.08); color: #f8fafc; border-color: rgba(255, 255, 255, 0.12); }
    .sb-toggle input { flex: none; width: .9rem; height: .9rem; cursor: pointer; accent-color: #2563eb; }

    html.sb-auto #sidebar {
        position: fixed; top: 0; bottom: 0; left: 0;
        width: 4rem; z-index: 60; transition: width .18s ease;
    }
    html.sb-auto #sidebar:hover,
    html.sb-auto #sidebar:focus-within {
        width: 16rem; transition-delay: .12s;
        box-shadow: 0 20px 50px rgba(0,0,0,.65);
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
        height: 1px; padding: 0; margin: 1rem .5rem; background: rgba(255,255,255,0.08); font-size: 0;
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
                class="fixed inset-y-0 left-0 z-50 w-64 bg-[#070F1E] text-slate-200 transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 flex flex-col shrink-0 border-r border-slate-800/80 shadow-2xl"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            <div class="flex flex-col items-center justify-center h-24 bg-[#040A14]/70 border-b border-slate-800/80 shrink-0 px-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.9)]"></span>
                    <h1 class="text-2xl font-black italic tracking-tighter text-white">
                        <span class="sb-brand-full">M2B <span class="text-blue-500 font-extrabold not-italic text-xl">ERP</span></span>
                        <span class="sb-brand-mini">M<span class="text-blue-500">2B</span></span>
                    </h1>
                </div>
                <span class="sb-brand-sub text-[9px] tracking-[0.25em] uppercase text-slate-400 font-semibold mt-1">Enterprise Control</span>
            </div>

            {{-- Sembunyikan otomatis: sidebar menyusut jadi rel ikon dan hanya
                 mengembang saat disentuh kursor. Pilihannya tersimpan di
                 localStorage per browser, jadi tiap staf punya preferensinya
                 sendiri tanpa menambah kolom di tabel users. --}}
            <label class="sb-toggle shrink-0" title="Sidebar menyusut jadi ikon, mengembang saat disentuh kursor (Ctrl+B)">
                <input type="checkbox" x-model="autoHide">
                <span>Sembunyikan otomatis</span>
            </label>

            <nav class="flex-1 px-3 space-y-1 overflow-y-auto py-5 custom-scrollbar">

                @if(auth()->user()->hasRole('auditor'))
                <div class="sb-hide-rail mx-1 mb-3 px-3 py-2 bg-amber-950/40 border border-amber-500/30 rounded-xl shadow-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-400 text-base leading-none">🔍</span>
                        <div>
                            <div class="text-xs font-black text-amber-400 uppercase tracking-widest leading-none">Auditor Mode</div>
                            <div class="text-[10px] text-amber-500/80 mt-0.5">Akses baca saja</div>
                        </div>
                    </div>
                </div>
                @endif

                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🏠</span><span class="sb-txt">Dashboard</span></a>

                @if(auth()->user()->hasPermission('dashboard.view') && !auth()->user()->hasRole(['auditor', 'konsultan_pajak']))
                <div class="sb-section px-3 py-1.5 mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Communication</div>

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
                <a href="{{ route('inbox.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 group {{ $adaHalamanEmail ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}">
                    <span class="flex-1"><span class="sb-ico">📬</span><span class="sb-txt">Pusat Email</span></span>
                    @if($unreadInboxCount + $emailMental > 0)
                        <span class="sb-badge ml-2 min-w-5 h-5 px-1.5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse shadow-xs">
                            {{ ($unreadInboxCount + $emailMental) > 99 ? '99+' : $unreadInboxCount + $emailMental }}
                        </span>
                    @endif
                </a>

                @php $moraLeadCount = \App\Models\MoraLeadNotification::whereNull('read_at')->count(); @endphp
                <a href="{{ route('admin.mora-leads') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 group {{ request()->routeIs('admin.mora-leads') ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-md shadow-orange-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}">
                    <span class="flex-1"><span class="sb-ico">🔥</span><span class="sb-txt">MORA Leads</span></span>
                    @if($moraLeadCount > 0)
                        <span class="sb-badge ml-2 min-w-5 h-5 px-1.5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse shadow-xs">
                            {{ $moraLeadCount > 9 ? '9+' : $moraLeadCount }}
                        </span>
                    @endif
                </a>
                @endif

                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <div class="sb-section px-3 py-1.5 mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Operations</div>

                @if(auth()->user()->hasPermission('shipment.view'))
                <a href="{{ route('admin.shipments.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.shipments*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📦</span><span class="sb-txt">Manage Shipments</span></a>

                @php $custMsgUnread = \App\Models\ShipmentMessage::unreadForAdmin()->count(); @endphp
                <a href="{{ route('admin.customer-messages') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.customer-messages') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}">
                    <span class="flex-1"><span class="sb-ico">💬</span><span class="sb-txt">Pesan Customer</span></span>
                    @if($custMsgUnread > 0)
                        <span class="sb-badge ml-2 min-w-5 h-5 px-1.5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse shadow-xs">
                            {{ $custMsgUnread > 9 ? '9+' : $custMsgUnread }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.calculator') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.calculator') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🧮</span><span class="sb-txt">Kalkulator Pabean</span></a>

                <a href="{{ route('hs-codes.explorer') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('hs-codes*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📋</span><span class="sb-txt">HS Code Explorer</span></a>

                <a href="{{ route('admin.field-docs.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.field-docs*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📸</span><span class="sb-txt">Dokumentasi Lapangan</span></a>
                @endif
                @endunless

                @if(auth()->user()->hasPermission('customer.view'))
                <a href="{{ route('admin.customers.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.customers*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">👥</span><span class="sb-txt">Manage Customers</span></a>
                
                <a href="{{ route('admin.vendors.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.vendors*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🤝</span><span class="sb-txt">Manage Vendors</span></a>
                @endif

                @if(auth()->user()->hasPermission('invoice.view'))
                <div class="sb-section px-3 py-1.5 mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sales & Finance</div>
                
                <a href="{{ route('admin.quotations.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.quotations*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📄</span><span class="sb-txt">Quotation / Penawaran</span></a>

                <a href="{{ route('admin.invoices.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.invoices*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🧾</span><span class="sb-txt">Invoicing / Tagihan</span></a>

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.bank-reconciliation') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.bank-reconciliation') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🏦</span><span class="sb-txt">Rekonsiliasi Bank</span></a>

                <a href="{{ route('finance.simple-invoice.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('finance.simple-invoice*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">💸</span><span class="sb-txt">Simple Invoice</span></a>
                @endunless

                @if(auth()->user()->hasPermission('job_costing.view'))
                <a href="{{ route('admin.job-costing.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.job-costing*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">💼</span><span class="sb-txt">Job Costing</span></a>
                <a href="{{ route('admin.profit-report') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.profit-report*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📈</span><span class="sb-txt">Laba per Shipment</span></a>
                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.petty-cash') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.petty-cash*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">💰</span><span class="sb-txt">Kas Kecil</span></a>

                <a href="{{ route('admin.products') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.products*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🛍️</span><span class="sb-txt">Master Product/Service</span></a>
                <a href="{{ route('admin.lartas-references') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.lartas-references') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🧭</span><span class="sb-txt">Referensi Lartas</span></a>
                @endunless
                @endif
                @endif

                @if(auth()->user()->hasPermission('cashier.view') || auth()->user()->hasPermission('accounting.view'))
                <div class="sb-section px-3 py-1.5 mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Accounting</div>

                {{-- Input/transaksi: hanya staff keuangan, BUKAN auditor --}}
                @if(auth()->user()->hasPermission('cashier.view'))
                <a href="{{ route('accounting.coa') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('accounting.coa') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📊</span><span class="sb-txt">Chart of Accounts</span></a>
                <a href="{{ route('accounting.journal') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('accounting.journal') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">✍️</span><span class="sb-txt">Journal Entry</span></a>
                <a href="{{ route('simple-cashier') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('simple-cashier') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">💰</span><span class="sb-txt">Kasir (Simple)</span></a>
                @endif

                {{-- Laporan keuangan: bisa diakses auditor & staff keuangan --}}
                <a href="{{ route('accounting.ledger') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('accounting.ledger') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📚</span><span class="sb-txt">General Ledger</span></a>
                <a href="{{ route('accounting.trial_balance') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('accounting.trial_balance') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">⚖️</span><span class="sb-txt">Trial Balance</span></a>
                <a href="{{ route('accounting.profit_loss') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('accounting.profit_loss') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📈</span><span class="sb-txt">Profit & Loss</span></a>
                <a href="{{ route('accounting.balance_sheet') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('accounting.balance_sheet') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📋</span><span class="sb-txt">Balance Sheet</span></a>
                @endif

                {{-- HRD & PAYROLL --}}
                <div class="sb-section px-3 py-1.5 mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">HRD &amp; Payroll</div>

                <a href="{{ route('staff.attendance') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('staff.attendance') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📍</span><span class="sb-txt">Presensi / Absensi Saya</span></a>

                @if(auth()->user()->hasRole(['admin', 'super_admin', 'director', 'finance', 'konsultan_pajak']))
                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <a href="{{ route('admin.hrd.employees') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.hrd.employees') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">👥</span><span class="sb-txt">Karyawan</span></a>

                <a href="{{ route('admin.hrd.jabatan') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.hrd.jabatan') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🏷️</span><span class="sb-txt">Jabatan</span></a>

                <a href="{{ route('admin.hrd.payroll-periods') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.hrd.payroll*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">💰</span><span class="sb-txt">Penggajian</span></a>

                <a href="{{ route('admin.attendance.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.attendance*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📊</span><span class="sb-txt">Rekap Absensi Mobile</span></a>

                <a href="{{ route('admin.visits') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.visits') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🚗</span><span class="sb-txt">Kunjungan Karyawan</span></a>
                @endunless

                <a href="{{ route('admin.tax-notes.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.tax-notes*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">🗒️</span><span class="sb-txt">Catatan Pajak</span></a>
                @endif

                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <div class="sb-section px-3 py-1.5 mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Settings</div>

                @if(auth()->user()->hasPermission('report.view_basic'))
                <a href="{{ route('admin.reports') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.reports') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📑</span><span class="sb-txt">Laporan / Reports</span></a>
                @endif

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.survey.dashboard') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.survey*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📋</span><span class="sb-txt">Customer Survey</span></a>
                @endunless

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.testimonial.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.testimonial*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">⭐</span><span class="sb-txt">Moderasi Testimoni</span></a>
                @endunless

                @if(auth()->user()->hasPermission('user.view'))
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.users*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">👤</span><span class="sb-txt">User Management</span></a>
                @endif

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.user-requests.index') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.user-requests*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📋</span><span class="sb-txt">User Requests</span></a>
                @endunless

                @if(auth()->user()->hasPermission('audit_log.view') || auth()->user()->hasPermission('cashier.view'))
                <a href="{{ route('audit-logs') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('audit-logs') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">📝</span><span class="sb-txt">Audit Logs</span></a>
                @endif

                @if(!in_array('auditor', auth()->user()->roles ?? []))
                <a href="https://medsos.m2b.co.id" target="_blank" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 hover:bg-blue-900/60 text-blue-400 hover:text-blue-200 border border-blue-800/70 mt-2"><span class="sb-ico">📱</span><span class="sb-txt">Media Sosial</span><svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-3 w-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                @endif
                @endunless

                <a href="{{ route('admin.profile') }}" class="flex items-center px-3.5 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ request()->routeIs('admin.profile') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20 font-semibold' : 'hover:bg-slate-800/70 text-slate-300 hover:text-white' }}"><span class="sb-ico">⚙️</span><span class="sb-txt">Admin Profile</span></a>

            </nav>

            <div class="sb-footer p-3.5 border-t border-slate-800/80 bg-[#040A14]/70 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-3.5 py-2 text-sm font-semibold text-slate-300 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl transition-all border border-transparent hover:border-rose-500/20"><span class="sb-ico">🚪</span><span class="sb-txt">Logout</span></button>
                </form>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-h-screen w-0 overflow-hidden bg-slate-50/50">
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
         class="erp-modal-backdrop z-[9999]"
         style="display: none;">
        <div class="erp-modal-panel w-full h-[92vh] max-w-6xl mx-auto flex flex-col bg-slate-950 border border-slate-800 shadow-2xl"
             @click.away="show = false">
            <div class="erp-modal-header bg-slate-900 border-slate-800 text-white px-6 py-3.5">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse shrink-0"></span>
                    <span class="text-white font-bold text-sm truncate max-w-xl" x-text="title || 'Dokumen Pratinjau'"></span>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="url" target="_blank" class="bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl px-3 py-1.5 text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        Buka di Tab Baru
                    </a>
                    <button @click="show = false; url = ''" class="erp-modal-close text-slate-400 hover:text-white hover:bg-slate-800">&times;</button>
                </div>
            </div>
            <div class="flex-1 bg-slate-950 p-2 overflow-hidden">
                <template x-if="url">
                    <iframe :src="url" class="w-full h-full border-0 rounded-xl bg-white"></iframe>
                </template>
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
