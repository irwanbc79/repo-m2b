<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - M2B Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { m2b: { primary: '#0F2C59', accent: '#B91C1C' } } } }
        }
    </script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden m-4 border-t-4 border-m2b-primary">
        <div class="bg-white p-6 text-center border-b-4 border-m2b-accent">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Portal" class="h-24 mx-auto w-auto">
            <p class="text-gray-500 text-xs tracking-widest uppercase mt-4 font-semibold">Buat Password Baru</p>
        </div>

        <div class="p-8">
            @if ($errors->any())
                <div class="mb-4 font-medium text-sm text-red-600 bg-red-50 p-3 rounded border-l-4 border-red-500">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-4">
                    <label class="block text-m2b-primary text-sm font-bold mb-2 ml-1">Email Address</label>
                    <input class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-gray-500 font-medium cursor-not-allowed" 
                           type="email" name="email" value="{{ $email ?? old('email') }}" readonly required>
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-sm font-bold mb-2 ml-1">Password Baru</label>
                    <input class="w-full px-4 py-3 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition" 
                           type="password" name="password" required autofocus placeholder="Min. 8 karakter">
                </div>

                <div class="mb-6">
                    <label class="block text-m2b-primary text-sm font-bold mb-2 ml-1">Ulangi Password</label>
                    <input class="w-full px-4 py-3 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition" 
                           type="password" name="password_confirmation" required placeholder="Ketik ulang password">
                </div>

                <button class="w-full bg-m2b-primary hover:bg-blue-900 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-200 transform hover:-translate-y-0.5" type="submit">
                    SIMPAN PASSWORD BARU
                </button>
            </form>
        </div>
    </div>

</body>
</html>