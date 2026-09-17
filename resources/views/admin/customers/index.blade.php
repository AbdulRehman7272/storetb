@extends('layouts.admin')
@section('title', 'Customers')
@section('content')
<form class="filters"><input name="search" value="{{ request('search') }}" placeholder="Name, mobile, email"><select name="blocked"><option value="">All</option><option value="yes">Blocked</option><option value="no">Allowed</option></select><button class="btn-small">Filter</button></form><table class="table table-sm"><tr><th>Name</th><th>Mobile</th><th>Orders</th><th>Total spent</th><th>Last order</th><th>Status</th></tr>@foreach($customers as $customer)<tr><td>{{ $customer->name }}</td><td>{{ $customer->mobile }}</td><td>{{ $customer->orders_count }}</td><td>{{ number_format($customer->total_spent) }}</td><td>{{ $customer->last_order_at?->format('Y-m-d') }}</td><td>{{ $customer->is_blocked ? 'Blocked' : 'Allowed' }}</td></tr>@endforeach</table>{{ $customers->links() }}
@endsection
