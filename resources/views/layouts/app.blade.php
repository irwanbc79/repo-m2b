<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>M2B Portal — PT. Mora Multi Berkah</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Premium Style -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
        }
        .navbar-brand {
            font-size: 18px;
            font-weight: 600;
        }
        .auth-box {
            padding: 40px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            border: 1px solid #e9ecef;
        }
        .footer {
            font-size: 13px;
            color: #6c757d;
            margin-top: 60px;
            text-align: center;
        }
        /* Logout button styling to look like a link */
        .logout-btn {
            background: none;
            border: none;
            color: #0d6efd;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            font-size: inherit;
        }
        .logout-btn:hover {
            color: #0a58ca;
        }
    </style>
    <!-- Alpine.js for interactivity -->
    
    <!-- QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    @stack('styles')
    @livewireStyles
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <span class="navbar-brand">
                <img src="/m2b-logo.png" height="34" class="me-2">
                PT. Mora Multi Berkah — Portal
            </span>
            <div>
                @auth
                    <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/admin/dashboard') }}">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                @else
                    <a href="/login">Login</a>
                @endauth
            </div>
        </div>
    </nav>
    <!-- MAIN CONTENT -->
    <div class="container py-5">
        {{ $slot ?? '' }}
        @yield('content')
    </div>
    <div class="footer">
        © 2025 PT. Mora Multi Berkah — All Rights Reserved.
    </div>
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
