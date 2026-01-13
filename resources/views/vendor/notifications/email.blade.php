@component('mail::layout')
{{-- ================= HEADER ================= --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
M2B Portal
@endcomponent
@endslot

{{-- ================= BODY ================= --}}
@if (isset($slot) && trim($slot) !== '')
    {{ $slot }}
@else
    {{-- Fallback agar tidak error jika slot kosong --}}
    <p>
        Email ini dikirim otomatis oleh sistem <strong>M2B Portal</strong>.
        Silakan lanjutkan proses sesuai instruksi pada email ini.
    </p>
@endif

{{-- ================= SUBCOPY ================= --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- ================= FOOTER ================= --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} <strong>M2B Portal</strong><br>
PT. Mora Multi Berkah<br>
Jl. Kapten Sumarsono, Komp. Graha Metropolitan Blok G No. 14, Medan<br>
Email: <a href="mailto:sales@m2b.co.id">sales@m2b.co.id</a> |
WhatsApp: <a href="https://wa.me/6281263027818">+62 812-6302-7818</a>
@endcomponent
@endslot
@endcomponent
