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
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/select2/select2.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-logo" href="{{ route('admin.dashboard') }}"><img src="{{ asset($logo) }}" alt="{{ $storeName }}"></a>
        <nav>
            @foreach([
                ['Dashboard','admin.dashboard','layout-dashboard'],
                ['Products','admin.products.index','package'],
                ['Categories','admin.categories.index','folder-tree'],
                ['Collections','admin.collections.index','layers-3'],
                ['Orders','admin.orders.index','shopping-bag'],
                ['Customers','admin.customers.index','users'],
                ['Payments','admin.payment-accounts.index','landmark'],
                ['Inventory','admin.inventory.index','warehouse'],
                ['Reports','admin.reports.index','chart-column'],
                ['Pages','admin.pages.index','files'],
                ['Staff & Roles','admin.roles.index','shield-check'],
                ['Settings','admin.settings.edit','settings'],
            ] as [$label,$route,$icon])
                <a class="{{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}"><i data-lucide="{{ $icon }}"></i><span>{{ $label }}</span></a>
            @endforeach
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-top">
            <div><p class="breadcrumb-lite">Admin / @yield('title', 'Dashboard')</p><h1>@yield('title', 'Dashboard')</h1></div>
            <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="icon-btn icon-btn--danger" title="Logout" aria-label="Logout"><i data-lucide="log-out"></i></button></form>
        </header>
        @if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
        @yield('content')
    </div>
</div>
<script src="{{ asset('admin-assets/vendor/libs/apexcharts/apexcharts.js') }}"></script>
<script src="{{ asset('admin-assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('admin-assets/vendor/libs/select2/select2.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{$('.admin-multiselect').select2({width:'100%',closeOnSelect:false,placeholder:'Select items'});});</script>
@stack('scripts')
</body>
</html>
