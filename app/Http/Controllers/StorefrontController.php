<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Page;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Support\StoreSettings;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home()
    {
        $categories = Category::query()->where('is_active', true)->orderBy('display_order')->orderBy('id')->get();
        $showSuperStore = (bool) StoreSettings::get('show_super_store', $categories->count() > 1);
        $landing = $categories->firstWhere('slug', StoreSettings::get('homepage_category_slug'));

        if ($landing || (! $showSuperStore && $categories->isNotEmpty())) {
            return $this->category(($landing ?: $categories->first())->slug);
        }

        return $this->render('home');
    }

    public function superStore()
    {
        $categoryCount = Category::query()->where('is_active', true)->count();
        abort_unless(StoreSettings::get('show_super_store', $categoryCount > 1), 404);

        return $this->render('home');
    }
    public function categories() { return $this->render('categories', [], ['title' => 'All Categories | TBrand', 'description' => 'Explore every TBrand shopping category.']); }
    public function shop(Request $request) { return $this->render('shop', ['query' => $request->query()]); }
    public function search(Request $request) { return $this->render('search', ['query' => $request->query('q', '')]); }
    public function cart() { return $this->render('cart'); }
    public function checkout() { return $this->render('checkout'); }

    public function category(string $slug)
    {
        $category = Category::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $products = $this->products()->where('category_id', $category->id)->get();
        return $this->render('category', ['category' => $this->categoryData($category), 'products' => $products->map(fn ($p) => $this->productData($p))->values()], ['title' => ($category->seo_title ?: $category->name).' | TBrand', 'description' => $category->seo_description ?: $category->description]);
    }

    public function collection(string $slug)
    {
        $collection = Collection::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $products = $collection->products()->with($this->productRelations())->where('status', 'published')->get();
        return $this->render('collection', ['collection' => $this->collectionData($collection), 'products' => $products->map(fn ($p) => $this->productData($p))->values()]);
    }

    public function product(string $slug)
    {
        $product = $this->products()->where('slug', $slug)->firstOrFail();
        $related = $this->products()->where('category_id', $product->category_id)->whereKeyNot($product->id)->limit(8)->get();
        return $this->render('product', ['product' => $this->productData($product), 'related' => $related->map(fn ($p) => $this->productData($p))->values()], ['title' => ($product->seo_title ?: $product->name).' | TBrand', 'description' => $product->seo_description ?: str($product->description)->stripTags()->limit(155), 'image' => $product->imageUrl(), 'type' => 'product']);
    }

    public function simple(string $page)
    {
        $record = Page::query()->where('slug', $page)->where('is_published', true)->first();
        return $this->render($page, ['contentPage' => ['title' => $record?->title ?: str($page)->replace('-', ' ')->title(), 'description' => $record?->body ?: '']]);
    }

    public function policy(string $slug)
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
        return $this->render('policy', ['policy' => ['title' => $page->title, 'description' => $page->body, 'items' => []]]);
    }

    private function render(string $page, array $context = [], array $meta = [])
    {
        $categories = Category::query()->with('parent')->where('is_active', true)->orderBy('display_order')->get();
        $collections = Collection::query()->where('is_active', true)->orderBy('display_order')->get();
        $products = $this->products()->latest()->get()->map(fn ($product) => $this->productData($product))->values();
        $categoryData = $categories->map(fn ($category) => $this->categoryData($category))->values();
        $sliderType = StoreSettings::get('slider_content_type', 'categories');
        $sliderRandom = (bool) StoreSettings::get('slider_random', false);
        if ($sliderType === 'products') {
            $selectedProducts = $sliderRandom
                ? $products->shuffle()->take(12)->values()
                : collect(StoreSettings::get('slider_product_ids', []))->map(fn ($id) => $products->firstWhere('id', (int) $id))->filter()->values();
            $sliderItems = $selectedProducts->flatMap(function ($product) {
                if (count($product['variants'] ?? []) === 0) {
                    return [['id' => $product['id'], 'product_id' => $product['id'], 'name' => $product['name'], 'image' => $product['images'][0] ?? null, 'url' => route('product.show', $product['slug']), 'type' => 'product']];
                }
                return collect($product['variants'])->unique('color')->map(fn ($variant) => [
                    'id' => $variant['id'],
                    'product_id' => $product['id'],
                    'name' => $product['name'].' - '.$variant['color'],
                    'image' => $variant['images'][0] ?? $variant['image'] ?? $product['images'][0] ?? null,
                    'url' => route('product.show', $product['slug']).'?sku='.urlencode($variant['sku']),
                    'type' => 'variant',
                ]);
            })->filter(fn ($item) => filled($item['image']))->shuffle()->values();
            $separatedItems = collect();
            while ($sliderItems->isNotEmpty()) {
                $lastProductId = $separatedItems->last()['product_id'] ?? null;
                $nextKey = $sliderItems->search(fn ($item) => $item['product_id'] !== $lastProductId);
                if ($nextKey === false) $nextKey = $sliderItems->keys()->first();
                $separatedItems->push($sliderItems->get($nextKey));
                $sliderItems->forget($nextKey);
            }
            $sliderItems = $separatedItems->values();
        } else {
            $sliderSource = $categoryData->map(fn ($category) => ['id' => $category['id'], 'name' => $category['name'], 'image' => $category['image'], 'url' => $category['url'], 'type' => 'category']);
            if ($sliderRandom) {
                $sliderItems = $sliderSource->shuffle()->take(12)->values();
            } else {
                $selectedIds = collect(StoreSettings::get('slider_category_ids', []));
                $sliderById = $sliderSource->keyBy('id');
                $sliderItems = $selectedIds->map(fn ($id) => $sliderById->get((int) $id))->filter()->values();
            }
        }
        $payload = [
            'page' => $page, 'baseUrl' => url('/'), 'csrfToken' => csrf_token(),
            'store' => [
                'name' => StoreSettings::get('store_name', 'TBrand'),
                'logo' => asset(StoreSettings::get('main_logo', 'assets/brand/logo-gold-black.png')),
                'currency' => StoreSettings::get('currency', 'PKR'),
                'whatsapp' => preg_replace('/\D/', '', StoreSettings::get('whatsapp', '923076690892')),
                'general_email' => StoreSettings::get('general_email', 'info@tbrand.pk'),
                'support_email' => StoreSettings::get('support_email', 'support@tbrand.pk'),
                'address' => StoreSettings::get('address', 'Pakistan'),
                'show_footer_contact' => (bool) StoreSettings::get('show_footer_contact', true),
                'show_super_store' => (bool) StoreSettings::get('show_super_store', $categories->count() > 1),
                'hero_image' => $this->assetUrl(StoreSettings::get('homepage_hero_image')) ?: ($categoryData->first()['hero'] ?? asset('assets/catalog/banner-accessories.webp')),
                'hero_slogan' => StoreSettings::get('homepage_hero_slogan', 'Style · Quality · Trust'),
                'hero_title' => StoreSettings::get('homepage_hero_title', 'Premium TBrand Store'),
                'hero_description' => StoreSettings::get('homepage_hero_description', 'Explore quality products selected for your store.'),
                'shipping_fee' => (float) StoreSettings::get('shipping_charge', 250),
                'free_shipping_threshold' => (float) StoreSettings::get('free_shipping_threshold', 5000),
                'advance_payment_free_shipping' => (bool) StoreSettings::get('advance_payment_free_shipping', false),
                'advance_payment_discount' => (float) StoreSettings::get('advance_payment_discount', 0),
                'coupon' => ['code' => 'TBRAND500', 'amount' => 500],
            ],
            'categories' => $categoryData, 'collections' => $collections->map(fn ($collection) => $this->collectionData($collection))->values(),
            'products' => $context['products'] ?? $products, 'allProducts' => $products,
            'featuredProducts' => $products->where('featured', true)->values(), 'bestSellers' => $products->sortByDesc('reviews')->take(8)->values(), 'newArrivals' => $products->take(8)->values(),
            'categoryPromotions' => $categoryData, 'paymentAccounts' => PaymentAccount::query()->where('is_active', true)->orderBy('display_order')->get(), 'shippingMethods' => ShippingMethod::query()->where('is_active', true)->get(), 'pageContext' => $context,
            'sliderItems' => $sliderItems,
        ];
        return view('storefront.page', ['page' => $page, 'data' => $payload, 'meta' => array_merge(['title' => StoreSettings::get('seo_title', 'TBrand | Premium Pakistani Super Store'), 'description' => StoreSettings::get('seo_description', 'Shop premium fashion, bedding, shoes, watches and accessories online in Pakistan.'), 'image' => asset('assets/brand/monogram-gold-round.png'), 'type' => 'website'], $meta)]);
    }

    private function products() { return Product::query()->with($this->productRelations())->where('status', 'published'); }
    private function productRelations(): array { return ['category', 'media', 'variants.optionValues.option', 'collections']; }

    private function productData(Product $product): array
    {
        $variants = $product->variants->where('is_enabled', true)->map(function ($variant) {
            $colour = $variant->optionValues->firstWhere('option.slug', 'colour');
            $size = $variant->optionValues->firstWhere('option.slug', 'size');
            $images = collect($variant->images ?: [])->prepend($variant->image)->filter()->unique()->map(fn ($image) => $this->assetUrl($image))->values();
            return ['id' => $variant->id, 'name' => $variant->name, 'sku' => $variant->sku, 'color' => $colour?->value, 'swatch' => $colour?->swatch, 'size' => $size?->value, 'price' => $variant->price(), 'original_price' => $variant->sale_price ? (float) $variant->regular_price : null, 'stock_quantity' => $variant->stock, 'image' => $images->first(), 'images' => $images];
        })->values();
        $images = collect($product->media->map(fn ($media) => $this->assetUrl($media->path))->filter()->all())
            ->merge($variants->flatMap(fn ($variant) => $variant['images'])->filter()->all())
            ->unique()
            ->values();
        if ($images->isEmpty()) $images->push($product->imageUrl());
        $lowestVariant = $variants->sortBy('price')->first();
        $price = $product->product_type === 'variant' && $lowestVariant ? $lowestVariant['price'] : $product->price();
        $originalPrice = $product->product_type === 'variant' && $lowestVariant ? $lowestVariant['original_price'] : ($product->sale_price ? (float) $product->regular_price : null);
        $colors = $variants->pluck('color')->filter()->unique()->values();
        $sizes = $variants->pluck('size')->filter()->unique()->values();
        $swatches = $variants->filter(fn ($v) => $v['color'])->mapWithKeys(fn ($v) => [$v['color'] => $v['swatch']])->all();
        if ($product->product_type === 'single' && $product->color) {
            $colors = collect([$product->color]);
            $swatches = [$product->color => $product->swatch ?: '#777777'];
        }
        if ($product->product_type === 'single' && $product->size) $sizes = collect([$product->size]);
        return ['id' => $product->id, 'slug' => $product->slug, 'name' => $product->name, 'category' => $product->category?->slug, 'subcategory' => $product->category?->name, 'fabric' => collect($product->tags)->first() ?: 'Premium', 'price' => $price, 'original_price' => $originalPrice, 'badge' => $product->is_featured ? 'Featured' : null, 'rating' => 5, 'reviews' => 0, 'stock' => $product->stock > $product->low_stock_threshold ? 'In stock' : ($product->stock > 0 ? 'Low stock' : 'Out of stock'), 'stock_quantity' => $product->stock, 'sku' => $product->sku, 'colors' => $colors, 'colorSwatches' => $swatches, 'sizes' => $sizes, 'variants' => $variants, 'collections' => $product->collections->pluck('slug')->values(), 'featured' => $product->is_featured, 'images' => $images, 'short_description' => str($product->description)->stripTags()->limit(150)->toString(), 'description' => $product->description, 'care' => $product->shipping_information ?: 'Follow the care instructions on the product label.'];
    }

    private function categoryData(Category $category): array
    {
        $fallback = 'assets/catalog/category-accessories.webp';
        return ['id' => $category->id, 'slug' => $category->slug, 'name' => $category->name, 'group' => $category->group_name ?: ($category->parent?->name ?: 'Shop'), 'description' => $category->description ?: '', 'image' => $this->assetUrl($category->main_image ?: $fallback), 'hero' => $this->assetUrl($category->hero_image ?: $category->banner ?: $category->main_image ?: $fallback), 'banner' => $this->assetUrl($category->banner ?: $category->hero_image ?: $category->main_image ?: $fallback), 'featured' => $category->is_featured, 'sections' => $category->children()->where('is_active', true)->pluck('name')->values(), 'url' => route('category.show', $category->slug)];
    }

    private function collectionData(Collection $collection): array { return ['slug' => $collection->slug, 'name' => $collection->name, 'description' => $collection->description ?: '', 'image' => $this->assetUrl($collection->image ?: 'assets/catalog/banner-accessories.webp')]; }
    private function assetUrl(?string $path): ?string { return ! $path ? null : (str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'))); }
}
