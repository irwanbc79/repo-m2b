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
<div class="max-w-md w-full">

    @if($quotation->approval_status === 'approved')
    {{-- ===== APPROVED SECTION ===== --}}
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-black text-gray-800 mb-2">Penawaran Disetujui!</h1>
        <p class="text-gray-500 text-sm mb-4">
            Terima kasih! Tim M2B akan segera menghubungi Anda untuk memproses pengiriman.
        </p>
        <div class="bg-green-50 rounded-xl px-5 py-4 text-left text-sm mb-6">
            <p class="text-green-700"><strong>{{ $quotation->quotation_number }}</strong></p>
            <p class="text-green-600 text-xs mt-1">Disetujui: {{ $quotation->approved_at?->format('d M Y, H:i') }}</p>
        </div>

        {{-- ===== UPLOAD SIGNED DOCUMENT ===== --}}
        <div class="border-t border-gray-100 pt-5 mt-2 text-left">
            <p class="text-sm font-bold text-gray-700 mb-1">📎 Upload Dokumen Bertandatangan</p>
            <p class="text-xs text-gray-500 mb-3">
                Wajib upload penawaran yang sudah ditandatangani Pimpinan dan distempel perusahaan (format PDF, maks 5 MB).
            </p>

            @if($quotation->signed_document_path)
            {{-- Already uploaded --}}
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center mb-3">
                <p class="text-sm font-bold text-emerald-700 mb-1">✅ Dokumen sudah diupload</p>
                <p class="text-xs text-emerald-600">{{ $quotation->signed_document_at?->format('d M Y, H:i') }}</p>
                <a href="{{ Storage::url($quotation->signed_document_path) }}" target="_blank"
                    class="inline-block mt-2 text-xs font-semibold text-emerald-700 underline">
                    Lihat dokumen
                </a>
            </div>
            <p class="text-center text-xs text-gray-400 mb-3">Ingin mengganti? Upload ulang di bawah.</p>
            @endif

            @if(session('upload_success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm font-medium mb-3">
                ✅ {{ session('upload_success') }}
            </div>
            @endif
            @if(session('upload_error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium mb-3">
                ⚠️ {{ session('upload_error') }}
            </div>
            @endif

            <form method="POST" action="{{ route('quotation.upload', $quotation->approval_token) }}" enctype="multipart/form-data">
                @csrf
                <div class="flex flex-col gap-3">
                    <input type="file" name="signed_document" accept=".pdf" required
                        class="text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 cursor-pointer border border-gray-200 rounded-xl p-2">
                    @if($errors->has('signed_document'))
                    <p class="text-xs text-red-600">⚠️ {{ $errors->first('signed_document') }}</p>
                    @endif
                    <button type="submit"
                        class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition shadow-sm text-sm">
                        📤 Upload Dokumen Sekarang
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6">
            <a href="https://wa.me/6281263027818?text=Halo+M2B,+saya+sudah+menyetujui+penawaran+{{ urlencode($quotation->quotation_number) }}"
                class="inline-flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold text-sm">
                💬 Konfirmasi via WhatsApp
            </a>
        </div>
    </div>

    @else
    {{-- ===== REJECTED SECTION ===== --}}
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
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
    </div>
    @endif

    <p class="text-center text-xs text-gray-400 mt-5">
        © {{ date('Y') }} PT. Mora Multi Berkah
    </p>
</div>
</body>
</html>
