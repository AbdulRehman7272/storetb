<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'category' => new Category(['is_active' => true, 'show_in_navbar' => true]),
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
        $category->update($this->payload($request));
        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->update(['is_active' => false, 'show_in_navbar' => false]);
        return back()->with('status', 'Category disabled.');
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
            'main_image_upload' => ['nullable', 'image', 'max:5120'],
            'hero_image_upload' => ['nullable', 'image', 'max:5120'],
            'banner_upload' => ['nullable', 'image', 'max:5120'],
        ]);
        foreach (['main_image', 'hero_image', 'banner'] as $field) {
            if ($request->hasFile($field.'_upload')) $validated[$field] = 'storage/'.$request->file($field.'_upload')->store('categories', 'public');
            unset($validated[$field.'_upload']);
        }
        return $validated + [
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'is_active' => $request->boolean('is_active'),
            'show_in_navbar' => $request->boolean('show_in_navbar'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }
}
