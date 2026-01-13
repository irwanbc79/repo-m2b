<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Akses Ditolak - M2B Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .m2b-red { color: #B91C1C; }
        .m2b-bg { background-color: #0F2C59; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="text-center px-4">
        <div class="inline-block p-4 rounded-full bg-red-100 mb-4">
            <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        
        <h2 class="text-3xl font-bold m2b-red mb-2">Akses Ditolak (403)</h2>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">
            Maaf, akun Anda tidak memiliki izin untuk mengakses halaman atau melakukan tindakan ini. Silakan hubungi Administrator jika ini adalah kesalahan.
        </p>
        
        <a href="{{ url('/') }}" class="m2b-bg text-white px-6 py-3 rounded-lg font-bold hover:opacity-90 transition inline-block shadow-lg">
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>