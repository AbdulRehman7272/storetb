@extends('layouts.admin')
@section('title','Collections')
@section('content')
<div class="toolbar"><a class="btn" href="{{ route('admin.collections.create') }}"><i data-lucide="plus"></i>Add Collection</a></div>
<section class="panel data-panel"><div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Products</th><th>Status</th><th></th></tr></thead><tbody>@forelse($collections as $collection)<tr><td><strong>{{ $collection->name }}</strong></td><td>{{ $collection->products_count }}</td><td><span class="status-badge status-badge--{{ $collection->is_active ? 'active' : 'inactive' }}">{{ $collection->is_active ? 'Active' : 'Disabled' }}</span></td><td class="cell-actions"><a class="icon-btn" href="{{ route('admin.collections.edit',$collection) }}" title="Edit {{ $collection->name }}"><i data-lucide="pencil"></i></a></td></tr>@empty<tr><td colspan="4">No collections found.</td></tr>@endforelse</tbody></table></div></section>{{ $collections->links() }}
@endsection
