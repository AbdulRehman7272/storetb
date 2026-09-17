@extends('layouts.admin')
@section('title','Collections')
@section('content')
<div class="toolbar"><a class="btn" href="{{ route('admin.collections.create') }}">Add Collection</a></div>
<section class="panel"><table class="table"><thead><tr><th>Name</th><th>Products</th><th>Status</th><th></th></tr></thead><tbody>@foreach($collections as $collection)<tr><td>{{ $collection->name }}</td><td>{{ $collection->products_count }}</td><td>{{ $collection->is_active ? 'Active' : 'Disabled' }}</td><td><a href="{{ route('admin.collections.edit',$collection) }}">Edit</a></td></tr>@endforeach</tbody></table>{{ $collections->links() }}</section>
@endsection
