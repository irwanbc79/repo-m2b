<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penawaran Kedaluwarsa — M2B</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="max-w-md w-full text-center">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-black text-gray-800 mb-2">Penawaran Kedaluwarsa</h1>
        <p class="text-gray-500 text-sm mb-6">
            Masa berlaku penawaran <strong>{{ $quotation->quotation_number }}</strong> telah berakhir pada
            {{ $quotation->valid_until->format('d M Y') }}.
        </p>
        <a href="https://wa.me/6281263027818?text=Halo+M2B,+saya+ingin+meminta+penawaran+baru+untuk+referensi+{{ urlencode($quotation->quotation_number) }}"
            class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold px-6 py-3 rounded-xl transition">
            💬 Minta Penawaran Baru
        </a>
    </div>
    <p class="text-center text-xs text-gray-400 mt-5">© {{ date('Y') }} PT. Mora Multi Berkah</p>
</div>
</body>
</html>
