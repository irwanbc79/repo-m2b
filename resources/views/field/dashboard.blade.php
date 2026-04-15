<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0F2C59">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Petugas Lapangan - M2B</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { m2b: { primary: '#0F2C59', secondary: '#1e3a8a' } }
                }
            }
        }
    </script>
    <style>
        body { -webkit-tap-highlight-color: transparent; }
        .safe-top { padding-top: env(safe-area-inset-top); }
        .safe-bottom { padding-bottom: calc(env(safe-area-inset-bottom) + 4rem); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen safe-top safe-bottom">

    {{-- Header --}}
    <header class="bg-m2b-primary text-white px-4 py-4 sticky top-0 z-40 shadow-lg">
        <div class="flex items-center justify-between max-w-lg mx-auto">
            <div>
                <p class="text-xs text-white/60 uppercase tracking-wide">Portal Petugas Lapangan</p>
                <p class="font-bold text-lg leading-tight">{{ auth()->user()->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition">
                    Keluar
                </button>
            </form>
        </div>
    </header>

    <div class="max-w-lg mx-auto px-4 py-5 space-y-5">

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-2xl p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-blue-600">{{ $todayUploads }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Hari Ini</p>
            </div>
            <div class="bg-white rounded-2xl p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-emerald-600">{{ $weekUploads }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Minggu Ini</p>
            </div>
            <div class="bg-white rounded-2xl p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-gray-700">{{ $totalUploads }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total</p>
            </div>
        </div>

        {{-- Upload Button --}}
        <a href="{{ route('field.upload') }}"
           class="flex items-center justify-center gap-3 w-full py-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg rounded-2xl shadow-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Upload Foto Lapangan
        </a>

        {{-- Riwayat Upload --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Riwayat Upload Saya</h2>
                <span class="text-xs text-gray-400">{{ $totalUploads }} foto</span>
            </div>

            @if($recentPhotos->isEmpty())
                <div class="py-12 text-center text-gray-400">
                    <svg class="mx-auto w-14 h-14 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="mt-3 text-sm">Belum ada foto yang diupload</p>
                    <p class="text-xs mt-1">Tap tombol di atas untuk mulai upload</p>
                </div>
            @else
                <div class="grid grid-cols-3 gap-1 p-1">
                    @foreach($recentPhotos as $photo)
                    <div class="relative aspect-square bg-gray-100 overflow-hidden rounded-lg">
                        <img src="{{ asset('storage/' . ($photo->thumbnail_path ?? $photo->file_path)) }}"
                             alt="Foto"
                             class="w-full h-full object-cover"
                             onerror="this.src=''; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-2xl\'>📷</div>'">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent px-1.5 py-1">
                            <p class="text-white text-[9px] leading-tight truncate">
                                {{ $photo->shipment->awb_number ?? $photo->shipment->bl_number ?? '-' }}
                            </p>
                            <p class="text-white/70 text-[9px]">{{ $photo->created_at->format('d/m H:i') }}</p>
                        </div>
                        @if($photo->latitude)
                        <div class="absolute top-1 right-1 bg-green-500 rounded-full w-3 h-3" title="Ada GPS"></div>
                        @endif
                    </div>
                    @endforeach
                </div>

                @if($recentPhotos->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 text-sm">
                    {{ $recentPhotos->links() }}
                </div>
                @endif
            @endif
        </div>

    </div>

</body>
</html>
