<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Penawaran — M2B Logistic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { m2b: { primary: '#0F2C59', accent: '#B91C1C' } } } } }</script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="max-w-lg w-full">

    {{-- Header --}}
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-m2b-primary rounded-2xl mb-3">
            <span class="text-white text-3xl font-black italic">M2B</span>
        </div>
        <p class="text-xs text-gray-400 uppercase tracking-widest">Logistic · Solution · Partner</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

        {{-- Banner --}}
        <div class="bg-m2b-primary px-6 py-5 text-white">
            <p class="text-xs font-bold uppercase tracking-widest text-blue-200 mb-1">Penawaran Harga</p>
            <h1 class="text-xl font-black">{{ $quotation->quotation_number }}</h1>
            <p class="text-sm text-blue-200 mt-1">{{ $quotation->origin }} → {{ $quotation->destination }}</p>
        </div>

        <div class="px-6 py-5 space-y-4">

            {{-- Detail ringkas --}}
            <div class="bg-gray-50 rounded-xl p-4 text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Service</span>
                    <span class="font-semibold text-gray-800">{{ ucfirst($quotation->service_type) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Berlaku Hingga</span>
                    <span class="font-semibold {{ $quotation->valid_until->diffInDays() <= 3 ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $quotation->valid_until->format('d M Y') }}
                        <span class="text-xs font-normal text-gray-400">({{ $quotation->valid_until->diffForHumans() }})</span>
                    </span>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-2 mt-2">
                    <span class="text-gray-500">Total Penawaran</span>
                    <span class="font-black text-m2b-primary text-base">Rp {{ number_format($quotation->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Konfirmasi aksi --}}
            @if($action === 'approve')
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                    <p class="text-green-800 font-semibold text-sm">Anda memilih untuk <strong>Menyetujui</strong> penawaran ini.</p>
                    <p class="text-green-600 text-xs mt-1">Tim M2B akan segera menghubungi Anda untuk langkah selanjutnya.</p>
                </div>
            @elseif($action === 'reject')
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                    <p class="text-red-800 font-semibold text-sm">Anda memilih untuk <strong>Menolak</strong> penawaran ini.</p>
                    <p class="text-red-600 text-xs mt-1">Kami mohon maaf tidak dapat memenuhi kebutuhan Anda kali ini.</p>
                </div>
            @else
                <p class="text-gray-600 text-sm text-center">Pilih keputusan Anda terhadap penawaran di atas:</p>
            @endif

            {{-- Form action --}}
            <form method="POST" action="{{ route('quotation.process', $quotation->approval_token) }}" class="space-y-3">
                @csrf
                @if($action === 'approve')
                    <input type="hidden" name="action" value="approve">
                    <button type="submit"
                        class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition text-base">
                        ✅ &nbsp;Ya, Saya Setujui Penawaran Ini
                    </button>
                    <a href="{{ route('quotation.approve', $quotation->approval_token) }}"
                        class="block text-center text-sm text-gray-400 hover:text-gray-600 py-2">← Kembali</a>
                @elseif($action === 'reject')
                    <input type="hidden" name="action" value="reject">
                    <button type="submit"
                        class="w-full py-3.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-base">
                        Tolak Penawaran Ini
                    </button>
                    <a href="{{ route('quotation.approve', $quotation->approval_token) }}"
                        class="block text-center text-sm text-gray-400 hover:text-gray-600 py-2">← Kembali</a>
                @else
                    <button type="submit" name="action" value="approve"
                        class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition text-base">
                        ✅ &nbsp;Setujui Penawaran
                    </button>
                    <button type="submit" name="action" value="reject"
                        class="w-full py-3 bg-white border-2 border-red-300 hover:border-red-500 text-red-600 font-semibold rounded-xl transition text-sm">
                        Tolak Penawaran
                    </button>
                @endif
            </form>

            {{-- WA fallback --}}
            <div class="text-center pt-2 border-t border-gray-100">
                <p class="text-xs text-gray-400 mb-2">Ada pertanyaan sebelum memutuskan?</p>
                <a href="https://wa.me/6281263027818?text=Halo+M2B,+saya+ingin+menanyakan+penawaran+{{ urlencode($quotation->quotation_number) }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-green-600 hover:text-green-700">
                    💬 Chat WhatsApp
                </a>
            </div>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-5">
        © {{ date('Y') }} PT. Mora Multi Berkah — Medan, Indonesia
    </p>
</div>
</body>
</html>
