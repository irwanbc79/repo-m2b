<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>M2B Portal Customer</title>
    <link rel="icon" href="{{ asset('images/m2b-logo.png') }}" type="image/png">
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { m2b: { primary: '#0F2C59', secondary: '#1e3a8a', accent: '#B91C1C' } } }
            }
        }
    </script>
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-50">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        
        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-m2b-primary text-white transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 flex flex-col shrink-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="flex flex-col items-center justify-center h-24 border-b border-blue-900 bg-m2b-primary shadow-lg shrink-0">
                <h1 class="text-3xl font-black italic tracking-tighter">M2B</h1>
                <span class="text-[10px] tracking-widest uppercase mt-1 text-blue-200">Logistic Solution</span>
            </div>

            <nav class="flex-1 px-4 space-y-2 overflow-y-auto py-6">
                {{-- Dashboard --}}
                <a href="{{ route('customer.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.dashboard') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">🏠</span>
                    Dashboard
                </a>

                {{-- Shipments --}}
                <a href="{{ route('customer.shipments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.shipments.index') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">📦</span>
                    Shipments
                </a>

                {{-- Create Booking --}}
                <a href="{{ route('customer.shipments.create') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.shipments.create') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">➕</span>
                    Create Booking
                </a>

                {{-- MENU KURS PAJAK (BERSIH DARI LINK SAMPAH) --}}
                <a href="{{ route('customer.kurs') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.kurs') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">💱</span>
                    Kurs Pajak
                </a>

                {{-- MENU KALKULATOR (TAMBAHAN BARU) --}}
                <a href="{{ route('customer.calculator') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.calculator') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">🧮</span>
                    Kalkulator Pabean
                </a>
                {{-- MENU HS CODE EXPLORER --}}
                <a href="{{ route('customer.hs-codes') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.hs-codes') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">📋</span>
                    HS Code Explorer
                </a>

                {{-- MENU PEMBAYARAN --}}
                <a href="{{ route('customer.invoices') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.invoices') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">💳</span>
                    Pembayaran
                </a>

                {{-- MENU LAPORAN --}}
                <a href="{{ route('customer.reports') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.reports') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">📊</span>
                    Laporan
                </a>

                {{-- My Profile --}}
                <a href="{{ route('customer.profile') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.profile') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">👤</span>
                    My Profile
                </a>
            </nav>

            <div class="p-4 border-t border-blue-900 bg-m2b-primary shrink-0">
                 <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-red-700 rounded-lg transition-colors">
                        <span class="text-lg mr-3">🚪</span>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="flex-1 flex flex-col min-h-screen w-0 overflow-hidden bg-slate-50">
            <header class="bg-white shadow-sm border-b h-16 flex items-center justify-between px-6 w-full">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 lg:hidden focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h1 class="text-xl font-bold text-m2b-primary truncate">@yield('header', 'Dashboard')</h1>
                
                {{-- JAM DIGITAL --}}
                <div class="hidden sm:flex flex-col items-end mr-4" 
                     x-data="{ date: new Date() }" 
                     x-init="setInterval(() => date = new Date(), 1000)">
                    <span class="text-xs font-bold text-gray-400 uppercase" x-text="date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short' })"></span>
                    <span class="text-lg font-black text-gray-600 tracking-widest font-mono" x-text="date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })"></span>
                </div>

                <div class="flex items-center gap-3 border-l border-gray-200 pl-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-gray-700 truncate max-w-[150px]">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider">CUSTOMER</p>
                    </div>
                    <div class="h-9 w-9 rounded-full bg-m2b-primary text-white flex items-center justify-center font-bold shrink-0 shadow-sm border-2 border-blue-100">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-x-hidden overflow-y-auto p-6 w-full">
                {{ $slot }}
            </div>
        </main>
    </div>
    @livewireScripts
</body>
</html>