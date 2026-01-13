<header class="bg-white shadow-sm border-b h-16 flex items-center justify-between px-6 w-full sticky top-0 z-30" x-data="{ sidebarOpen: false }">
    
    {{-- BAGIAN KIRI: TOGGLE SIDEBAR & JUDUL --}}
    <div class="flex items-center">
        {{-- Tombol Hamburger (Mobile) --}}
        <button @click="$dispatch('toggle-sidebar')" class="text-gray-500 lg:hidden mr-4 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        
        {{-- Judul Halaman --}}
        <h1 class="text-xl font-bold text-gray-800 hidden md:block">{{ $title }}</h1>
    </div>

    {{-- BAGIAN KANAN: WIDGETS --}}
    <div class="flex items-center gap-6">
        
        {{-- 1. WIDGET KURS USD (BARU) --}}
        <div class="hidden lg:flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-lg py-1.5 px-3 shadow-sm transition hover:shadow-md">
            <div class="flex items-center gap-2 border-r border-blue-200 pr-3 mr-1">
                {{-- Icon Bendera USA (SVG) --}}
                <div class="w-6 h-6 rounded-full overflow-hidden border border-gray-200 relative shadow-sm">
                    <svg viewBox="0 0 50 30" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full object-cover">
                        <rect width="50" height="30" fill="#B22234"/>
                        <rect y="5" width="50" height="5" fill="white"/>
                        <rect y="10" width="50" height="5" fill="#B22234"/>
                        <rect y="15" width="50" height="5" fill="white"/>
                        <rect y="20" width="50" height="5" fill="#B22234"/>
                        <rect y="25" width="50" height="5" fill="white"/>
                        <rect width="25" height="15" fill="#3C3B6E"/>
                    </svg>
                </div>
                <span class="text-xs font-black text-blue-900 tracking-wider">USD</span>
            </div>
            <div class="flex flex-col items-end leading-none">
                <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-0.5">Kurs Pajak hari ini</span>
                <span class="text-sm font-black text-m2b-primary font-mono">Rp {{ number_format($usdRate, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- 2. NOTIFIKASI --}}
        {{-- Kita panggil komponen notifikasi yang sudah ada --}}
        <livewire:admin.notification-dropdown />

        {{-- 3. LIVE DATE & TIME --}}
        <div class="hidden md:flex flex-col items-end text-right" 
             x-data="{ 
                date: new Date(),
                init() { setInterval(() => this.date = new Date(), 1000) }
             }">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1" 
                 x-text="date.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })">
                {{-- Fallback PHP saat loading --}}
                {{ now()->translatedFormat('l, d F Y') }}
            </div>
            <div class="text-lg font-black text-m2b-primary font-mono leading-none tracking-wide" 
                 x-text="date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':')">
                {{-- Fallback PHP saat loading --}}
                {{ date('H:i:s') }}
            </div>
        </div>

        {{-- 4. PROFILE --}}
        <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
            <div class="text-right hidden sm:block">
                <span class="block text-sm font-bold text-gray-600">{{ auth()->user()->name }}</span>
                {{-- Menggunakan helper role display yang sudah ada --}}
                <span class="block text-xs text-gray-400 uppercase">{{ auth()->user()->getRoleDisplayAttribute() ?? 'User' }}</span>
            </div>
            <div class="h-9 w-9 rounded bg-gray-900 text-white flex items-center justify-center font-bold shrink-0 shadow-sm cursor-pointer">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
        </div>

    </div>
</header>