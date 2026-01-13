<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - M2B Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="max-w-2xl w-full bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="inline-block p-4 bg-purple-100 rounded-full mb-4">
                    <svg class="w-16 h-16 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">QR Code Survey M2B</h1>
                <p class="text-gray-600">Scan untuk mengisi survey kepuasan pelanggan</p>
            </div>

            <!-- QR Code using Local Image -->
            <div class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-xl p-8 mb-6">
                <div class="flex justify-center mb-4">
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <img src="{{ asset('images/qr_survey.png') }}" 
                             alt="QR Code Survey M2B"
                             class="w-80 h-80 object-contain">
                    </div>
                </div>
                <p class="text-center text-sm text-gray-600 font-mono break-all">
                    https://portal.m2b.co.id/survey
                </p>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <h3 class="font-semibold text-blue-900 mb-2">📱 Cara Menggunakan:</h3>
                <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                    <li>Scan QR code menggunakan kamera smartphone</li>
                    <li>Klik "Download" untuk save QR code</li>
                    <li>Print dan tempatkan di tempat strategis</li>
                    <li>Customer dapat langsung isi survey</li>
                </ol>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <a href="{{ asset('images/qr_survey.png') }}" 
                   download="M2B-Survey-QR.png"
                   class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold text-center">
                    📥 Download QR Code
                </a>
                <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    🖨️ Print QR Code
                </button>
            </div>

            <div class="border-t pt-6">
                <h3 class="font-semibold text-gray-900 mb-3">🔗 Direct Link:</h3>
                <div class="flex items-center space-x-2">
                    <input type="text" id="surveyUrl" value="https://portal.m2b.co.id/survey" readonly 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm">
                    <button onclick="copyUrl()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                        Copy
                    </button>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="/admin/survey/dashboard" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        function copyUrl() {
            const input = document.getElementById('surveyUrl');
            input.select();
            document.execCommand('copy');
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✓ Copied!';
            btn.classList.add('bg-green-700');
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('bg-green-700');
            }, 2000);
        }
    </script>

    <style>
        @media print {
            body { 
                background: white; 
                display: flex;
                justify-content: center;
                align-items: center;
            }
            button, a[download], input { display: none; }
            .bg-gradient-to-br { background: white !important; }
        }
    </style>
</body>
</html>
