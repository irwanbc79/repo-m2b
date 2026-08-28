<div class="max-w-6xl mx-auto space-y-6 pb-12" x-data="webAttendance()">
    
    {{-- Header Page --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-900 via-[#0F2C59] to-indigo-950 p-6 rounded-2xl shadow-xl border border-slate-700/60 text-white relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 rounded-full flex items-center gap-1.5 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Portal Presensi Staf
                </span>
                <span class="text-xs text-slate-300 font-medium">| Cadangan Web / Mobile</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-2">
                📍 Presensi &amp; Absensi Harian M2B
            </h1>
            <p class="text-xs text-slate-300 mt-1 max-w-xl">
                Halo, <strong>{{ auth()->user()->name }}</strong> ({{ strtoupper(auth()->user()->role) }}). Gunakan portal ini untuk check-in/check-out harian dari browser atau saat aplikasi mobile mengalami kendala teknis.
            </p>
        </div>

        {{-- Digital Real-Time Clock --}}
        <div class="relative z-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center min-w-[200px] shadow-lg">
            <div class="text-[11px] font-bold text-slate-300 uppercase tracking-widest" id="clock-date">
                {{ now()->isoFormat('dddd, D MMMM Y') }}
            </div>
            <div class="text-3xl font-black text-white tracking-wider my-1 font-mono drop-shadow-md" id="clock-time">
                {{ now()->format('H:i:s') }} <span class="text-xs text-emerald-400">WIB</span>
            </div>
            <div class="text-[10px] text-slate-300">Zona Waktu Asia/Jakarta</div>
        </div>

        {{-- Ambient decorative glow --}}
        <div class="absolute -right-10 -top-10 w-48 h-48 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
    <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 rounded-xl shadow-sm flex items-center justify-between animate-fadeIn">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🎉</span>
            <div>
                <h4 class="font-bold text-sm">Berhasil!</h4>
                <p class="text-xs text-emerald-700">{{ session('success') }}</p>
            </div>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm font-bold">✕</button>
    </div>
    @endif

    @if(session()->has('error'))
    <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-900 rounded-xl shadow-sm flex items-center justify-between animate-fadeIn">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h4 class="font-bold text-sm">Perhatian</h4>
                <p class="text-xs text-red-700">{{ session('error') }}</p>
            </div>
        </div>
        <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm font-bold">✕</button>
    </div>
    @endif

    {{-- Status Card Hari Ini --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Card Check-In --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border {{ $todayCheckin ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200' }} flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-md {{ $todayCheckin ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                    Status Check-In (Masuk)
                </span>
                @if($todayCheckin)
                    <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                        <span>🟢</span> {{ \Carbon\Carbon::parse($todayCheckin->created_at)->format('H:i:s') }} WIB
                    </h3>
                    <p class="text-xs text-slate-500">
                        Lokasi: <strong>{{ $todayCheckin->location->name ?? 'Di luar radius' }}</strong> 
                        @if($todayCheckin->verified_at)
                            <span class="text-emerald-600 font-bold">✓ Terverifikasi</span>
                        @else
                            <span class="text-amber-600 font-bold">⏳ Menunggu Verifikasi</span>
                        @endif
                    </p>
                @else
                    <h3 class="text-xl font-black text-slate-400 flex items-center gap-2">
                        <span>⚪</span> Belum Check-In
                    </h3>
                    <p class="text-xs text-slate-400">Silakan lakukan check-in saat mulai jam kerja.</p>
                @endif
            </div>
            <div class="w-12 h-12 rounded-xl {{ $todayCheckin ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center text-xl shadow-md">
                📥
            </div>
        </div>

        {{-- Card Check-Out --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border {{ $todayCheckout ? 'border-indigo-200 bg-indigo-50/20' : 'border-slate-200' }} flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-md {{ $todayCheckout ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500' }}">
                    Status Check-Out (Pulang)
                </span>
                @if($todayCheckout)
                    <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                        <span>🏁</span> {{ \Carbon\Carbon::parse($todayCheckout->created_at)->format('H:i:s') }} WIB
                    </h3>
                    <p class="text-xs text-slate-500">
                        Lokasi: <strong>{{ $todayCheckout->location->name ?? 'Di luar radius' }}</strong>
                        @if($todayCheckout->verified_at)
                            <span class="text-indigo-600 font-bold">✓ Terverifikasi</span>
                        @else
                            <span class="text-amber-600 font-bold">⏳ Menunggu Verifikasi</span>
                        @endif
                    </p>
                @else
                    <h3 class="text-xl font-black text-slate-400 flex items-center gap-2">
                        <span>⚪</span> Belum Check-Out
                    </h3>
                    <p class="text-xs text-slate-400">Lakukan check-out saat menyelesaikan jam kerja.</p>
                @endif
            </div>
            <div class="w-12 h-12 rounded-xl {{ $todayCheckout ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center text-xl shadow-md">
                📤
            </div>
        </div>
    </div>

    {{-- Main Action Panel --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Sisi Kiri: Form & Kamera Webcam (7 Cols) --}}
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-800 text-base flex items-center gap-2">
                    <span>📸</span> Formulir &amp; Verifikasi Presensi
                </h3>
                <span class="text-xs text-slate-400 font-medium">GPS + Webcam</span>
            </div>

            {{-- GPS Location Status Banner --}}
            <div class="p-4 rounded-xl border transition-all"
                 :class="isLocated ? (isWithinRadius ? 'bg-emerald-50/80 border-emerald-300 text-emerald-900' : 'bg-amber-50/80 border-amber-300 text-amber-900') : 'bg-slate-50 border-slate-200 text-slate-700'">
                <div class="flex items-start gap-3">
                    <span class="text-xl mt-0.5">📍</span>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-black uppercase tracking-wider mb-1" x-text="locationTitle">Mendeteksi Lokasi GPS...</h4>
                        <p class="text-xs leading-relaxed" x-text="locationMessage">Mohon izinkan akses lokasi (Geolocation) pada browser Anda agar sistem dapat memvalidasi titik koordinat presensi.</p>
                        
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                            <button type="button" @click="requestLocation()" class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold text-slate-700 shadow-sm flex items-center gap-1">
                                🔄 Refresh GPS
                            </button>
                            <template x-if="latitude && longitude">
                                <span class="font-mono text-slate-500 text-[10px]" x-text="'Lat: ' + latitude.toFixed(6) + ', Lng: ' + longitude.toFixed(6)"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Camera / Selfie Stream --}}
            <div class="space-y-3">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Foto Selfie Presensi (Kamera / Webcam)
                </label>

                <div class="relative bg-slate-900 rounded-2xl overflow-hidden border border-slate-300 aspect-video max-h-[300px] flex items-center justify-center shadow-inner">
                    {{-- Video preview --}}
                    <video x-ref="videoElement" autoplay playsinline class="w-full h-full object-cover" :class="cameraActive && !photoCaptured ? 'block' : 'hidden'"></video>
                    
                    {{-- Captured photo preview --}}
                    <img x-show="photoCaptured" :src="photoDataUrl" class="w-full h-full object-cover" />

                    {{-- Placeholder when camera off --}}
                    <div x-show="!cameraActive && !photoCaptured" class="text-center p-6 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-xs font-bold text-slate-300">Kamera Belum Aktif</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Klik "Aktifkan Kamera" di bawah untuk mengambil foto selfie.</p>
                    </div>

                    {{-- Capture overlay canvas (hidden) --}}
                    <canvas x-ref="canvasElement" class="hidden"></canvas>
                </div>

                {{-- Camera Controls --}}
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <template x-if="!cameraActive && !photoCaptured">
                            <button type="button" @click="startCamera()" class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold shadow transition flex items-center gap-1.5">
                                📷 Aktifkan Kamera
                            </button>
                        </template>

                        <template x-if="cameraActive && !photoCaptured">
                            <button type="button" @click="capturePhoto()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-lg transition flex items-center gap-1.5 animate-bounce">
                                📸 Jepret Foto Selfie
                            </button>
                        </template>

                        <template x-if="photoCaptured">
                            <button type="button" @click="retakePhoto()" class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow transition flex items-center gap-1.5">
                                🔄 Foto Ulang
                            </button>
                        </template>

                        <template x-if="cameraActive">
                            <button type="button" @click="stopCamera()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition">
                                Matikan
                            </button>
                        </template>
                    </div>

                    {{-- Fallback upload file --}}
                    <div class="text-right">
                        <label class="text-[11px] text-blue-600 hover:text-blue-800 font-bold cursor-pointer underline">
                            Atau upload file foto
                            <input type="file" wire:model="selfieFile" accept="image/*" class="hidden" @change="photoCaptured = false">
                        </label>
                    </div>
                </div>
            </div>

            {{-- Notes Input --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Catatan Kehadiran (Opsional)
                </label>
                <input type="text" wire:model="notes" placeholder="Contoh: Bekerja di kantor, tugas luar kota, dinas Belawan, dll..."
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition text-slate-800 font-medium">
            </div>

            {{-- Action Buttons --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                @if(!$todayCheckin || ($todayCheckin && $todayCheckout))
                    {{-- Tombol Check-In --}}
                    <button type="button" 
                            wire:click="submitCheckin" 
                            wire:loading.attr="disabled"
                            :disabled="!latitude || !longitude"
                            class="flex-1 py-3.5 px-6 rounded-xl font-extrabold text-sm tracking-wide text-white bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="submitCheckin">📥 CHECK-IN MASUK HARI INI</span>
                        <span wire:loading wire:target="submitCheckin">⏳ Menyimpan Presensi...</span>
                    </button>
                @elseif($todayCheckin && !$todayCheckout)
                    {{-- Tombol Check-Out --}}
                    <button type="button" 
                            wire:click="submitCheckout" 
                            wire:loading.attr="disabled"
                            :disabled="!latitude || !longitude"
                            class="flex-1 py-3.5 px-6 rounded-xl font-extrabold text-sm tracking-wide text-white bg-gradient-to-r from-indigo-600 to-purple-700 hover:from-indigo-700 hover:to-purple-800 shadow-lg shadow-indigo-600/30 transition transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="submitCheckout">📤 CHECK-OUT PULANG HARI INI</span>
                        <span wire:loading wire:target="submitCheckout">⏳ Menyimpan Presensi Pulang...</span>
                    </button>
                @endif
            </div>

        </div>

        {{-- Sisi Kanan: Panduan Lokasi Kantor & Mobile App (5 Cols) --}}
        <div class="lg:col-span-5 space-y-6">
            
            {{-- Info Mobile App --}}
            <div class="bg-gradient-to-br from-blue-900 to-slate-900 text-white rounded-2xl p-5 shadow-lg border border-blue-800/50 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">📱</span>
                    <div>
                        <h4 class="font-extrabold text-sm text-white">M2B Executive Mobile App</h4>
                        <p class="text-[11px] text-blue-200">Aplikasi Absensi &amp; Operasional Lapangan</p>
                    </div>
                </div>

                <p class="text-xs text-blue-100 leading-relaxed">
                    Staf dianjurkan melakukan presensi utama via <strong>Aplikasi Mobile M2B</strong> di smartphone untuk pelacakan rute &amp; dokumentasi lapangan real-time.
                </p>

                <div class="bg-white/10 rounded-xl p-3 text-xs space-y-2 border border-white/10">
                    <div class="flex items-center justify-between text-slate-300">
                        <span>Akun Login:</span>
                        <strong class="text-white">{{ auth()->user()->email }}</strong>
                    </div>
                    <div class="flex items-center justify-between text-slate-300">
                        <span>PWA Add to Home:</span>
                        <strong class="text-emerald-400">portal.m2b.co.id</strong>
                    </div>
                </div>
            </div>

            {{-- Daftar Titik Lokasi Resmi Geofence --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-3">
                <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <span>🏢</span> Titik Lokasi Kantor &amp; Geofence Resmi
                </h4>
                <div class="space-y-2 max-h-[260px] overflow-y-auto pr-1 text-xs">
                    @foreach($activeLocations as $loc)
                    <div class="p-2.5 rounded-xl border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800">{{ $loc->name }}</div>
                            <div class="text-[10px] text-slate-400">Radius: {{ $loc->radius_meters }} meter</div>
                        </div>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-semibold uppercase">
                            {{ $loc->type }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- Riwayat Presensi Pribadi (10 Transaksi Terakhir) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                    <span>📋</span> Riwayat Kehadiran Anda
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Daftar rekaman check-in &amp; check-out pribadi 10 transaksi terakhir</p>
            </div>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                {{ count($recentHistory) }} Rekaman
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900 text-slate-200 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Tipe</th>
                        <th class="py-3 px-4">Lokasi</th>
                        <th class="py-3 px-4">Status Verifikasi</th>
                        <th class="py-3 px-4">Foto Selfie</th>
                        <th class="py-3 px-4">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($recentHistory as $rec)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($rec->created_at)->translatedFormat('d M Y') }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ \Carbon\Carbon::parse($rec->created_at)->format('H:i:s') }} WIB</div>
                        </td>
                        <td class="py-3 px-4">
                            @if($rec->type === 'checkin')
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase">
                                    📥 Check-In
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold bg-indigo-100 text-indigo-800 border border-indigo-200 uppercase">
                                    📤 Check-Out
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-800">{{ $rec->location->name ?? 'Di luar radius kantor' }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ round($rec->latitude, 4) }}, {{ round($rec->longitude, 4) }}</div>
                        </td>
                        <td class="py-3 px-4">
                            @if($rec->verified_at)
                                <span class="text-emerald-600 font-bold flex items-center gap-1 text-[11px]">
                                    <span>✓</span> Terverifikasi
                                </span>
                            @else
                                <span class="text-amber-600 font-bold flex items-center gap-1 text-[11px]">
                                    <span>⏳</span> Menunggu HRD
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($rec->selfie_path)
                                <a href="{{ asset('storage/' . $rec->selfie_path) }}" target="_blank" class="inline-block relative group">
                                    <img src="{{ asset('storage/' . $rec->selfie_path) }}" class="w-9 h-9 rounded-lg object-cover border border-slate-200 group-hover:scale-110 transition shadow-sm" />
                                </a>
                            @else
                                <span class="text-slate-400 text-[11px] italic">Tanpa foto</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ $rec->notes ?: '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            Belum ada rekaman riwayat absensi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Alpine.js Script untuk Geolocation & Webcam --}}
<script>
function webAttendance() {
    return {
        latitude: null,
        longitude: null,
        isLocated: false,
        isWithinRadius: false,
        locationTitle: 'Mendeteksi Lokasi GPS...',
        locationMessage: 'Mohon izinkan akses lokasi browser Anda.',
        
        cameraActive: false,
        photoCaptured: false,
        photoDataUrl: null,
        stream: null,

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            this.requestLocation();
        },

        updateClock() {
            const now = new Date();
            const timeEl = document.getElementById('clock-time');
            if (timeEl) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                timeEl.innerHTML = `${hours}:${minutes}:${seconds} <span class="text-xs text-emerald-400">WIB</span>`;
            }
        },

        requestLocation() {
            if (!navigator.geolocation) {
                this.locationTitle = 'GPS Tidak Didukung';
                this.locationMessage = 'Browser Anda tidak mendukung Geolocation API.';
                return;
            }

            this.locationTitle = 'Mencari Koordinat GPS...';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.latitude = pos.coords.latitude;
                    this.longitude = pos.coords.longitude;
                    this.isLocated = true;
                    this.locationTitle = 'Lokasi Terdeteksi';
                    this.locationMessage = `Akurasi: ±${Math.round(pos.coords.accuracy)}m`;

                    // Kirim ke Livewire component
                    @this.setCoordinates(this.latitude, this.longitude);
                },
                (err) => {
                    this.locationTitle = 'Izin Lokasi Ditolak';
                    this.locationMessage = 'Klik ikon gembok/setelan browser di address bar untuk mengizinkan akses lokasi.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Browser tidak mendukung akses kamera.');
                return;
            }

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } })
                .then((mediaStream) => {
                    this.stream = mediaStream;
                    this.$refs.videoElement.srcObject = mediaStream;
                    this.cameraActive = true;
                    this.photoCaptured = false;
                })
                .catch((err) => {
                    alert('Gagal mengakses kamera: ' + err.message);
                });
        },

        capturePhoto() {
            const video = this.$refs.videoElement;
            const canvas = this.$refs.canvasElement;
            if (!video || !canvas) return;

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            this.photoDataUrl = canvas.toDataURL('image/jpeg', 0.85);
            this.photoCaptured = true;

            // Kirim base64 ke Livewire
            @this.set('selfieData', this.photoDataUrl);

            this.stopCamera();
        },

        retakePhoto() {
            this.photoCaptured = false;
            this.photoDataUrl = null;
            @this.set('selfieData', null);
            this.startCamera();
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.cameraActive = false;
        }
    }
}
</script>
