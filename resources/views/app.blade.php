<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google-site-verification" content="ofSIu2__1UPOEyEbw33HJhPcgLBGaslCua19xjAgPB0" />

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @php
            $clinic = \App\Models\ClinicProfile::current();
        @endphp

        {{--
            Favicon — URL kekal tetap supaya Google tidak memaparkan ikon lama.
            Fail ini dijana daripada logo klinik semasa (Tetapan → Profil
            Klinik) oleh App\Services\FaviconGenerator; jalankan
            `php artisan clinic:favicons` untuk menjana semula secara manual.
        --}}
        <link rel="icon" href="{{ url('/favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ url('/favicon-192x192.png') }}">
        <link rel="apple-touch-icon" href="{{ url('/apple-touch-icon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @if (($page['component'] ?? null) === 'Landing')
            {{--
                Data berstruktur untuk halaman awam — memberitahu Google logo
                rasmi klinik supaya panel pengetahuan tidak menggunakan imej
                lama yang dirangkak sebelum ini.
            --}}
            <link rel="canonical" href="{{ url('/') }}">
            <meta name="description" content="{{ $clinic->name }}{{ $clinic->tagline ? ' — '.$clinic->tagline : '' }}. {{ $clinic->address_full }}.">

            <meta property="og:type" content="website">
            <meta property="og:site_name" content="{{ $clinic->name }}">
            <meta property="og:title" content="{{ $clinic->name }}">
            <meta property="og:description" content="{{ $clinic->tagline }}">
            <meta property="og:url" content="{{ url('/') }}">
            <meta property="og:image" content="{{ url('/favicon-512x512.png') }}">
            <meta name="twitter:card" content="summary">

            @php
                $clinicSchema = array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'MedicalClinic',
                    'name' => $clinic->name,
                    'description' => $clinic->tagline,
                    'url' => url('/'),
                    'logo' => url('/favicon-512x512.png'),
                    'image' => url('/favicon-512x512.png'),
                    'telephone' => $clinic->phone,
                    'email' => $clinic->email,
                    'address' => array_filter([
                        '@type' => 'PostalAddress',
                        'streetAddress' => $clinic->address,
                        'addressLocality' => $clinic->city,
                        'postalCode' => $clinic->postcode,
                        'addressRegion' => $clinic->state,
                        'addressCountry' => 'MY',
                    ]),
                    'geo' => $clinic->latitude && $clinic->longitude ? [
                        '@type' => 'GeoCoordinates',
                        'latitude' => (float) $clinic->latitude,
                        'longitude' => (float) $clinic->longitude,
                    ] : null,
                    'hasMap' => $clinic->google_maps_url,
                ], fn ($value) => $value !== null && $value !== '');
            @endphp

            <script type="application/ld+json">
                @json($clinicSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            </script>
        @endif

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
