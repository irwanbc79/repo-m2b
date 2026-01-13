<!DOCTYPE html>
<html lang="id">
<head>
    <title>Verifikasi Email - M2B Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-lg text-center">
        <img src="{{ asset('images/m2b-logo.png') }}" class="h-16 mx-auto mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Verifikasi Email Anda</h2>
        <p class="text-gray-600 mb-6">Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi alamat email Anda dengan mengklik link yang baru saja kami kirimkan ke email Anda.</p>
        
        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 font-medium text-sm text-green-600">
                Link verifikasi baru telah dikirim ke email Anda.
            </div>
        @endif

        <div class="flex justify-center gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="text-white bg-blue-900 hover:bg-blue-800 px-4 py-2 rounded">Kirim Ulang Link</button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-600 underline hover:text-gray-900 px-4 py-2">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>