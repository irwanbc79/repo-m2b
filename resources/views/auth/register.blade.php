<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - M2B Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { m2b: { primary: '#0F2C59', accent: '#B91C1C' } } } }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans py-10">

    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden m-4 border-t-4 border-m2b-primary">
        <div class="bg-white p-6 text-center border-b-4 border-m2b-accent">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Portal" class="h-24 mx-auto w-auto">
            <p class="text-gray-500 text-xs tracking-widest uppercase mt-4 font-semibold">New Partner Registration</p>
        </div>

        <div class="p-8">
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-3">
                    <p class="text-red-700 text-xs">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Full Name / PIC</label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition" 
                           type="text" name="name" value="{{ old('name') }}" required placeholder="Nama Lengkap PIC">
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Company Name</label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition" 
                           type="text" name="company_name" value="{{ old('company_name') }}" required placeholder="Nama Perusahaan">
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Email Address</label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition" 
                           type="email" name="email" value="{{ old('email') }}" required placeholder="email@perusahaan.com">
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Password</label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition" 
                           type="password" name="password" required placeholder="Min. 8 karakter">
                </div>

                <div class="mb-6">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Confirm Password</label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition" 
                           type="password" name="password_confirmation" required placeholder="Ulangi password">
                </div>

                <button class="w-full bg-m2b-primary hover:bg-blue-900 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-200 transform hover:-translate-y-0.5" type="submit">
                    REGISTER NOW
                </button>
            </form>

            <!-- Google OAuth -->
            <div class="relative flex py-3 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink mx-4 text-gray-400 text-xs uppercase tracking-wider">atau</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>

            <a href="{{ route('login.google') }}" class="w-full inline-flex items-center justify-center gap-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-2.5 px-4 rounded-lg shadow-sm hover:shadow transition duration-200 transform hover:-translate-y-0.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                    <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.68 1.54 14.98 1 12 1 7.24 1 3.2 3.73 1.24 7.74l3.86 3C6.01 7.73 8.78 5.04 12 5.04z"/>
                    <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.36H12v4.51h6.43c-.28 1.44-1.1 2.67-2.33 3.5l3.61 2.8c2.11-1.95 3.33-4.82 3.33-8.45z"/>
                    <path fill="#FBBC05" d="M5.1 14.76c-.23-.69-.36-1.43-.36-2.2s.13-1.51.36-2.2l-3.86-3C.43 9.07 0 10.49 0 12s.43 2.93 1.24 4.64l3.86-3.24z"/>
                    <path fill="#34A853" d="M12 23c3.24 0 5.97-1.07 7.96-2.91l-3.61-2.8c-1.1.74-2.51 1.18-4.35 1.18-3.22 0-5.99-2.69-6.9-5.7l-3.86 3C3.2 20.27 7.24 23 12 23z"/>
                </svg>
                <span class="text-xs">Daftar dengan Google</span>
            </a>

            <div class="mt-6 text-center border-t pt-4 border-gray-100">
                <p class="text-sm text-gray-600">Sudah punya akun? <a href="{{ route('login') }}" class="text-m2b-accent font-bold hover:underline">Login di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>
