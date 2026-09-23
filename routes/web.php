<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PaymentAccountController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\StorefrontController;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

$adminPath = env('ADMIN_PATH', 'tbrand-control');

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/super-store', [StorefrontController::class, 'superStore'])->name('super-store');
Route::get('/sitemap.xml', function () {
    $urls = collect([['loc' => url('/'), 'lastmod' => now()->toDateString(), 'frequency' => 'daily'], ['loc' => route('shop'), 'lastmod' => now()->toDateString(), 'frequency' => 'daily']]);
    Category::where('is_active', true)->get()->each(fn ($item) => $urls->push(['loc' => route('category.show', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'frequency' => 'weekly']));
    Collection::where('is_active', true)->get()->each(fn ($item) => $urls->push(['loc' => route('collection.show', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'frequency' => 'weekly']));
    Product::where('status', 'published')->get()->each(fn ($item) => $urls->push(['loc' => route('product.show', $item->slug), 'lastmod' => $item->updated_at->toDateString(), 'frequency' => 'weekly']));
    Page::where('is_published', true)->get()->each(fn ($item) => $urls->push(['loc' => url('/'.$item->slug), 'lastmod' => $item->updated_at->toDateString(), 'frequency' => 'monthly']));
    return response()->view('storefront.sitemap', compact('urls'))->header('Content-Type', 'application/xml');
})->name('sitemap');
Route::get('/robots.txt', function () use ($adminPath) {
    return response("User-agent: *\nAllow: /\nDisallow: /{$adminPath}\nDisallow: /cart\nDisallow: /checkout\nDisallow: /search\nSitemap: ".route('sitemap')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');
Route::get('/categories', [StorefrontController::class, 'categories'])->name('categories');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('category.show');
Route::get('/shop', [StorefrontController::class, 'shop'])->name('shop');
Route::get('/collection/{slug}', [StorefrontController::class, 'collection'])->name('collection.show');
Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('product.show');
Route::get('/search', [StorefrontController::class, 'search'])->name('search');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.index');
Route::post('/cart/{product:slug}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/item/{item}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/order', [CheckoutController::class, 'storeFrontend'])->name('checkout.frontend');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->middleware('signed')->name('checkout.success');
Route::get('/order-confirmation', fn () => app(StorefrontController::class)->simple('order-confirmation'))->name('order-confirmation');
Route::get('/track-order', fn () => app(StorefrontController::class)->simple('track-order'))->name('track.index');
Route::post('/track-order', [OrderTrackingController::class, 'show'])->name('track.show');

foreach (['wishlist', 'compare', 'recently-viewed', 'about', 'contact', 'faq', 'size-guide'] as $page) {
    Route::get('/'.$page, fn () => app(StorefrontController::class)->simple($page))->name($page);
}
Route::get('/policies/{slug}', [StorefrontController::class, 'policy'])->name('policies.show');

Route::prefix($adminPath)->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('admin')->group(function () {
        Route::get('/', DashboardController::class)->middleware('permission:view-dashboard')->name('dashboard');
        Route::middleware('permission:manage-products')->group(function () {
            Route::resource('products', ProductController::class)->except('show');
            Route::get('vendor-code/check', [ProductController::class, 'checkVendorCode'])->name('vendor-code.check');
            Route::post('products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
            Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
        });
        Route::middleware('permission:manage-categories')->group(function () {
            Route::resource('categories', CategoryController::class)->except('show');
            Route::resource('collections', CollectionController::class)->except('show');
        });
        Route::resource('payment-accounts', PaymentAccountController::class)->except('show')->middleware('permission:manage-payments');
        Route::middleware('permission:manage-orders')->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
            Route::get('payment-proofs/{proof}', [OrderController::class, 'proof'])->name('proofs.show');
        });
        Route::get('customers', [CustomerController::class, 'index'])->middleware('permission:manage-customers')->name('customers.index');
        Route::get('inventory', [InventoryController::class, 'index'])->middleware('permission:manage-inventory')->name('inventory.index');
        Route::get('reports', [ReportController::class, 'index'])->middleware('permission:manage-reports')->name('reports.index');
        Route::resource('pages', AdminPageController::class)->except('show', 'destroy')->middleware('permission:manage-pages');
        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:manage-staff')->name('roles.index');
        Route::get('settings', [SettingsController::class, 'edit'])->middleware('permission:manage-settings')->name('settings.edit');
        Route::post('settings', [SettingsController::class, 'update'])->middleware('permission:manage-settings')->name('settings.update');
        Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->middleware('permission:manage-settings')->name('settings.profile.update');
    });
});

Route::get('/{slug}', [StorefrontController::class, 'category'])->name('category.legacy');
