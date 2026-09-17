@extends('layouts.admin')
@section('title', 'Pages')
@section('content')
<div class="toolbar"><a class="btn" href="{{ route('admin.pages.create') }}"><i data-lucide="file-plus-2"></i>Add Page</a></div>
<section class="panel data-panel"><div class="table-responsive"><table class="table"><thead><tr><th>Title</th><th>URL</th><th>Status</th><th></th></tr></thead><tbody>@foreach($pages as $page)<tr><td><strong>{{ $page->title }}</strong></td><td>/{{ $page->slug }}</td><td><span class="status-badge status-badge--{{ $page->is_published ? 'published' : 'inactive' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span></td><td class="cell-actions"><a class="icon-btn" href="{{ route('admin.pages.edit',$page) }}" title="Edit {{ $page->title }}"><i data-lucide="pencil"></i></a></td></tr>@endforeach</tbody></table></div></section>{{ $pages->links() }}
@endsection
