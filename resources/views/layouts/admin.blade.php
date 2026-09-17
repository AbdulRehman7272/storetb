@php
    use App\Support\StoreSettings;
    $storeName = StoreSettings::get('store_name', 'TBrand');
    $logo = StoreSettings::get('main_logo', 'brand/tbrand/01_Main_Horizontal_Black.png');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - {{ $storeName }}</title>
    <link rel="icon" href="{{ asset(StoreSettings::get('favicon', 'brand/tbrand/favicon.ico')) }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/conca.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/apexcharts/apexcharts.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-logo" href="{{ route('admin.dashboard') }}"><img src="{{ asset($logo) }}" alt="{{ $storeName }}"></a>
        <nav>
            @foreach([
                ['Dashboard','admin.dashboard'],
                ['Products','admin.products.index'],
                ['Categories','admin.categories.index'],
                ['Collections','admin.collections.index'],
                ['Orders','admin.orders.index'],
                ['Customers','admin.customers.index'],
                ['Payments','admin.payment-accounts.index'],
                ['Inventory','admin.inventory.index'],
                ['Reports','admin.reports.index'],
                ['Pages','admin.pages.index'],
                ['Staff & Roles','admin.roles.index'],
                ['Settings','admin.settings.edit'],
            ] as [$label,$route])
                <a class="{{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}">{{ $label }}</a>
            @endforeach
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-top">
            <div><p class="breadcrumb-lite">Admin / @yield('title', 'Dashboard')</p><h1>@yield('title', 'Dashboard')</h1></div>
            <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="btn btn-sm btn-outline-dark">Logout</button></form>
        </header>
        @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
        @yield('content')
    </div>
</div>
<script src="{{ asset('admin-assets/vendor/libs/apexcharts/apexcharts.js') }}"></script>
@stack('scripts')
</body>
</html>
