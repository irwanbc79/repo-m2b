<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lengkapi Data - M2B Portal</title>
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
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Portal" class="h-20 mx-auto w-auto">
            <p class="text-gray-500 text-xs tracking-widest uppercase mt-4 font-semibold">Lengkapi Data Pendaftaran</p>
        </div>

        <div class="p-8">
            <div class="mb-5 bg-blue-50 border-l-4 border-m2b-primary p-3">
                <p class="text-m2b-primary text-xs">Masuk sebagai <span class="font-bold">{{ $email }}</span>. Mohon lengkapi data perusahaan Anda. Akun akan ditinjau & diaktifkan admin.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 bg-red-50 border-l-4 border-red-500 p-3">
                    <ul class="text-red-700 text-xs list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.complete.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Nama PIC</label>
                    <input class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-gray-500"
                           type="text" value="{{ $name }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Nama Perusahaan <span class="text-m2b-accent">*</span></label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                           type="text" name="company_name" value="{{ old('company_name') }}" required placeholder="PT / CV / Nama Usaha">
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Jabatan / Perwakilan <span class="text-m2b-accent">*</span></label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                           type="text" name="position" value="{{ old('position') }}" required placeholder="mis. Direktur / Staf Ekspor-Impor / Owner">
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Kebutuhan Layanan <span class="text-m2b-accent">*</span></label>
                    <select class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                            name="trade_type" required>
                        <option value="" disabled {{ old('trade_type') ? '' : 'selected' }}>-- Pilih kebutuhan --</option>
                        <option value="import" {{ old('trade_type') === 'import' ? 'selected' : '' }}>Impor</option>
                        <option value="export" {{ old('trade_type') === 'export' ? 'selected' : '' }}>Ekspor</option>
                        <option value="both" {{ old('trade_type') === 'both' ? 'selected' : '' }}>Impor &amp; Ekspor</option>
                        <option value="domestic" {{ old('trade_type') === 'domestic' ? 'selected' : '' }}>Pengiriman Domestik</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Rencana / Komoditas <span class="text-m2b-accent">*</span></label>
                    <textarea class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                              name="trade_plan" rows="2" required placeholder="mis. Impor mesin tekstil dari China, rutin tiap bulan / Kirim hasil bumi antar pulau">{{ old('trade_plan') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">No. HP / WhatsApp <span class="text-m2b-accent">*</span></label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                           type="text" name="phone" value="{{ old('phone') }}" required placeholder="08xxxxxxxxxx">
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Alamat Lengkap <span class="text-m2b-accent">*</span></label>
                    <textarea class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                              name="address" rows="2" required placeholder="Jalan, nomor, kelurahan, kecamatan">{{ old('address') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">Kota <span class="text-m2b-accent">*</span></label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                           type="text" name="city" value="{{ old('city') }}" required placeholder="Jakarta">
                </div>

                <div class="mb-6">
                    <label class="block text-m2b-primary text-xs font-bold mb-2 uppercase">NPWP <span class="text-gray-400 normal-case">(opsional)</span></label>
                    <input class="w-full px-4 py-2 bg-gray-50 border border-blue-200 rounded-lg focus:outline-none focus:border-m2b-primary focus:ring-2 focus:ring-m2b-primary/20 transition"
                           type="text" name="npwp" value="{{ old('npwp') }}" placeholder="15 atau 16 digit angka">
                </div>

                <button class="w-full bg-m2b-primary hover:bg-blue-900 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-200 transform hover:-translate-y-0.5" type="submit">
                    KIRIM & DAFTAR
                </button>
            </form>

            <div class="mt-6 text-center border-t pt-4 border-gray-100">
                <p class="text-sm text-gray-600">Bukan Anda? <a href="{{ route('login') }}" class="text-m2b-accent font-bold hover:underline">Kembali ke Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
