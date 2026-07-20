<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima Kasih - M2B Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="max-w-2xl w-full bg-white rounded-2xl shadow-2xl p-8 text-center">
            <div class="mb-6">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <h1 class="text-4xl font-bold text-gray-800 mb-4">Terima Kasih! 🎉</h1>
            <p class="text-xl text-gray-600 mb-6">Survey Anda telah berhasil dikirim</p>

            @if(session('promoter_testimonial_token'))
            {{-- Promoter (NPS tinggi) → ajak menulis testimoni, di momen yang pas --}}
            <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border border-amber-200 rounded-2xl p-6 mb-8">
                <div class="text-3xl mb-2">⭐</div>
                <h2 class="text-xl font-bold text-amber-800 mb-1">Wah, terima kasih atas penilaian tingginya!</h2>
                <p class="text-gray-600 text-sm mb-4">Maukah Anda membagikan pengalaman singkat sebagai testimoni? Hanya 1 menit — dan bisa tampil di halaman utama M2B.</p>
                <a href="{{ route('testimonial.form', ['token' => session('promoter_testimonial_token')]) }}"
                   class="inline-block bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-3 rounded-xl shadow-md transition">
                    ✍️ Tulis Testimoni Saya
                </a>
            </div>
            @endif

            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-8 text-left">
                <p class="text-gray-700 mb-3">
                    <strong class="text-blue-700">Feedback Anda sangat berharga bagi kami!</strong>
                </p>
                <p class="text-gray-600 text-sm">
                    Kami akan menggunakan masukan Anda untuk terus meningkatkan kualitas layanan PT. Mora Multi Berkah.
                </p>
            </div>

            <div class="bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-purple-800 mb-3">🎁 Special Appreciation</h2>
                <p class="text-gray-700 mb-4">Sebagai bentuk terima kasih, dapatkan:</p>
                <ul class="text-left text-gray-700 space-y-2 mb-4">
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span><strong>5% Discount</strong> untuk booking berikutnya</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span><strong>Priority Support</strong> untuk kebutuhan logistics Anda</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span><strong>Free Consultation</strong> dengan tim expert kami</span>
                    </li>
                </ul>
                <p class="text-sm text-gray-600 italic">
                    *Hubungi sales@m2b.co.id dengan kode: <strong>SURVEY2025</strong>
                </p>
            </div>

            <div class="mb-8">
                <p class="text-gray-600 text-sm mb-2">Bergabunglah dengan</p>
                <p class="text-3xl font-bold text-blue-600">200+ Perusahaan</p>
                <p class="text-gray-600 text-sm">yang telah mempercayai M2B</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <a href="https://portal.m2b.co.id" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    🏠 Portal M2B
                </a>
                <a href="https://m2b.co.id" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    🌐 Website
                </a>
                <a href="https://wa.me/6281263027818" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    💬 WhatsApp
                </a>
            </div>

            <div class="border-t pt-6">
                <p class="text-gray-600 text-sm mb-2">Pertanyaan atau butuh bantuan?</p>
                <div class="flex justify-center space-x-6 text-sm text-gray-600">
                    <a href="mailto:sales@m2b.co.id" class="hover:text-blue-600">
                        📧 sales@m2b.co.id
                    </a>
                    <a href="tel:+6281263027818" class="hover:text-blue-600">
                        📞 +62 812 6302 7818
                    </a>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t">
                <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logo" class="h-12 mx-auto mb-2">
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} PT. Mora Multi Berkah. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
