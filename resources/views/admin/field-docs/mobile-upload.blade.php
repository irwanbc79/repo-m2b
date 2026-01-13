<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>📷 Upload Foto - M2B Field</title>
    
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
        body { -webkit-tap-highlight-color: transparent; }
        .safe-top { padding-top: env(safe-area-inset-top); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
    </style>
    
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen safe-top safe-bottom">
    
    {{-- Mobile Header --}}
    <header class="bg-m2b-secondary text-white px-4 py-3 sticky top-0 z-40 shadow-lg">
        <div class="flex items-center justify-between max-w-lg mx-auto">
            <a href="{{ route('admin.field-docs.index') }}" class="text-white/80 hover:text-white">
                ← Kembali
            </a>
            <h1 class="text-lg font-bold">📷 Upload Foto</h1>
            <span class="text-sm opacity-80">{{ auth()->user()->name ?? 'User' }}</span>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="p-4 pb-8 max-w-lg mx-auto">
        @livewire('field-photo-upload', ['shipment' => $shipmentModel?->shipment_number])
    </main>

    @livewireScripts
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('requestGpsLocation', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        Livewire.dispatch('gpsLocationReceived', { 
                            lat: position.coords.latitude, 
                            lng: position.coords.longitude 
                        });
                    },
                    (error) => {
                        console.error('GPS Error:', error);
                        alert('Tidak dapat mengakses lokasi GPS: ' + error.message);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            } else {
                alert('Browser tidak mendukung GPS');
            }
        });
    });
    </script>
</body>
</html>
