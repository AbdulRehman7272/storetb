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
    <meta name="robots" content="{{ $meta['robots'] }}">
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $meta['image'] }}">
    <meta property="og:image:alt" content="{{ $meta['title'] }}">
    <meta property="og:site_name" content="{{ $data['store']['name'] }}">
    <meta property="og:locale" content="en_PK">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta['title'] }}">
    <meta name="twitter:description" content="{{ $meta['description'] }}">
    <meta name="twitter:image" content="{{ $meta['image'] }}">
    <link rel="icon" href="{{ asset('assets/favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--color-ink:{{ \App\Support\StoreSettings::get('primary_color', '#111111') }};--color-gold:{{ \App\Support\StoreSettings::get('accent_color', '#c8a45d') }};--color-gold-2:{{ \App\Support\StoreSettings::get('accent_color', '#c8a45d') }};--color-text:{{ \App\Support\StoreSettings::get('page_text_color', '#f7f2e7') }};--color-button-text:{{ \App\Support\StoreSettings::get('button_text_color', '#161207') }}}</style>
    @php
        $schema = ['@context' => 'https://schema.org', '@graph' => [
            ['@type' => 'Organization', '@id' => url('/').'#organization', 'name' => $data['store']['name'], 'url' => url('/'), 'logo' => $data['store']['logo']],
            ['@type' => 'WebSite', '@id' => url('/').'#website', 'name' => $data['store']['name'], 'url' => url('/'), 'publisher' => ['@id' => url('/').'#organization'], 'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
        ]];
        if ($page === 'product' && isset($data['pageContext']['product'])) {
            $item = $data['pageContext']['product'];
            $schema['@graph'][] = ['@type' => 'Product', 'name' => $item['name'], 'image' => $item['images'], 'description' => $item['short_description'], 'sku' => $item['sku'], 'offers' => ['@type' => 'Offer', 'priceCurrency' => $data['store']['currency'], 'price' => $item['price'], 'availability' => $item['stock_quantity'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => url()->current()]];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
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
