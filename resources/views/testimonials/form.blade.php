<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Testimoni - M2B Logistics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/images/m2b-logo.png" type="image/png">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4">

<div class="max-w-lg w-full">

    <!-- Header -->
    <div class="text-center mb-8">
        <img src="/images/m2b-logo.png" alt="M2B Logistics" class="h-14 mx-auto mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Tulis Testimoni Anda</h1>
        <p class="text-gray-500 mt-1">Pengalaman Anda sangat berarti bagi kami</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-lg p-8">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('testimonial.submit', $token) }}" method="POST">
            @csrf

            <!-- Rating Bintang -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Rating Kepuasan *</label>
                <div class="flex gap-2" id="star-container">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" onclick="setRating({{ $i }})"
                                class="star-btn text-4xl transition-transform hover:scale-110 focus:outline-none"
                                data-value="{{ $i }}">
                            ⭐
                        </button>
                    @endfor
                </div>
                <input type="hidden" name="rating" id="rating-input" value="5">
                <p class="text-xs text-gray-400 mt-2" id="rating-label">Sangat Puas</p>
            </div>

            <!-- Nama -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama / PIC *</label>
                <input type="text" name="display_name" value="{{ old('display_name') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                       placeholder="Isikan nama Anda atau PIC Perusahaan" required>
            </div>

            <!-- Perusahaan -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Perusahaan</label>
                <input type="text" name="company_name" value="{{ old('company_name', $testimonial->company_name) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                       placeholder="PT. Nama Perusahaan (opsional)">
            </div>

            <!-- Jabatan -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Jabatan</label>
                <input type="text" name="position" value="{{ old('position') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                       placeholder="Direktur, Manager Logistik, dll. (opsional)">
            </div>

            <!-- Isi Testimoni -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Testimoni *</label>
                <textarea name="content" rows="5" required minlength="10" maxlength="1000"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm resize-none"
                          placeholder="Ceritakan pengalaman Anda bekerja sama dengan M2B Logistics...">{{ old('content') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Minimal 10 karakter</p>
            </div>

            <button type="submit"
                    class="w-full py-3 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-lg transition text-sm">
                Kirim Testimoni →
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        Testimoni akan ditinjau oleh tim kami sebelum ditayangkan.
    </p>
</div>

<script>
    const labels = ['', 'Tidak Puas', 'Kurang Puas', 'Cukup', 'Puas', 'Sangat Puas'];
    function setRating(val) {
        document.getElementById('rating-input').value = val;
        document.getElementById('rating-label').textContent = labels[val];
        document.querySelectorAll('.star-btn').forEach((btn, i) => {
            btn.style.opacity = i < val ? '1' : '0.3';
        });
    }
    setRating(5);
</script>
</body>
</html>
