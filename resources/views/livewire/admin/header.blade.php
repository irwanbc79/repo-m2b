<header class="bg-white/85 backdrop-blur-md supports-[backdrop-filter]:bg-white/80 border-b border-slate-200/80 h-16 flex items-center justify-between px-6 w-full sticky top-0 z-30 transition-all shadow-[0_1px_3px_rgba(0,0,0,0.03)]" x-data="{ sidebarOpen: false }">
    
    {{-- BAGIAN KIRI: TOGGLE SIDEBAR & JUDUL --}}
    <div class="flex items-center gap-3">
        {{-- Tombol Hamburger (Mobile) --}}
        <button @click="$dispatch('toggle-sidebar')" class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 lg:hidden focus:outline-none transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        
        {{-- Judul Halaman & Breadcrumb --}}
        <div class="hidden md:flex flex-col justify-center">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">M2B Portal</span>
                <span class="text-slate-300 text-xs leading-none">/</span>
                <h1 class="text-base font-bold text-slate-900 tracking-tight leading-none">{{ $title }}</h1>
                @if(auth()->user()->hasRole('auditor'))
                <span class="ml-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 border border-amber-300/80 uppercase tracking-wider shadow-xs">
                    🔍 Auditor Mode
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- BAGIAN KANAN: WIDGETS --}}
    <div class="flex items-center gap-4 sm:gap-6">
        
        {{-- 1. WIDGET KURS USD (MARKET STYLE) --}}
        <div class="hidden lg:flex items-center gap-3 bg-slate-50/80 border border-slate-200/80 rounded-xl py-1.5 px-3.5 shadow-xs transition hover:shadow-sm hover:border-blue-300/80 group">
            <div class="flex items-center gap-2 border-r border-slate-200 pr-3 mr-0.5">
                {{-- Icon Bendera USA (SVG) --}}
                <div class="w-5 h-5 rounded-full overflow-hidden border border-slate-200 relative shadow-xs shrink-0">
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
                <span class="text-xs font-black text-slate-800 tracking-wider">USD</span>
            </div>
            <div class="flex flex-col items-end leading-none">
                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mb-0.5 group-hover:text-blue-600 transition-colors">Kurs Pajak Hari Ini</span>
                <span class="text-sm font-black text-slate-900 font-mono tracking-tight">Rp {{ number_format($usdRate, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- 2. NOTIFIKASI --}}
        @unless(auth()->user()->hasRole(['konsultan_pajak', 'auditor']))
        <livewire:admin.notification-dropdown />
        @endunless

        {{-- 3. LIVE DATE & TIME --}}
        <div class="hidden md:flex flex-col items-end text-right pl-3 border-l border-slate-200/80" 
             x-data="{ 
                date: new Date(),
                init() { setInterval(() => this.date = new Date(), 1000) }
             }">
            <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-none mb-1">
                <span class="relative flex h-1.5 w-1.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                </span>
                <span x-text="date.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' })">
                    {{ now()->translatedFormat('l, d M Y') }}
                </span>
            </div>
            <div class="text-base font-black text-slate-900 font-mono leading-none tracking-tight" 
                 x-text="date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':')">
                {{ date('H:i:s') }}
            </div>
        </div>

        {{-- 4. PROFILE USER --}}
        <div class="flex items-center gap-3 pl-4 border-l border-slate-200/80">
            <div class="text-right hidden sm:block leading-tight">
                <span class="block text-xs font-bold text-slate-800">{{ auth()->user()->name }}</span>
                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                    {{ auth()->user()->getRoleDisplayAttribute() ?? 'User' }}
                </span>
            </div>
            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-slate-900 via-blue-900 to-indigo-900 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs ring-2 ring-slate-200/80 cursor-pointer hover:ring-blue-400 transition">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
        </div>

    </div>
</header>