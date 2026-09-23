<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\HtmlSanitizer;

class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::query()->orderBy('title')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page(['is_published' => true])]);
    }

    public function store(Request $request)
    {
        Page::query()->create($this->payload($request));
        return redirect()->route('admin.pages.index')->with('status', 'Page saved.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $page->update($this->payload($request));
        return redirect()->route('admin.pages.index')->with('status', 'Page updated.');
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['body'] = HtmlSanitizer::clean($validated['body'] ?? null);
        $validated['seo_title'] = $validated['seo_title'] ?: $validated['title'];
        $validated['seo_description'] = str($validated['seo_description'] ?: strip_tags($validated['body'] ?? ''))->squish()->limit(160, '')->toString();

        return $validated;
    }
}
