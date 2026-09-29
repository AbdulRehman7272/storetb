<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()->withCount('products')->with('parent');
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        return view('admin.categories.index', [
            'categories' => $query->orderBy('display_order')->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(['is_active' => true, 'show_in_navbar' => true, 'margin_type' => 'flat', 'margin_value' => 0, 'discount_type' => 'percentage', 'discount_value' => 0]),
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::query()->create($this->payload($request));
        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::query()->whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        DB::transaction(function () use ($request, $category) {
            $category->update($this->payload($request));
            $this->repriceProducts($category);
        });
        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['delete' => 'This category is in use. Move its products and child categories before deleting it.']);
        }

        $category->delete();
        return back()->with('status', 'Category deleted permanently.');
    }

    private function repriceProducts(Category $category): void
    {
        $category->products()->with('variants')->get()->each(function ($product) use ($category) {
            $product->variants->each(function ($variant) use ($category) {
                if ($variant->cost_price === null) return;
                [$fullPrice, $salePrice] = $this->pricesFromCost((float) $variant->cost_price, $category);
                $variant->update(['regular_price' => $fullPrice, 'sale_price' => $salePrice]);
            });

            if ($product->variants->isNotEmpty()) {
                $product->update([
                    'regular_price' => $product->variants->min('regular_price') ?? 0,
                    'sale_price' => $product->variants->min('sale_price'),
                ]);
            } elseif ($product->cost_price !== null) {
                [$fullPrice, $salePrice] = $this->pricesFromCost((float) $product->cost_price, $category);
                $product->update(['regular_price' => $fullPrice, 'sale_price' => $salePrice]);
            }
        });
    }

    private function pricesFromCost(float $cost, Category $category): array
    {
        $margin = (float) $category->margin_value;
        $discount = (float) $category->discount_value;
        $sale = $category->margin_type === 'percentage' ? $cost * (1 + $margin / 100) : $cost + $margin;
        $full = $category->discount_type === 'percentage' && $discount > 0
            ? $sale / (1 - min($discount, 99.99) / 100)
            : $sale + $discount;
        $roundToFifty = fn (float $value) => max(0, round($value / 50) * 50);

        return [$roundToFifty($full), $roundToFifty($sale)];
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'margin_type' => ['required', 'in:flat,percentage'],
            'margin_value' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:flat,percentage'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'main_image_upload' => ['nullable', 'image', 'max:5120'],
            'hero_image_upload' => ['nullable', 'image', 'max:5120'],
            'banner_upload' => ['nullable', 'image', 'max:5120'],
        ]);
        foreach (['main_image', 'hero_image', 'banner'] as $field) {
            if ($request->hasFile($field.'_upload')) $validated[$field] = 'storage/'.$request->file($field.'_upload')->store('categories', 'public');
            unset($validated[$field.'_upload']);
        }
        $validated['description'] = HtmlSanitizer::clean($validated['description'] ?? null);
        return $validated + [
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'is_active' => $request->boolean('is_active'),
            'show_in_navbar' => $request->boolean('show_in_navbar'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }
}
