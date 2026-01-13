<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>@yield('title', config('app.name', 'M2B Portal'))</title>

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Tailwind or app css (sesuaikan jika kamu pakai asset berbeda) --}}
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{-- Livewire styles --}}
    @if (app()->environment('local') || true)
        @livewireStyles
    @else
        @livewireStyles
    @endif
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

    {{-- Top navbar --}}
    <header class="bg-white border-b shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ url('/admin/dashboard') }}" class="text-lg font-bold">
                        {{ config('app.name', 'M2B Portal') }}
                    </a>
                </div>

                <nav class="flex items-center gap-3 text-sm">
                    <a href="{{ url('/admin/dashboard') }}" class="px-3 py-2 hover:bg-gray-100 rounded">Dashboard</a>
                    <a href="{{ url('/admin/inbox') }}" class="px-3 py-2 hover:bg-gray-100 rounded">Inbox</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 hover:bg-gray-100 rounded">Logout</button>
                    </form>
                </nav>
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <main class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Livewire page content rendered here --}}
            {{ $slot }}
        </div>
    </main>

    {{-- Footer (opsional) --}}
    <footer class="text-xs text-gray-500 text-center py-6">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </footer>

    {{-- App JS (alpine, app.js, dll) --}}
    <script src="{{ asset('js/app.js') }}" defer></script>

    {{-- Livewire scripts --}}
    @livewireScripts

    {{-- Hook for custom events (example attachment-ready handler untuk blade upload-download) --}}
    <script>
    window.addEventListener('attachment-ready', function(e){
        // example handler if you use dispatchBrowserEvent('attachment-ready', ...) from Livewire
        // e.detail.filename, e.detail.data_uri
        console.log('attachment-ready', e.detail);
    });

    window.addEventListener('notify', function(e){
        // simple alert for messages from Livewire
        alert((e.detail.type || 'info') + ': ' + (e.detail.message || ''));
    });
    </script>
</body>
</html>
