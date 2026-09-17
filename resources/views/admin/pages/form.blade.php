@extends('layouts.admin')
@section('title', $page->exists ? 'Edit Page' : 'Add Page')
@section('content')
<form class="panel form-panel" method="post" action="{{ $page->exists ? route('admin.pages.update',$page) : route('admin.pages.store') }}">@csrf @if($page->exists) @method('PUT') @endif<div class="form-grid"><label>Title<input name="title" value="{{ $page->title }}" required></label><label>Slug<input name="slug" value="{{ $page->slug }}"></label><label class="check-card"><input type="checkbox" name="is_published" value="1" @checked($page->is_published)><span>Published</span></label><label class="wide">Body<textarea name="body" rows="10">{{ $page->body }}</textarea></label><label>SEO title<input name="seo_title" value="{{ $page->seo_title }}"></label><label class="wide">SEO description<textarea name="seo_description">{{ $page->seo_description }}</textarea></label></div><button class="btn"><i data-lucide="save"></i>Save Page</button></form>
@endsection
