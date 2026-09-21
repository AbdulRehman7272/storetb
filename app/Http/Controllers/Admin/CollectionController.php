<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function index() { return view('admin.collections.index', ['collections' => Collection::withCount('products')->orderBy('display_order')->paginate(30)]); }
    public function create() { return view('admin.collections.form', ['collection' => new Collection(['is_active' => true]), 'products' => Product::orderBy('name')->get()]); }
    public function edit(Collection $collection) { return view('admin.collections.form', ['collection' => $collection->load('products'), 'products' => Product::orderBy('name')->get()]); }
    public function store(Request $request) { $collection = Collection::create($this->payload($request)); $collection->products()->sync($request->input('product_ids', [])); return redirect()->route('admin.collections.edit', $collection)->with('status', 'Collection created.'); }
    public function update(Request $request, Collection $collection) { $collection->update($this->payload($request)); $collection->products()->sync($request->input('product_ids', [])); return back()->with('status', 'Collection updated.'); }
    public function destroy(Collection $collection)
    {
        $image = $collection->image;
        $collection->products()->detach();
        $collection->delete();

        if ($image && str_starts_with($image, 'storage/')) {
            Storage::disk('public')->delete(str($image)->after('storage/')->toString());
        }

        return back()->with('status', 'Collection deleted. Products were not deleted.');
    }
    private function payload(Request $request): array { $data = $request->validate(['name' => ['required', 'max:255'], 'slug' => ['nullable', 'max:255'], 'description' => ['nullable'], 'image' => ['nullable', 'image', 'max:5120'], 'display_order' => ['nullable', 'integer'], 'product_ids' => ['array'], 'product_ids.*' => ['exists:products,id']]); $data['slug'] = $data['slug'] ?: Str::slug($data['name']); $data['is_active'] = $request->boolean('is_active'); $data['display_order'] ??= 0; if ($request->hasFile('image')) $data['image'] = 'storage/'.$request->file('image')->store('collections', 'public'); unset($data['product_ids']); return $data; }
}
