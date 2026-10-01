<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Page;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Support\StoreSettings;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

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
    public function categories() { return $this->render('categories', [], ['title' => 'All Categories', 'description' => 'Explore every shopping category.']); }
    public function shop(Request $request) { return $this->render('shop', ['query' => $request->query()], ['title' => 'Shop']); }
    public function search(Request $request) { return $this->render('search', ['query' => $request->query('q', '')], ['title' => 'Search', 'robots' => 'noindex,follow']); }
    public function cart() { return $this->render('cart', [], ['title' => 'Shopping Cart', 'robots' => 'noindex,nofollow']); }
    public function checkout() { return $this->render('checkout', [], ['title' => 'Checkout', 'robots' => 'noindex,nofollow']); }

    public function category(string $slug)
    {
        $category = Category::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        return $this->render('category', ['category' => $this->categoryData($category), 'catalogCategory' => $category->slug], ['title' => $category->seo_title ?: $category->name, 'description' => $category->seo_description ?: str($category->description)->stripTags()->squish()->limit(160, '')]);
    }

    public function collection(string $slug)
    {
        $collection = Collection::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        return $this->render('collection', ['collection' => $this->collectionData($collection), 'catalogCollection' => $collection->slug], ['title' => $collection->name, 'description' => $collection->description]);
    }

    public function product(string $slug)
    {
        $product = $this->products()->where('slug', $slug)->firstOrFail();
        $related = $this->products()->where('category_id', $product->category_id)->whereKeyNot($product->id)->limit(8)->get();
        return $this->render('product', ['product' => $this->productData($product), 'related' => $related->map(fn ($p) => $this->productData($p, false))->values()], ['title' => $product->seo_title ?: $product->name, 'description' => $product->seo_description ?: str($product->description)->stripTags()->squish()->limit(155, ''), 'image' => $product->imageUrl(), 'type' => 'product']);
    }

    public function catalog(Request $request)
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'], 'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'], 'categories' => ['nullable', 'array'], 'categories.*' => ['string', 'max:255'],
            'collection' => ['nullable', 'string', 'max:255'], 'collections' => ['nullable', 'array'], 'collections.*' => ['string', 'max:255'],
            'colors' => ['nullable', 'array'], 'colors.*' => ['string', 'max:80'],
            'sizes' => ['nullable', 'array'], 'sizes.*' => ['string', 'max:80'],
            'min' => ['nullable', 'numeric', 'min:0'], 'max' => ['nullable', 'numeric', 'min:0'],
            'available' => ['nullable', 'boolean'], 'discounted' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:newest,price-low,price-high,discount,random'],
            'slugs' => ['nullable', 'array', 'max:100'], 'slugs.*' => ['string', 'max:255'],
        ]);

        $batchSize = $this->catalogBatchSize();
        $paginator = $this->catalogQuery($validated)->paginate($batchSize)->withQueryString();

        return response()->json([
            'data' => $paginator->getCollection()->map(fn ($product) => $this->productData($product, false))->values(),
            'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()],
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function simple(string $page)
    {
        $record = Page::query()->where('slug', $page)->where('is_published', true)->first();
        $fallback = $this->defaultPage($page);
        $title = $record?->title ?: $fallback['title'];
        $body = HtmlSanitizer::clean($record?->body ?: $fallback['body']);
        $noIndex = in_array($page, ['wishlist', 'compare', 'recently-viewed', 'order-confirmation', 'track-order'], true);
        return $this->render($page, ['contentPage' => ['title' => $title, 'description' => $body]], ['title' => $record?->seo_title ?: $title, 'description' => $record?->seo_description ?: str($body)->stripTags()->squish()->limit(160, ''), 'robots' => $noIndex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large']);
    }

    public function policy(string $slug)
    {
        $page = Page::query()->whereIn('slug', [$slug, $slug === 'terms-conditions' ? 'terms-and-conditions' : $slug])->where('is_published', true)->first();
        $fallback = $this->defaultPage($slug);
        $title = $page?->title ?: $fallback['title'];
        $body = HtmlSanitizer::clean($page?->body ?: $fallback['body']);
        return $this->render('policy', ['policy' => ['title' => $title, 'description' => $body, 'items' => []]], ['title' => $page?->seo_title ?: $title, 'description' => $page?->seo_description ?: str($body)->stripTags()->squish()->limit(160, '')]);
    }

    private function defaultPage(string $slug): array
    {
        return match ($slug) {
            'about' => ['title' => 'About TBrand', 'body' => '<p>TBrand brings thoughtfully selected Pakistani fashion to customers who value style, quality, and dependable service.</p><p>We focus on clear product presentation, practical shopping, and responsive customer support.</p>'],
            'contact' => ['title' => 'Contact TBrand', 'body' => '<p>Contact our customer care team for help with products, sizing, delivery, payments, or exchanges.</p>'],
            'faq' => ['title' => 'Frequently Asked Questions', 'body' => '<p>Find answers about ordering, payment, delivery, sizing, and exchanges at TBrand.</p>'],
            'size-guide' => ['title' => 'Size Guide', 'body' => '<p>Use the measurements shown on each product and compare them with a similar garment that fits you comfortably.</p>'],
            'shipping-policy' => ['title' => 'Shipping Policy', 'body' => '<h2>Delivery across Pakistan</h2><p>TBrand processes confirmed orders as quickly as possible. Estimated delivery is normally 2-5 working days, depending on destination and courier availability.</p><h2>Delivery charges</h2><p>Shipping charges and free-shipping eligibility are displayed during checkout before the order is placed.</p><h2>Order updates</h2><p>Customers may contact TBrand support with their order number for delivery assistance.</p>'],
            'return-exchange-policy' => ['title' => 'Returns & Exchanges', 'body' => '<h2>Exchange eligibility</h2><p>Unused, unworn items may be requested for exchange within 14 days of delivery. Items must retain their original condition, packaging, and labels.</p><h2>Reporting an issue</h2><p>Please contact TBrand promptly with your order number and clear photos if an item arrives damaged or incorrect.</p><h2>Non-returnable items</h2><p>Used, altered, washed, or customer-damaged products cannot be accepted.</p>'],
            'privacy-policy' => ['title' => 'Privacy Policy', 'body' => '<h2>Information we collect</h2><p>TBrand collects the contact, delivery, and payment-reference information needed to process and support customer orders.</p><h2>How information is used</h2><p>Customer information is used for order fulfilment, delivery updates, support, fraud prevention, and legal record keeping.</p><h2>Information sharing</h2><p>Necessary delivery details may be shared with payment and courier partners. TBrand does not sell customer information.</p><h2>Your choices</h2><p>You may contact TBrand to request correction of inaccurate personal information.</p>'],
            'terms-conditions', 'terms-and-conditions' => ['title' => 'Terms & Conditions', 'body' => '<h2>Orders and availability</h2><p>Orders are subject to product availability, successful confirmation, and the prices shown at checkout.</p><h2>Product presentation</h2><p>We aim to present colours and details accurately, but screens, lighting, and fabric batches may create slight differences.</p><h2>Payments and delivery</h2><p>Customers must provide accurate contact and delivery information. Advance payments are verified before fulfilment.</p><h2>Use of this website</h2><p>Website content and product photography may not be copied or reused without permission from TBrand.</p>'],
            default => ['title' => str($slug)->replace('-', ' ')->title()->toString(), 'body' => '<p>Information for TBrand customers.</p>'],
        };
    }

    private function render(string $page, array $context = [], array $meta = [])
    {
        $categories = Cache::remember('storefront:categories:v1', 300, fn () => Category::query()
            ->with(['parent', 'children' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')])
            ->where('is_active', true)->orderBy('display_order')->orderBy('id')->get());
        $collections = Cache::remember('storefront:collections:v1', 300, fn () => Collection::query()
            ->where('is_active', true)->orderBy('display_order')->get());
        $catalogFilters = array_filter([
            'category' => $context['catalogCategory'] ?? null,
            'collection' => $context['catalogCollection'] ?? null,
            'q' => $page === 'search' ? ($context['query'] ?? '') : null,
        ]);
        $catalogPages = ['home', 'shop', 'search', 'category', 'collection'];
        $batchSize = $this->catalogBatchSize();
        $catalogPage = in_array($page, $catalogPages, true) ? $this->catalogQuery($catalogFilters)->paginate($batchSize) : null;
        $products = $catalogPage ? $catalogPage->getCollection()->map(fn ($product) => $this->productData($product, false))->values() : collect();
        $categoryData = $categories->map(fn ($category) => $this->categoryData($category))->values();
        $sliderType = StoreSettings::get('slider_content_type', 'categories');
        $sliderRandom = (bool) StoreSettings::get('slider_random', false);
        if ($sliderType === 'products') {
            $selectedIds = collect(StoreSettings::get('slider_product_ids', []))->map(fn ($id) => (int) $id)->filter();
            $selectedProducts = $sliderRandom
                ? $products->shuffle()->take(12)->values()
                : $this->products()->whereKey($selectedIds)->get()->sortBy(fn ($product) => $selectedIds->search($product->id))->map(fn ($product) => $this->productData($product, false))->values();
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
                'mobile_product_columns' => (int) StoreSettings::get('mobile_product_columns', 1),
                'catalog_display_mode' => StoreSettings::get('catalog_display_mode', 'load_more'),
                'catalog_batch_size' => $batchSize,
                'coupon' => ['code' => 'TBRAND500', 'amount' => 500],
            ],
            'categories' => $categoryData, 'collections' => $collections->map(fn ($collection) => $this->collectionData($collection))->values(),
            'products' => $products, 'allProducts' => $products,
            'catalogEndpoint' => route('catalog.products'),
            'catalogMeta' => ['current_page' => $catalogPage?->currentPage() ?? 1, 'last_page' => $catalogPage?->lastPage() ?? 1, 'per_page' => $batchSize, 'total' => $catalogPage?->total() ?? 0],
            'featuredProducts' => $products->where('featured', true)->values(), 'bestSellers' => $products->sortByDesc('reviews')->take(8)->values(), 'newArrivals' => $products->take(8)->values(),
            'categoryPromotions' => $categoryData,
            'paymentAccounts' => in_array($page, ['checkout', 'cart'], true) ? PaymentAccount::query()->where('is_active', true)->orderBy('display_order')->get() : [],
            'shippingMethods' => in_array($page, ['checkout', 'cart'], true) ? ShippingMethod::query()->where('is_active', true)->get() : [],
            'pageContext' => $context,
            'sliderItems' => $sliderItems,
        ];
        $storeName = StoreSettings::get('store_name', 'TBrand');
        $defaultTitle = StoreSettings::get('seo_title', $storeName.' | Premium Pakistani Super Store');
        $meta = array_merge(['title' => $defaultTitle, 'description' => StoreSettings::get('seo_description', 'Shop premium fashion and lifestyle products online in Pakistan.'), 'image' => $this->assetUrl(StoreSettings::get('seo_social_image')) ?: asset('assets/brand/monogram-gold-round.png'), 'type' => 'website', 'robots' => 'index,follow,max-image-preview:large'], $meta);
        if ($meta['title'] !== $defaultTitle && ! str_contains($meta['title'], $storeName)) $meta['title'] .= ' | '.$storeName;
        $meta['description'] = str($meta['description'])->stripTags()->squish()->limit(160, '')->toString();
        return view('storefront.page', ['page' => $page, 'data' => $payload, 'meta' => $meta]);
    }

    private function products() { return Product::query()->with($this->productRelations())->where('status', 'published'); }
    private function productRelations(): array
    {
        return [
            'category:id,slug,name',
            'media:id,product_id,path,position,is_primary',
            'variants' => fn ($query) => $query->select(['id', 'product_id', 'name', 'sku', 'image', 'images', 'regular_price', 'sale_price', 'stock', 'is_enabled']),
            'variants.optionValues' => fn ($query) => $query->select(['product_option_values.id', 'product_option_id', 'value', 'slug', 'swatch']),
            'variants.optionValues.option:id,slug',
            'collections:id,slug,name',
        ];
    }

    private function catalogQuery(array $filters = []): Builder
    {
        $query = $this->products()->select([
            'id', 'category_id', 'name', 'slug', 'sku', 'description', 'regular_price', 'sale_price',
            'stock', 'low_stock_threshold', 'tags', 'product_type', 'color', 'swatch', 'size',
            'is_featured', 'published_at', 'created_at',
        ]);
        if (! empty($filters['slugs'])) $query->whereIn('slug', $filters['slugs']);
        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('tags', 'like', $term));
        }
        $categories = array_values(array_filter(array_merge((array) ($filters['categories'] ?? []), (array) ($filters['category'] ?? []))));
        $collections = array_values(array_filter(array_merge((array) ($filters['collections'] ?? []), (array) ($filters['collection'] ?? []))));
        if ($categories) $query->whereHas('category', fn ($q) => $q->whereIn('slug', $categories));
        if ($collections) $query->whereHas('collections', fn ($q) => $q->whereIn('slug', $collections));
        if (! empty($filters['colors'])) $query->whereHas('variants.optionValues', fn ($q) => $q->whereIn('value', $filters['colors']));
        if (! empty($filters['sizes'])) $query->whereHas('variants.optionValues', fn ($q) => $q->whereIn('value', $filters['sizes']));
        if (isset($filters['min'])) $query->where(fn ($q) => $q->where('sale_price', '>=', $filters['min'])->orWhere(fn ($q) => $q->whereNull('sale_price')->where('regular_price', '>=', $filters['min'])));
        if (isset($filters['max'])) $query->where(fn ($q) => $q->where('sale_price', '<=', $filters['max'])->orWhere(fn ($q) => $q->whereNull('sale_price')->where('regular_price', '<=', $filters['max'])));
        if (! empty($filters['available'])) $query->where(fn ($q) => $q->where('stock', '>', 0)->orWhereHas('variants', fn ($v) => $v->where('is_enabled', true)->where('stock', '>', 0)));
        if (! empty($filters['discounted'])) $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'regular_price');
        return match ($filters['sort'] ?? 'newest') {
            'price-low' => $query->orderByRaw('COALESCE(sale_price, regular_price) asc'),
            'price-high' => $query->orderByRaw('COALESCE(sale_price, regular_price) desc'),
            'discount' => $query->orderByRaw('(regular_price - COALESCE(sale_price, regular_price)) desc'),
            'random' => $query->orderByRaw("CRC32(CONCAT(id, 'tbrand'))"),
            default => $query->latest('published_at')->latest('id'),
        };
    }

    private function catalogBatchSize(): int
    {
        return max(1, min(48, (int) StoreSettings::get('catalog_batch_size', 24)));
    }

    private function productData(Product $product, bool $full = true): array
    {
        $variants = $product->variants->where('is_enabled', true)->map(function ($variant) use ($full) {
            $colour = $variant->optionValues->firstWhere('option.slug', 'colour');
            $size = $variant->optionValues->firstWhere('option.slug', 'size');
            $images = collect($variant->images ?: [])->prepend($variant->image)->filter()->unique()->when(! $full, fn ($items) => $items->take(2))->map(fn ($image) => $this->assetUrl($image, $full ? 1600 : 900))->values();
            return ['id' => $variant->id, 'name' => $variant->name, 'sku' => $variant->sku, 'color' => $colour?->value, 'swatch' => $colour?->swatch, 'size' => $size?->value, 'price' => $variant->price(), 'original_price' => $variant->sale_price !== null && $variant->sale_price < $variant->regular_price ? (float) $variant->regular_price : null, 'stock_quantity' => $variant->stock, 'image' => $images->first(), 'images' => $images];
        })->values();
        $images = collect($product->media->when(! $full, fn ($items) => $items->take(2))->map(fn ($media) => $this->assetUrl($media->path, $full ? 1600 : 900))->filter()->all())
            ->merge($variants->flatMap(fn ($variant) => $variant['images'])->filter()->all())
            ->unique()
            ->values();
        if ($images->isEmpty()) $images->push($product->imageUrl());
        $lowestVariant = $variants->sortBy('price')->first();
        $price = $product->product_type === 'variant' && $lowestVariant ? $lowestVariant['price'] : $product->price();
        $originalPrice = $product->product_type === 'variant' && $lowestVariant ? $lowestVariant['original_price'] : ($product->sale_price !== null && $product->sale_price < $product->regular_price ? (float) $product->regular_price : null);
        $colors = $variants->pluck('color')->filter()->unique()->values();
        $sizes = $variants->pluck('size')->filter()->unique()->values();
        $swatches = $variants->filter(fn ($v) => $v['color'])->mapWithKeys(fn ($v) => [$v['color'] => $v['swatch']])->all();
        if ($product->product_type === 'single' && $product->color) {
            $colors = collect([$product->color]);
            $swatches = [$product->color => $product->swatch ?: '#777777'];
        }
        if ($product->product_type === 'single' && $product->size) $sizes = collect([$product->size]);
        $data = ['id' => $product->id, 'slug' => $product->slug, 'name' => $product->name, 'category' => $product->category?->slug, 'subcategory' => $product->category?->name, 'fabric' => collect($product->tags)->first() ?: 'Premium', 'price' => $price, 'original_price' => $originalPrice, 'rating' => 5, 'reviews' => 0, 'stock' => $product->stock > $product->low_stock_threshold ? 'In stock' : ($product->stock > 0 ? 'Low stock' : 'Out of stock'), 'stock_quantity' => $product->stock, 'sku' => $product->sku, 'colors' => $colors, 'colorSwatches' => $swatches, 'sizes' => $sizes, 'variants' => $variants, 'collections' => $product->collections->pluck('slug')->values(), 'featured' => $product->is_featured, 'images' => $images, 'short_description' => str(preg_replace('/<[^>]+>/', ' ', $product->description ?? ''))->squish()->limit(150)->toString()];
        if ($full) $data += ['description' => $product->description, 'care' => $product->shipping_information ?: 'Follow the care instructions on the product label.'];
        return $data;
    }

    private function categoryData(Category $category): array
    {
        $fallback = 'assets/catalog/category-accessories.webp';
        return ['id' => $category->id, 'slug' => $category->slug, 'name' => $category->name, 'group' => $category->group_name ?: ($category->parent?->name ?: 'Shop'), 'description' => HtmlSanitizer::clean($category->description) ?: '', 'image' => $this->assetUrl($category->main_image ?: $fallback, 700), 'hero' => $this->assetUrl($category->hero_image ?: $category->banner ?: $category->main_image ?: $fallback, 1920), 'banner' => $this->assetUrl($category->banner ?: $category->hero_image ?: $category->main_image ?: $fallback, 1600), 'featured' => $category->is_featured, 'sections' => $category->children->pluck('name')->values(), 'url' => route('category.show', $category->slug)];
    }

    private function collectionData(Collection $collection): array { return ['slug' => $collection->slug, 'name' => $collection->name, 'description' => $collection->description ?: '', 'image' => $this->assetUrl($collection->image ?: 'assets/catalog/banner-accessories.webp', 1600)]; }

    private function assetUrl(?string $path, int $maxWidth = 1600): ?string
    {
        if (! $path || str_starts_with($path, 'http')) return $path;
        $relative = ltrim($path, '/');
        if (str_ends_with(strtolower($relative), '.webp')) return asset($relative);
        $source = public_path($relative);
        if (! is_file($source) || ! extension_loaded('gd')) return asset($relative);
        $targetRelative = preg_replace('/\.[^.]+$/', '-'.$maxWidth.'.webp', $relative);
        $target = public_path($targetRelative);
        if (is_file($target) && filemtime($target) >= filemtime($source)) return asset($targetRelative);
        $info = @getimagesize($source);
        $creator = match ($info['mime'] ?? '') { 'image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', default => null };
        if (! $creator || ! function_exists($creator)) return asset($relative);
        $image = @$creator($source);
        if (! $image) return asset($relative);
        $width = imagesx($image); $height = imagesy($image);
        $newWidth = min($width, $maxWidth);
        $newHeight = max(1, (int) round($height * ($newWidth / $width)));
        $output = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($output, false); imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefill($output, 0, 0, $transparent);
        imagecopyresampled($output, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        if (! is_dir(dirname($target))) @mkdir(dirname($target), 0775, true);
        @imagewebp($output, $target, 82);
        imagedestroy($output);
        imagedestroy($image);
        return asset(is_file($target) ? $targetRelative : $relative);
    }
}
