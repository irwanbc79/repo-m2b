<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Berhasil - Portal M2B</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/images/m2b-logo.png">
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 text-center">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Registrasi Berhasil!</h1>
            <p class="text-green-100 mt-2">Satu langkah lagi untuk mengaktifkan akun</p>
        </div>
        <div class="p-8">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <h3 class="font-bold text-blue-800">Cek Email Anda</h3>
                        <p class="text-blue-700 text-sm mt-1">Kami telah mengirimkan email verifikasi. Klik link di email untuk mengaktifkan akun.</p>
                    </div>
                </div>
            </div>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex items-center">
                    <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">1</span>
                    <span>Buka inbox email Anda</span>
                </div>
                <div class="flex items-center">
                    <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">2</span>
                    <span>Cari email dari <strong>Portal M2B</strong></span>
                </div>
                <div class="flex items-center">
                    <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">3</span>
                    <span>Klik tombol <strong>"Verifikasi Sekarang"</strong></span>
                </div>
            </div>
            <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-yellow-800 text-sm">
                    <strong>Tidak menerima email?</strong> Cek folder spam. Jika masih tidak ada, hubungi 
                    <a href="mailto:sales@m2b.co.id" class="text-blue-600 underline">sales@m2b.co.id</a>
                </p>
            </div>
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">← Kembali ke halaman Login</a>
            </div>
        </div>
        <div class="bg-gray-100 px-8 py-4 text-center">
            <p class="text-gray-500 text-xs">&copy; {{ date('Y') }} PT. Mora Multi Berkah</p>
        </div>
    </div>
</body>
</html>
