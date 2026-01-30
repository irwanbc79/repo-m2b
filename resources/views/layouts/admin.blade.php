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
    
    <link rel="icon" href="{{ asset('images/m2b-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { m2b: { primary: '#0F2C59', secondary: '#1e3a8a', accent: '#B91C1C' } }
                }
            }
        }
    </script>
    
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
                
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    🏠 Dashboard
                </a>

                @if(auth()->user()->hasPermission('dashboard.view'))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Communication</div>
                
                <a href="{{ route('inbox.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group {{ request()->routeIs('inbox.*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1">📧 Email Inbox</span>
                </a>
                
                <a href="{{ route('sent-emails.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors group {{ request()->routeIs('sent-emails.*') ? 'bg-m2b-accent text-white shadow-lg' : 'hover:bg-gray-800 text-gray-300' }}">
                    <span class="flex-1">📤 Email Terkirim</span>
                </a>
                @endif

                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Operations</div>

                @if(auth()->user()->hasPermission('shipment.view'))
                <a href="{{ route('admin.shipments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.shipments*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📦 Manage Shipments
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

                <a href="{{ route('admin.bank-reconciliation') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.bank-reconciliation') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🏦 Rekonsiliasi Bank
                </a>

                <a href="{{ route('finance.simple-invoice.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('finance.simple-invoice*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💸 Simple Invoice
                </a>
                
                @if(auth()->user()->hasPermission('job_costing.view'))
                <a href="{{ route('admin.job-costing.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.job-costing*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💼 Job Costing
                </a>
                <a href="{{ route('admin.petty-cash') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.petty-cash*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💰 Kas Kecil
                </a>

                <a href="{{ route('admin.products') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.products*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    🛍️ Master Product/Service
                </a>
                @endif
                @endif

                @if(auth()->user()->hasPermission('cashier.view'))
                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Accounting</div>

                <a href="{{ route('accounting.coa') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('accounting.coa') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📊 Chart of Accounts
                </a>
                
                <a href="{{ route('accounting.journal') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg {{ request()->routeIs('accounting.journal') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    ✍️ Journal Entry
                </a>
                
                <a href="{{ route('simple-cashier') }}" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('simple-cashier') ? 'bg-green-600 text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    💰 Kasir (Simple)
                </a>
                
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

                <div class="px-4 py-2 mt-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Settings</div>

                @if(auth()->user()->hasPermission('report.view_basic'))
                <a href="{{ route('admin.reports') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reports') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📑 Laporan / Reports
                </a>
                @endif

                <a href="{{ route('admin.survey.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.survey*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📋 Customer Survey
                </a>
                
                <a href="{{ route('admin.reports') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.reports') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📑 Laporan / Reports
                </a>
                
                @if(auth()->user()->hasPermission('user.view'))
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    👤 User Management
                </a>
                @endif
                <a href="{{ route('admin.user-requests.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.user-requests*') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📋 User Requests
                </a>

                @if(auth()->user()->hasPermission('cashier.view'))
                <a href="{{ route('audit-logs') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('audit-logs') ? 'bg-m2b-accent text-white' : 'hover:bg-gray-800 text-gray-300' }}">
                    📝 Audit Logs
                </a>
                @endif

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

    @livewireScripts
    @stack('scripts')
</body>
</html>
