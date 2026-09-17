@extends('layouts.admin')
@section('title', 'Categories')
@section('content')
<div class="toolbar"><a class="btn" href="{{ route('admin.categories.create') }}">Add Category</a></div><form class="filters"><input name="search" value="{{ request('search') }}" placeholder="Search categories"><button class="btn-small">Filter</button></form>
<table class="table table-sm"><tr><th>Name</th><th>Root URL</th><th>Products</th><th>Navbar</th><th>Status</th><th></th></tr>@foreach($categories as $category)<tr><td>{{ $category->name }}</td><td>/{{ $category->slug }}</td><td>{{ $category->products_count }}</td><td>{{ $category->show_in_navbar ? 'Yes' : 'No' }}</td><td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td><td><a href="{{ route('admin.categories.edit',$category) }}">Edit</a></td></tr>@endforeach</table>{{ $categories->links() }}
@endsection
