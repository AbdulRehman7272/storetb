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
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content admin-confirm-modal">
            <div class="modal-header">
                <div class="admin-confirm-modal__heading">
                    <span class="admin-confirm-modal__icon"><i data-lucide="triangle-alert"></i></span>
                    <h2 class="modal-title" id="deleteConfirmTitle">Confirm deletion</h2>
                </div>
                <button type="button" class="icon-btn admin-confirm-modal__close" data-bs-dismiss="modal" aria-label="Close" title="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <p>Delete <strong data-delete-name></strong> permanently?</p>
                <small data-delete-note>This item can only be deleted when it is not in use.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" data-delete-form>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn admin-danger-btn"><i data-lucide="trash-2"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('admin-assets/vendor/libs/apexcharts/apexcharts.js') }}"></script>
<script src="{{ asset('admin-assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('admin-assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('admin-assets/js/bootstrap.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{$('.admin-multiselect').select2({width:'100%',closeOnSelect:false,placeholder:'Select items'});});</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('deleteConfirmModal');
    const modal = bootstrap.Modal.getOrCreateInstance(element);
    const form = element.querySelector('[data-delete-form]');
    const name = element.querySelector('[data-delete-name]');
    const note = element.querySelector('[data-delete-note]');

    document.querySelectorAll('[data-delete-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            form.action = trigger.dataset.deleteAction;
            name.textContent = trigger.dataset.deleteName;
            const notes = {
                category: 'Categories containing products or child categories cannot be deleted.',
                collection: 'Products assigned to this collection will remain in your store.',
                product: 'Products used in an order or customer cart cannot be deleted.',
            };
            note.textContent = notes[trigger.dataset.deleteKind] || 'This item will be deleted permanently.';
            modal.show();
        });
    });
});
document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-admin-image-input]');
    if (!input || !input.files?.[0]) return;
    const picker = input.closest('[data-admin-image-picker]');
    const preview = picker.querySelector('[data-admin-image-preview]');
    const empty = picker.querySelector('[data-admin-image-empty]');
    const state = picker.querySelector('[data-admin-image-state]');
    const reader = new FileReader();
    reader.onload = () => {
        preview.src = reader.result;
        preview.hidden = false;
        empty.hidden = true;
        state.textContent = 'Selected replacement';
        picker.classList.add('has-selection');
    };
    reader.readAsDataURL(input.files[0]);
});
</script>
@stack('scripts')
</body>
</html>
