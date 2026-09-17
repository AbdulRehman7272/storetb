@php
    use App\Models\Category;
    use App\Support\StoreSettings;
    $settings = $settings ?? StoreSettings::allPublic();
    $navCategories = Category::query()->where('is_active', true)->where('show_in_navbar', true)->orderBy('display_order')->get();
    $storeName = $settings['store_name'] ?? 'TBrand';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $storeName)</title>
    <meta name="description" content="@yield('description', 'Shop premium fashion and lifestyle products at '.$storeName)">
    <link rel="icon" href="{{ asset($settings['favicon'] ?? 'brand/tbrand/favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="storefront">
    <div class="announcement">Free delivery over PKR {{ number_format($settings['free_shipping_threshold'] ?? 5000) }} | WhatsApp {{ $settings['whatsapp'] ?? '' }}</div>
    <header class="site-header">
        <a class="brand" href="{{ route('home') }}">
            <img src="{{ asset($settings['main_logo'] ?? 'brand/tbrand/01_Main_Horizontal_Black.png') }}" alt="{{ $storeName }}">
        </a>
        <nav class="nav-scroll">
            @foreach($navCategories as $category)
                <a href="{{ route('category.show', $category->slug) }}">{{ $category->name }}</a>
            @endforeach
        </nav>
        <form class="search" action="{{ route('search') }}">
            <input name="q" value="{{ request('q') }}" placeholder="Search products">
        </form>
        <a class="cart-link" href="{{ route('cart.index') }}">Cart</a>
    </header>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="flash error">{{ $errors->first() }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div>
            <strong>{{ $storeName }}</strong>
            <p>{{ $settings['general_email'] ?? 'info@tbrand.pk' }} | {{ $settings['support_email'] ?? 'support@tbrand.pk' }}</p>
        </div>
        <nav>
            <a href="{{ route('category.show', 'about') }}">About</a>
            <a href="{{ route('category.show', 'contact') }}">Contact</a>
            <a href="{{ route('category.show', 'faq') }}">FAQ</a>
            <a href="{{ route('category.show', 'shipping-policy') }}">Shipping</a>
            <a href="{{ route('category.show', 'return-exchange-policy') }}">Returns</a>
        </nav>
        <a href="{{ $settings['footer_credit_url'] ?? 'https://boostuplive.com' }}" target="_blank" rel="noopener">{{ $settings['footer_credit'] ?? 'Powered by BoostupLive' }}</a>
    </footer>
</body>
</html>
