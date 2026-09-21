<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] }}</title>
    <meta name="description" content="{{ $meta['description'] }}">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $meta['description'] }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $meta['image'] ?? asset('assets/brand/monogram-gold-round.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ asset('assets/favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--color-ink:{{ \App\Support\StoreSettings::get('primary_color', '#111111') }};--color-gold:{{ \App\Support\StoreSettings::get('accent_color', '#c8a45d') }};--color-gold-2:{{ \App\Support\StoreSettings::get('accent_color', '#c8a45d') }};--color-text:{{ \App\Support\StoreSettings::get('page_text_color', '#f7f2e7') }};--color-button-text:{{ \App\Support\StoreSettings::get('button_text_color', '#161207') }}}</style>
    <script type="application/ld+json">
        {"@@context":"https://schema.org","@@type":"Organization","name":"TBrand","url":"{{ url('/') }}","logo":"{{ $data['store']['logo'] }}"}
    </script>
</head>
<body>
    <noscript>
        <div class="noscript">TBrand requires JavaScript for cart, filters, search, checkout, and product interactions.</div>
    </noscript>
    <div id="tbrand-app"></div>
    <script>
        window.TBRAND = @json($data);
        window.TBRAND.currentUrl = "{{ url()->current() }}";
    </script>
</body>
</html>
