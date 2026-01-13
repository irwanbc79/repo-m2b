<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Server Error - M2B Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .m2b-blue { color: #0F2C59; }
        .m2b-bg { background-color: #0F2C59; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="text-center px-4">
        <h1 class="text-9xl font-black text-gray-200">500</h1>
        
        <div class="mt-[-50px]">
            <h2 class="text-3xl font-bold m2b-blue mb-2">Oops! Terjadi Kesalahan Sistem</h2>
            <p class="text-gray-600 mb-8 max-w-md mx-auto">
                Mohon maaf, server kami sedang mengalami gangguan sementara. Tim teknis kami telah diberitahu tentang masalah ini.
            </p>
            
            <div class="space-x-4">
                <a href="{{ url('/') }}" class="m2b-bg text-white px-6 py-3 rounded-lg font-bold hover:opacity-90 transition inline-block">
                    Kembali ke Dashboard
                </a>
                <button onclick="location.reload()" class="border-2 border-gray-300 text-gray-600 px-6 py-3 rounded-lg font-bold hover:bg-gray-50 transition">
                    Coba Lagi
                </button>
            </div>

            <p class="mt-10 text-xs text-gray-400">Error ID: {{ request()->header('X-Request-ID') ?? date('YmdHis') }}</p>
        </div>
    </div>
</body>
</html>