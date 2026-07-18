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
    </style>
    
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    @stack('styles')
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100">
    
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }" @toggle-sidebar.window="sidebarOpen = !sidebarOpen">
        
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 flex flex-col shrink-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="flex flex-col items-center justify-center h-24 bg-black/20 border-b border-gray-800 shrink-0">
                <h1 class="text-2xl font-black italic tracking-tighter text-white">M2B <span class="text-m2b-accent">ADMIN</span></h1>
                <span class="text-[10px] tracking-widest uppercase text-gray-400">Control Center</span>
            </div>

            <nav class="flex-1 px-4 space-y-2 overflow-y-auto py-6 custom-scrollbar">

                @if(auth()->user()->hasRole('auditor'))
                <div class="mx-1 mb-3 px-3 py-2.5 bg-amber-900/40 border border-amber-600/50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-400 text-base leading-none">🔍</span>
                        <div>
                            <div class="text-xs font-black text-amber-400 uppercase tracking-widest leading-none">Auditor Mode</div>
                            <div class="text-[10px] text-amber-600 mt-0.5">Akses baca saja</div>
                        </div>
                    </div>
                </div>
                @endif

                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    🏠 Dashboard
                </a>

                @if(auth()->user()->hasPermission('dashboard.view') && !auth()->user()->hasRole(['auditor', 'konsultan_pajak']))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Communication</div>

                @php $unreadInboxCount = \DB::table('emails')->where('is_read', false)->count(); @endphp
                <a href="{{ route('inbox.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group {{ request()->routeIs('inbox.*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1">📧 Email Inbox</span>
                    @if($unreadInboxCount > 0)
                        <span class="ml-2 min-w-5 h-5 px-1.5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse">
                            {{ $unreadInboxCount > 99 ? '99+' : $unreadInboxCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('sent-emails.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group {{ request()->routeIs('sent-emails.*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1">📤 Email Terkirim</span>
                </a>

                @php $moraLeadCount = \App\Models\MoraLeadNotification::whereNull('read_at')->count(); @endphp
                <a href="{{ route('admin.mora-leads') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group {{ request()->routeIs('admin.mora-leads') ? 'bg-orange-600 text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1">🔥 MORA Leads</span>
                    @if($moraLeadCount > 0)
                        <span class="ml-2 min-w-5 h-5 px-1.5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse">
                            {{ $moraLeadCount > 9 ? '9+' : $moraLeadCount }}
                        </span>
                    @endif
                </a>
                @endif

                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Operations</div>

                @if(auth()->user()->hasPermission('shipment.view'))
                <a href="{{ route('admin.shipments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.shipments*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📦 Manage Shipments
                </a>

                @php $custMsgUnread = \App\Models\ShipmentMessage::unreadForAdmin()->count(); @endphp
                <a href="{{ route('admin.customer-messages') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.customer-messages') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1">💬 Pesan Customer</span>
                    @if($custMsgUnread > 0)
                        <span class="ml-2 min-w-5 h-5 px-1.5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center animate-pulse">
                            {{ $custMsgUnread > 9 ? '9+' : $custMsgUnread }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.calculator') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.calculator') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🧮 Kalkulator Pabean
                </a>

                <a href="{{ route('hs-codes.explorer') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('hs-codes*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📋 HS Code Explorer
                </a>

                <a href="{{ route('admin.field-docs.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.field-docs*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📸 Dokumentasi Lapangan
                </a>
                @endif
                @endunless

                @if(auth()->user()->hasPermission('customer.view'))
                <a href="{{ route('admin.customers.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.customers*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    👥 Manage Customers
                </a>
                
                <a href="{{ route('admin.vendors.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.vendors*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🤝 Manage Vendors
                </a>
                @endif

                @if(auth()->user()->hasPermission('invoice.view'))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Sales & Finance</div>
                
                <a href="{{ route('admin.quotations.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.quotations*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📄 Quotation / Penawaran
                </a>

                <a href="{{ route('admin.invoices.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.invoices*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🧾 Invoicing / Tagihan
                </a>

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.bank-reconciliation') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.bank-reconciliation') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🏦 Rekonsiliasi Bank
                </a>

                <a href="{{ route('finance.simple-invoice.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('finance.simple-invoice*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💸 Simple Invoice
                </a>
                @endunless

                @if(auth()->user()->hasPermission('job_costing.view'))
                <a href="{{ route('admin.job-costing.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.job-costing*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💼 Job Costing
                </a>
                <a href="{{ route('admin.profit-report') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.profit-report*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📈 Laba per Shipment
                </a>
                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.petty-cash') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.petty-cash*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💰 Kas Kecil
                </a>

                <a href="{{ route('admin.products') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.products*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🛍️ Master Product/Service
                </a>
                <a href="{{ route('admin.lartas-references') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.lartas-references') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🧭 Referensi Lartas
                </a>
                @endunless
                @endif
                @endif

                @if(auth()->user()->hasPermission('cashier.view') || auth()->user()->hasPermission('accounting.view'))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Accounting</div>

                {{-- Input/transaksi: hanya staff keuangan, BUKAN auditor --}}
                @if(auth()->user()->hasPermission('cashier.view'))
                <a href="{{ route('accounting.coa') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('accounting.coa') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📊 Chart of Accounts
                </a>
                <a href="{{ route('accounting.journal') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.journal') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    ✍️ Journal Entry
                </a>
                <a href="{{ route('simple-cashier') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('simple-cashier') ? 'bg-green-600 text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💰 Kasir (Simple)
                </a>
                @endif

                {{-- Laporan keuangan: bisa diakses auditor & staff keuangan --}}
                <a href="{{ route('accounting.ledger') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.ledger') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📚 General Ledger
                </a>
                <a href="{{ route('accounting.trial_balance') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.trial_balance') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    ⚖️ Trial Balance
                </a>
                <a href="{{ route('accounting.profit_loss') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.profit_loss') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📈 Profit & Loss
                </a>
                <a href="{{ route('accounting.balance_sheet') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.balance_sheet') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📋 Balance Sheet
                </a>
                @endif

                {{-- HRD & PAYROLL --}}
                @if(auth()->user()->hasRole(['admin', 'super_admin', 'director', 'finance', 'konsultan_pajak']))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">HRD &amp; Payroll</div>

                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <a href="{{ route('admin.hrd.employees') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.hrd.employees') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    👥 Karyawan
                </a>

                <a href="{{ route('admin.hrd.jabatan') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.hrd.jabatan') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    🏷️ Jabatan
                </a>

                <a href="{{ route('admin.hrd.payroll-periods') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.hrd.payroll*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    💰 Penggajian
                </a>

                <a href="{{ route('admin.attendance.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.attendance*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    📍 Absensi Mobile
                </a>

                <a href="{{ route('admin.visits') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.visits') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    🚗 Kunjungan Karyawan
                </a>
                @endunless

                <a href="{{ route('admin.tax-notes.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.tax-notes*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    🗒️ Catatan Pajak
                </a>
                @endif

                @unless(auth()->user()->hasRole('konsultan_pajak'))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Settings</div>

                @if(auth()->user()->hasPermission('report.view_basic'))
                <a href="{{ route('admin.reports') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reports') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📑 Laporan / Reports
                </a>
                @endif

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.survey.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.survey*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📋 Customer Survey
                </a>
                @endunless

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.testimonial.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.testimonial*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    ⭐ Moderasi Testimoni
                </a>
                @endunless

                @if(auth()->user()->hasPermission('user.view'))
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    👤 User Management
                </a>
                @endif

                @unless(auth()->user()->hasRole('auditor'))
                <a href="{{ route('admin.user-requests.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.user-requests*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📋 User Requests
                </a>
                @endunless

                @if(auth()->user()->hasPermission('audit_log.view') || auth()->user()->hasPermission('cashier.view'))
                <a href="{{ route('audit-logs') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('audit-logs') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📝 Audit Logs
                </a>
                @endif

                @if(!in_array('auditor', auth()->user()->roles ?? []))
                <a href="https://medsos.m2b.co.id" target="_blank" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors hover:bg-blue-900 text-blue-400 hover:text-blue-200 border border-blue-800 mt-2">
                    📱 Media Sosial
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-3 w-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                @endif
                @endunless

                <a href="{{ route('admin.profile') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.profile') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    ⚙️ Admin Profile
                </a>

            </nav>

            <div class="p-4 border-t border-gray-800 bg-gray-900 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-red-900 rounded-lg transition-colors">
                        🚪 Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-h-screen w-0 overflow-hidden">
            @livewire('admin.header', ['title' => View::hasSection('header') ? View::getSection('header') : 'Admin Dashboard'])
            <div class="flex-1 overflow-x-hidden overflow-y-auto p-6 w-full">
                @yield('content')
                {{ $slot ?? '' }}
            </div>
        </main>
    </div>

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
