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

            <div class="mt-6 text-center border-t pt-4 border-gray-100">
                <p class="text-sm text-gray-600">Sudah punya akun? <a href="{{ route('login') }}" class="text-m2b-accent font-bold hover:underline">Login di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>