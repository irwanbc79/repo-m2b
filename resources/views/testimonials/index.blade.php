<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni Pelanggan - M2B Logistics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/images/m2b-logo.png" type="image/png">

    {{-- Schema.org AggregateRating untuk Google SERP --}}
    @if($totalCount > 0)
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "Organization",
        "name": "M2B Logistics - PT. Mora Multi Berkah",
        "url": "https://portal.m2b.co.id",
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "{{ number_format($avgRating, 1) }}",
            "reviewCount": "{{ $totalCount }}",
            "bestRating": "5",
            "worstRating": "1"
        },
        "review": [
            @foreach($testimonials->take(10) as $t)
            {
                "@type": "Review",
                "author": {
                    "@type": "Organization",
                    "name": "{{ addslashes($t->display_name) }}"
                },
                "reviewRating": {
                    "@type": "Rating",
                    "ratingValue": "{{ $t->rating }}",
                    "bestRating": "5"
                },
                "reviewBody": "{{ addslashes(Str::limit($t->content, 200)) }}",
                "datePublished": "{{ $t->approved_at?->format('Y-m-d') }}"
            }{{ !$loop->last ? ',' : '' }}
            @endforeach
        ]
    }
    </script>
    @endif
</head>
<body class="bg-gray-50">

<!-- Header -->
<div class="bg-blue-900 text-white py-16 px-4 text-center">
    <img src="/images/m2b-logo.png" alt="M2B Logistics" class="h-12 mx-auto mb-6 brightness-0 invert">
    <h1 class="text-3xl font-bold mb-2">Apa Kata Pelanggan Kami</h1>
    <p class="text-blue-200 mb-6">Kepercayaan klien adalah fondasi kami</p>

    @if($totalCount > 0)
    <div class="inline-flex items-center gap-3 bg-white/10 backdrop-blur rounded-full px-6 py-3">
        <span class="text-3xl font-bold">{{ number_format($avgRating, 1) }}</span>
        <div>
            <div class="flex gap-0.5">
                @for($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= round($avgRating) ? 'text-yellow-400' : 'text-white/30' }}">★</span>
                @endfor
            </div>
            <p class="text-xs text-blue-200">dari {{ $totalCount }} ulasan</p>
        </div>
    </div>
    @endif
</div>

<!-- Testimonials Grid -->
<div class="max-w-6xl mx-auto px-4 py-16">

    @if($testimonials->isEmpty())
        <div class="text-center py-20 text-gray-400">
            <div class="text-5xl mb-4">💬</div>
            <p>Belum ada testimoni yang ditayangkan.</p>
        </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($testimonials as $t)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition">
            <!-- Rating -->
            <div class="flex gap-0.5 mb-4">
                @for($i = 1; $i <= 5; $i++)
                    <span class="text-lg {{ $i <= $t->rating ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
                @endfor
            </div>
            <!-- Konten -->
            <p class="text-gray-700 text-sm leading-relaxed mb-5 italic">"{{ $t->content }}"</p>
            <!-- Author -->
            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <div class="w-10 h-10 rounded-full bg-blue-900 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    {{ strtoupper(substr($t->display_name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-semibold text-gray-800 text-sm">{{ $t->display_name }}</p>
                    @if($t->position || $t->company_name)
                    <p class="text-xs text-gray-400">
                        {{ collect([$t->position, $t->company_name])->filter()->implode(' · ') }}
                    </p>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<!-- Footer -->
<div class="text-center py-8 text-sm text-gray-400 border-t border-gray-200">
    <p>© {{ date('Y') }} PT. Mora Multi Berkah · M2B Logistics</p>
</div>

</body>
</html>
