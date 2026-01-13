<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - M2B Portal</title>
    <link rel="icon" href="{{ asset('images/m2b-logo.png') }}" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        m2b: {
                            primary: '#0F2C59', // Warna Biru Tua M2B
                            accent: '#B91C1C',  // Warna Merah Aksen M2B
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden m-4 border-t-4 border-m2b-primary">
        <div class="bg-white p-6 text-center border-b-4 border-m2b-accent">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Portal" class="h-32 mx-auto w-auto">
        </div>

        <div class="p-8 pt-8">
            <h2 class="text-2xl font-bold text-m2b-primary text-center mb-8">PT. MORA MULTI BERKAH</h2>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                    <p class="text-red-700 text-sm">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="mb-5">
                    <label class="block text-m2b-primary text-sm font-bold mb-2 ml-1">Email Address</label>
                    <input class="w-full px-4 py-3 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition duration-200" 
                           id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
                </div>

                <div class="mb-8">
                    <div class="flex justify-between items-center mb-2 ml-1">
                        <label class="block text-m2b-primary text-sm font-bold">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs text-m2b-accent font-semibold hover:underline">Forgot Password?</a>
                    </div>
                    <input class="w-full px-4 py-3 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition duration-200" 
                           id="password" type="password" name="password" required placeholder="••••••••">
                </div>

                <button class="w-full bg-m2b-primary hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-lg shadow-lg hover:shadow-xl transition duration-200 transform hover:-translate-y-0.5" type="submit">
                    SIGN IN
                </button>
            </form>

            <div class="mt-8 text-center space-y-2 border-t pt-6 border-gray-100">
                <p class="text-sm text-gray-600">
                    Belum punya akun? 
                    <a href="{{ route('register') }}" class="text-m2b-primary font-bold hover:underline text-base">Daftar Sekarang</a>
                </p>
                <p class="text-xs text-gray-400">
                    Butuh bantuan? <a href="mailto:support@m2b.co.id" class="hover:text-m2b-accent">Hubungi Support</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>