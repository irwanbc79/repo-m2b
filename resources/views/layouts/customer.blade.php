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
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path></svg>
                    Dashboard
                </a>

                {{-- Shipments --}}
                <a href="{{ route('customer.shipments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.shipments.index') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Shipments
                </a>

                {{-- Create Booking --}}
                <a href="{{ route('customer.shipments.create') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.shipments.create') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Create Booking
                </a>

                {{-- MENU KURS PAJAK (BERSIH DARI LINK SAMPAH) --}}
                <a href="{{ route('customer.kurs') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.kurs') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Kurs Pajak
                </a>

                {{-- MENU KALKULATOR (TAMBAHAN BARU) --}}
                <a href="{{ route('customer.calculator') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.calculator') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Kalkulator Pabean
                </a>
                {{-- MENU HS CODE EXPLORER --}}
                <a href="{{ route('customer.hs-codes') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.hs-codes') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    HS Code Explorer
                </a>

                {{-- MENU PEMBAYARAN --}}
                <a href="{{ route('customer.invoices') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.invoices') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Pembayaran
                </a>

                {{-- MENU LAPORAN --}}
                <a href="{{ route('customer.reports') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.reports') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Laporan
                </a>

                {{-- My Profile --}}
                <a href="{{ route('customer.profile') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.profile') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    My Profile
                </a>
            </nav>

            <div class="p-4 border-t border-blue-900 bg-m2b-primary shrink-0">
                 <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-red-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
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