<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quotation->approval_status === 'approved' ? 'Penawaran Disetujui' : 'Penawaran Ditolak' }} — M2B</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { m2b: { primary: '#0F2C59' } } } } }</script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="max-w-md w-full text-center">

    <div class="bg-white rounded-2xl shadow-lg p-8">
        @if($quotation->approval_status === 'approved')
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-gray-800 mb-2">Penawaran Disetujui!</h1>
            <p class="text-gray-500 text-sm mb-6">
                Terima kasih! Tim M2B akan segera menghubungi Anda untuk memproses pengiriman.
            </p>
            <div class="bg-green-50 rounded-xl px-5 py-4 text-left text-sm mb-6">
                <p class="text-green-700"><strong>{{ $quotation->quotation_number }}</strong></p>
                <p class="text-green-600 text-xs mt-1">Disetujui: {{ $quotation->approved_at?->format('d M Y, H:i') }}</p>
            </div>
            <a href="https://wa.me/6281263027818?text=Halo+M2B,+saya+sudah+menyetujui+penawaran+{{ urlencode($quotation->quotation_number) }}"
                class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold px-6 py-3 rounded-xl transition">
                💬 Konfirmasi via WhatsApp
            </a>
        @else
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-gray-800 mb-2">Penawaran Ditolak</h1>
            <p class="text-gray-500 text-sm mb-6">
                Keputusan Anda sudah kami catat. Kami mohon maaf jika tidak sesuai harapan.
            </p>
            <a href="https://wa.me/6281263027818?text=Halo+M2B,+saya+ingin+mendiskusikan+kembali+penawaran+{{ urlencode($quotation->quotation_number) }}"
                class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-bold px-6 py-3 rounded-xl transition">
                💬 Diskusikan Ulang via WhatsApp
            </a>
        @endif
    </div>

    <p class="text-center text-xs text-gray-400 mt-5">
        © {{ date('Y') }} PT. Mora Multi Berkah
    </p>
</div>
</body>
</html>
