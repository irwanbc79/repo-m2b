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
            <!-- Success Icon -->
            <div class="mb-6">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <!-- Thank You Message -->
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Terima Kasih! 🎉</h1>
            <p class="text-xl text-gray-600 mb-6">Survey Anda telah berhasil dikirim</p>

            <!-- Message -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-8 text-left">
                <p class="text-gray-700 mb-3">
                    <strong class="text-blue-700">Feedback Anda sangat berharga bagi kami!</strong>
                </p>
                <p class="text-gray-600 text-sm mb-2">
                    Kami akan menggunakan masukan Anda untuk terus meningkatkan kualitas layanan PT. Mora Multi Berkah.
                </p>
                <p class="text-gray-600 text-sm">
                    Tim kami akan meninjau feedback Anda dan melakukan perbaikan yang diperlukan.
                </p>
            </div>

            <!-- Special Offer / Next Steps -->
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
                    *Hubungi sales@m2b.co.id dengan menyebutkan kode: <strong>SURVEY2025</strong>
                </p>
            </div>

            <!-- Social Proof -->
            <div class="mb-8">
                <p class="text-gray-600 text-sm mb-2">Bergabunglah dengan</p>
                <p class="text-3xl font-bold text-blue-600">200+ Perusahaan</p>
                <p class="text-gray-600 text-sm">yang telah mempercayai M2B untuk kebutuhan logistics mereka</p>
            </div>

            <!-- CTA Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <a href="https://portal.m2b.co.id" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    🏠 Portal M2B
                </a>
                <a href="https://m2b.co.id" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    🌐 Website
                </a>
                <a href="https://wa.me/628123456789" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    💬 WhatsApp
                </a>
            </div>

            <!-- Contact Info -->
            <div class="border-t pt-6">
                <p class="text-gray-600 text-sm mb-2">Pertanyaan atau butuh bantuan?</p>
                <div class="flex justify-center space-x-6 text-sm text-gray-600">
                    <a href="mailto:sales@m2b.co.id" class="hover:text-blue-600">
                        📧 sales@m2b.co.id
                    </a>
                    <a href="tel:+628123456789" class="hover:text-blue-600">
                        📞 +62 812-3456-789
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t">
                <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logo" class="h-12 mx-auto mb-2">
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} PT. Mora Multi Berkah. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <!-- Auto-redirect after 10 seconds (optional) -->
    <script>
        // Uncomment if you want auto-redirect
        // setTimeout(() => {
        //     window.location.href = 'https://portal.m2b.co.id';
        // }, 10000);
    </script>
</body>
</html>
