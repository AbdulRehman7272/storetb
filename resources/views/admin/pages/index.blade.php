@extends('layouts.admin')
@section('title', 'Pages')
@section('content')
<div class="toolbar"><a class="btn" href="{{ route('admin.pages.create') }}">Add Page</a></div><table class="table table-sm"><tr><th>Title</th><th>URL</th><th>Status</th><th></th></tr>@foreach($pages as $page)<tr><td>{{ $page->title }}</td><td>/{{ $page->slug }}</td><td>{{ $page->is_published ? 'Published' : 'Draft' }}</td><td><a href="{{ route('admin.pages.edit',$page) }}">Edit</a></td></tr>@endforeach</table>{{ $pages->links() }}
@endsection
