<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Halaman Tidak Ditemukan - M2B Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .m2b-blue { color: #0F2C59; }
        .m2b-bg { background-color: #0F2C59; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="text-center px-4">
        <h1 class="text-9xl font-black text-gray-200">404</h1>
        
        <div class="mt-[-50px]">
            <h2 class="text-3xl font-bold m2b-blue mb-2">Halaman Tidak Ditemukan</h2>
            <p class="text-gray-600 mb-8 max-w-md mx-auto">
                Sepertinya Anda tersesat. Halaman yang Anda cari mungkin telah dihapus, dipindahkan, atau link-nya salah.
            </p>
            
            <a href="{{ url('/') }}" class="m2b-bg text-white px-6 py-3 rounded-lg font-bold hover:opacity-90 transition inline-block shadow-lg">
                &larr; Kembali ke Home
            </a>
        </div>
    </div>
</body>
</html>