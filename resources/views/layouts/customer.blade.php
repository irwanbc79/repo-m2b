<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>M2B Portal Customer</title>

    {{-- PWA / Add to Home Screen (iPhone & Android) --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="M2B Customer">
    <meta name="theme-color" content="#0F2C59">
    <link rel="apple-touch-icon" href="{{ asset('images/m2b-logo.png') }}">

    <link rel="icon" href="{{ asset('images/m2b-logo.png') }}" type="image/png">
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                @php
                    $docReqBadge = 0;
                    try {
                        if (auth()->user()?->customer) {
                            $docReqBadge = \App\Models\DocumentRequirement::query()
                                ->whereHas('shipment', fn ($q) => $q->where('customer_id', auth()->user()->customer->id))
                                ->where('status', 'requested')
                                ->count();
                        }
                    } catch (\Throwable $e) { $docReqBadge = 0; }
                @endphp
                <a href="{{ route('customer.shipments.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.shipments.index') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">📦</span>
                    Shipments
                    @if($docReqBadge > 0)
                        <span class="ml-auto inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-m2b-accent text-white text-[10px] font-black animate-pulse" title="{{ $docReqBadge }} dokumen diminta tim M2B">{{ $docReqBadge }}</span>
                    @endif
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

                {{-- MENU PENAWARAN --}}
                <a href="{{ route('customer.quotations') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.quotations') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">📄</span>
                    Penawaran Saya
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

                {{-- Testimoni --}}
                <a href="{{ route('customer.testimonial') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('customer.testimonial') ? 'bg-m2b-accent text-white' : 'hover:bg-blue-900 text-gray-300' }}">
                    <span class="text-lg mr-3">⭐</span>
                    Testimoni
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
            {{-- Bar impersonation: admin sedang "Lihat sebagai customer" --}}
            @if(session()->has('impersonator_id'))
            <div class="bg-purple-700 text-white px-4 py-2 flex items-center justify-between gap-3 text-sm shrink-0">
                <span class="flex items-center gap-2">
                    <span>👁️</span>
                    Mode pratinjau — Anda melihat portal sebagai <strong>{{ auth()->user()->customer->company_name ?? auth()->user()->name }}</strong>
                </span>
                <a href="{{ route('impersonate.stop') }}" class="bg-white/20 hover:bg-white/30 transition rounded-lg px-3 py-1 font-bold shrink-0">
                    ← Kembali ke Admin
                </a>
            </div>
            @endif
            @php
                $usdRate = \Illuminate\Support\Facades\Cache::remember('customer_usd_rate_header', 21600, function () {
                    return \App\Models\TaxExchangeRate::where('currency_code', 'USD')
                        ->orderByDesc('valid_from')
                        ->value('rate') ?? 0;
                });
            @endphp
            <header class="bg-white shadow-sm border-b h-16 flex items-center justify-between px-6 w-full">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 lg:hidden focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h1 class="text-xl font-bold text-m2b-primary truncate">@yield('header', 'Dashboard')</h1>

                {{-- KURS USD PAJAK --}}
                @if($usdRate > 0)
                <a href="{{ route('customer.kurs') }}" class="hidden md:flex items-center gap-2 bg-blue-50 border border-blue-100 hover:bg-blue-100 transition-colors rounded-xl px-3 py-1.5 text-xs font-bold text-blue-800 shrink-0">
                    <img src="https://flagcdn.com/w20/us.png" class="w-4 h-3 object-cover rounded-sm" alt="USD">
                    <span>USD</span>
                    <span class="text-blue-600">Rp {{ number_format($usdRate, 0, ',', '.') }}</span>
                </a>
                @endif

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
                {{-- Soft-gate: pengingat lengkapi data perusahaan --}}
                @php
                    $__cust = auth()->user()?->customer;
                    $__dq = $__cust ? $__cust->dataQuality() : null;
                @endphp
                @if($__dq && $__dq['level'] !== 'good' && ! request()->routeIs('customer.profile'))
                <div x-data="{ show: sessionStorage.getItem('m2b_profile_reminder') !== 'hidden' }"
                     x-show="show" x-cloak
                     class="mb-6 rounded-xl border {{ $__dq['level'] === 'bad' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' }} p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 mt-0.5 {{ $__dq['level'] === 'bad' ? 'text-red-500' : 'text-amber-500' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold {{ $__dq['level'] === 'bad' ? 'text-red-800' : 'text-amber-800' }}">
                                Lengkapi data perusahaan Anda
                            </p>
                            <p class="text-xs {{ $__dq['level'] === 'bad' ? 'text-red-700' : 'text-amber-700' }} mt-0.5">
                                Data yang lengkap & benar diperlukan untuk pengurusan dokumen kepabeanan dan penerbitan invoice. Mohon lengkapi:
                            </p>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach(array_slice($__dq['issues'], 0, 5) as $__issue)
                                <span class="inline-flex items-center text-[11px] font-medium px-2 py-0.5 rounded-full bg-white border {{ $__dq['level'] === 'bad' ? 'border-red-200 text-red-700' : 'border-amber-200 text-amber-700' }}">
                                    {{ $__issue }}
                                </span>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3 mt-3">
                                <a href="{{ route('customer.profile') }}"
                                   class="inline-flex items-center gap-1.5 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition {{ $__dq['level'] === 'bad' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-500 hover:bg-amber-600' }}">
                                    <span>👤</span> Lengkapi Sekarang
                                </a>
                                <button type="button"
                                        @click="show = false; sessionStorage.setItem('m2b_profile_reminder', 'hidden'); fetch('{{ route('customer.profile-reminder.dismiss') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })"
                                        class="text-xs font-medium {{ $__dq['level'] === 'bad' ? 'text-red-600 hover:text-red-800' : 'text-amber-600 hover:text-amber-800' }}">
                                    Ingatkan nanti
                                </button>
                            </div>
                        </div>
                        <button type="button"
                                @click="show = false; sessionStorage.setItem('m2b_profile_reminder', 'hidden'); fetch('{{ route('customer.profile-reminder.dismiss') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })"
                                class="shrink-0 {{ $__dq['level'] === 'bad' ? 'text-red-400 hover:text-red-600' : 'text-amber-400 hover:text-amber-600' }}"
                                title="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                @endif

                {{ $slot }}
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
</body>
</html>