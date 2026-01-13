<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Tidak Ditemukan - M2B Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8 text-center">
        <!-- Icon -->
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" class="text-red-400"/>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">File Tidak Ditemukan</h1>
        <p class="text-gray-600 mb-6">
            File dokumen ini tidak tersedia di server. Kemungkinan file belum diupload atau telah dihapus.
        </p>
        
        @if(isset($document))
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <h3 class="font-semibold text-gray-700 mb-2">Detail Dokumen:</h3>
            <div class="text-sm text-gray-600 space-y-1">
                <p><span class="font-medium">ID:</span> {{ $document->id }}</p>
                <p><span class="font-medium">Nama File:</span> {{ $document->filename ?? '-' }}</p>
                <p><span class="font-medium">Tipe:</span> {{ $document->document_type ?? '-' }}</p>
                <p><span class="font-medium">Shipment:</span> {{ $document->shipment_id ?? '-' }}</p>
            </div>
        </div>
        @endif
        
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="javascript:history.back()" 
               class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
            <a href="{{ url('/') }}" 
               class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Ke Dashboard
            </a>
        </div>
        
        <p class="text-xs text-gray-400 mt-6">
            Jika Anda yakin file ini seharusnya ada, silakan hubungi Admin.
        </p>
    </div>
</body>
</html>
