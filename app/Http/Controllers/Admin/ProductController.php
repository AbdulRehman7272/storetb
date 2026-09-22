<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Brand;
use App\Models\Collection;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with('category', 'brand', 'vendor', 'primaryMedia');

        if ($request->filled('search')) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('sku', 'like', '%' . $request->search . '%'));
        }
        foreach (['category_id', 'brand_id', 'vendor_id', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }
        if ($request->filled('stock')) {
            $query->when($request->stock === 'in', fn ($q) => $q->where('stock', '>', 5))
                ->when($request->stock === 'low', fn ($q) => $q->whereColumn('stock', '<=', 'low_stock_threshold')->where('stock', '>', 0))
                ->when($request->stock === 'out', fn ($q) => $q->where('stock', '<=', 0));
        }
        if ($request->filled('price_min')) {
            $query->where('regular_price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('regular_price', '<=', $request->price_max);
        }

        return view('admin.products.index', [
            'products' => $query->orderBy($request->get('sort', 'created_at'), $request->get('direction', 'desc'))->paginate(20)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'collections' => Collection::query()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.products.form', [
            'product' => new Product(['status' => 'draft', 'stock' => 0, 'product_type' => 'single']),
            'mode' => $request->get('mode', 'quick'),
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'collections' => Collection::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::query()->create($this->payload($request));
        $this->storeImage($request, $product);
        $this->storeVariants($request, $product);
        $product->collections()->sync($request->input('collection_ids', []));

        $route = $request->input('action') === 'another'
            ? redirect()->route('admin.products.create')->with('status', 'Product saved. Add another one.')
            : redirect()->route('admin.products.edit', $product)->with('status', 'Product saved.');

        return $route;
    }

    public function edit(Product $product)
    {
        $product->load('media', 'variants.optionValues.option', 'collections');

        return view('admin.products.form', [
            'product' => $product,
            'mode' => 'detailed',
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'collections' => Collection::query()->orderBy('name')->get(),
        ]);
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $product->update($this->payload($request, $product));
        $this->storeImage($request, $product);
        $this->storeVariants($request, $product);
        $product->collections()->sync($request->input('collection_ids', []));

        return redirect()->route('admin.products.edit', $product)->with('status', 'Product updated.');
    }

    public function checkVendorCode(Request $request)
    {
        $code = trim((string) $request->query('code'));
        if ($code === '') return response()->json(['exists' => false]);

        $product = Product::query()->where('vendor_code', $code)
            ->when($request->integer('exclude_product_id'), fn ($query, $id) => $query->whereKeyNot($id))->first();
        $variant = ProductVariant::query()->with('product')->where('vendor_code', $code)
            ->when($request->integer('exclude_variant_id'), fn ($query, $id) => $query->whereKeyNot($id))->first();
        $match = $product ?: $variant?->product;

        return response()->json([
            'exists' => (bool) $match,
            'name' => $match?->name,
            'url' => $match ? route('admin.products.edit', $match) : null,
        ]);
    }

    public function destroy(Product $product)
    {
        if (OrderItem::query()->where('product_id', $product->id)->exists() || CartItem::query()->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['delete' => 'This product is in use by an order or cart. Archive it instead of deleting it.']);
        }

        $product->load('media', 'variants');
        $product->media->each(fn ($media) => $this->deletePublicFile($media->path));
        $product->variants->each(fn ($variant) => $this->deletePublicFile($variant->image));
        $product->delete();

        return back()->with('status', 'Product deleted permanently.');
    }

    private function deletePublicFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(str($path)->after('storage/')->toString());
        }
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'action' => ['required', 'in:publish,unpublish,archive,category,discount,stock'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stock_delta' => ['nullable', 'integer'],
        ]);

        $products = Product::query()->whereIn('id', $validated['ids']);
        match ($validated['action']) {
            'publish' => $products->update(['status' => 'published', 'published_at' => now()]),
            'unpublish' => $products->update(['status' => 'draft']),
            'archive' => $products->update(['status' => 'archived']),
            'category' => $products->update(['category_id' => $validated['category_id']]),
            'discount' => $products->get()->each(fn ($product) => $product->update(['sale_price' => $product->regular_price * (1 - (($validated['discount'] ?? 0) / 100))])),
            'stock' => $products->get()->each(fn ($product) => $product->increment('stock', (int) ($validated['stock_delta'] ?? 0))),
        };

        return back()->with('status', 'Bulk action completed.');
    }

    public function duplicate(Product $product)
    {
        $copy = $product->replicate(['slug', 'sku', 'status', 'published_at']);
        $copy->name = $product->name . ' Copy';
        $copy->slug = Str::slug($copy->name) . '-' . Str::random(4);
        $copy->sku = 'TB-' . strtoupper(Str::random(8));
        $copy->status = 'draft';
        $copy->save();

        foreach ($product->media as $media) {
            ProductMedia::query()->create($media->replicate(['product_id'])->fill(['product_id' => $copy->id])->toArray());
        }

        return redirect()->route('admin.products.edit', $copy)->with('status', 'Product duplicated as draft.');
    }

    private function payload(StoreProductRequest $request, ?Product $product = null): array
    {
        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        if ($product && Product::query()->where('slug', $data['slug'])->whereKeyNot($product->id)->exists()) {
            $data['slug'] .= '-' . $product->id;
        }
        if (! $product && Product::query()->where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-' . Str::random(4);
        }
        $data['sku'] = ($data['sku'] ?? null) ?: ($product->sku ?? ('TB-' . strtoupper(Str::slug($data['name']))));
        $skuQuery = Product::query()->where('sku', $data['sku']);
        if ($product) {
            $skuQuery->whereKeyNot($product->id);
        }
        if ($skuQuery->exists()) {
            $data['sku'] .= '-' . ($product?->id ?: strtoupper(Str::random(4)));
        }
        $data['regular_price'] = $data['regular_price'] ?? 0;
        $data['stock'] = $data['stock'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['published_at'] = $data['status'] === 'published' ? ($product->published_at ?? now()) : null;
        $data['seo_title'] = $request->input('seo_title') ?: $data['name'];
        $data['seo_description'] = $request->input('seo_description');
        $data['tags'] = collect(explode(',', (string) $request->input('tags_text')))->map(fn ($tag) => trim($tag))->filter()->values()->all();
        unset($data['collection_ids'], $data['tags_text'], $data['variants'], $data['image']);

        return $data;
    }

    private function storeImage(Request $request, Product $product): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $path = $request->file('image')->store('products', 'public');
        $product->media()->update(['is_primary' => false]);
        ProductMedia::query()->create([
            'product_id' => $product->id,
            'path' => 'storage/' . $path,
            'type' => 'image',
            'alt_text' => $product->name,
            'position' => 0,
            'is_primary' => true,
        ]);
    }

    private function storeVariants(Request $request, Product $product): void
    {
        $colourOption = ProductOption::query()->firstOrCreate(['slug' => 'colour'], ['name' => 'Colour']);
        $sizeOption = ProductOption::query()->firstOrCreate(['slug' => 'size'], ['name' => 'Size']);
        $kept = [];
        $colourImages = [];
        foreach ($request->input('variants', []) as $index => $row) {
            $color = ProductOptionValue::query()->updateOrCreate(['product_option_id' => $colourOption->id, 'slug' => Str::slug($row['color'])], ['value' => $row['color'], 'swatch' => $row['swatch'] ?? '#777777']);
            $size = filled($row['size'] ?? null) ? ProductOptionValue::query()->firstOrCreate(['product_option_id' => $sizeOption->id, 'slug' => Str::slug($row['size'])], ['value' => $row['size']]) : null;
            $fullPrice = (float) (($row['regular_price'] ?? null) ?: ($row['sale_price'] ?? 0));
            $enteredSalePrice = (float) ($row['sale_price'] ?? 0);
            $salePrice = $enteredSalePrice > 0 && $enteredSalePrice < $fullPrice ? $enteredSalePrice : null;
            $variant = ProductVariant::query()->updateOrCreate(['id' => $row['id'] ?? null, 'product_id' => $product->id], ['name' => collect([$row['color'], $row['size'] ?? null])->filter()->join(' / '), 'sku' => $row['sku'], 'vendor_reference' => $row['vendor_reference'] ?? null, 'vendor_code' => ($row['vendor_code'] ?? null) ?: null, 'regular_price' => $fullPrice, 'sale_price' => $salePrice, 'cost_price' => ($row['cost_price'] ?? null) ?: null, 'stock' => $row['stock'], 'is_enabled' => (bool) ($row['enabled'] ?? false)]);
            $image = $request->file("variants.$index.image");
            if ($image?->isValid()) {
                $storedImage = $image->store('variants', 'public');
                if (! $storedImage) {
                    throw new \RuntimeException('The variant image could not be saved.');
                }
                $variant->update(['image' => 'storage/'.$storedImage]);
            } elseif (! $variant->image && isset($colourImages[Str::slug($row['color'])])) {
                $variant->update(['image' => $colourImages[Str::slug($row['color'])]]);
            }
            $removedImages = collect($row['remove_images'] ?? []);
            $gallery = collect($variant->images ?: [])->filter()->reject(fn ($path) => $removedImages->contains($path));
            $removedImages->each(fn ($path) => $this->deletePublicFile($path));
            if ($removedImages->contains($variant->image)) $variant->update(['image' => null]);
            foreach ($request->file("variants.$index.images", []) as $galleryImage) {
                if ($galleryImage->isValid()) {
                    $gallery->push('storage/'.$galleryImage->store('variants', 'public'));
                }
            }
            if ($variant->image) $gallery->prepend($variant->image);
            $gallery = $gallery->unique()->values();
            $variant->update(['images' => $gallery->all(), 'image' => $variant->image ?: $gallery->first()]);
            if ($variant->image) $colourImages[Str::slug($row['color'])] = $variant->image;
            $variant->optionValues()->sync(collect([$color->id, $size?->id])->filter());
            $kept[] = $variant->id;
        }
        if ($request->input('product_type') === 'single') {
            $product->variants()->delete();
        } elseif ($request->has('variants')) {
            $product->variants()->whereNotIn('id', $kept)->delete();
        }
        if ($kept) $product->update(['stock' => $product->variants()->sum('stock')]);
    }
}
