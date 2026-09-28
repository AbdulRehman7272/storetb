@extends('layouts.admin')
@section('title', 'Orders')
@section('content')
<nav class="order-view-tabs" aria-label="Order views">
    <a class="{{ request('view','pending') === 'pending' ? 'active' : '' }}" href="{{ route('admin.orders.index',['view'=>'pending']) }}">Pending</a>
    <a class="{{ request('view') === 'arranged' ? 'active' : '' }}" href="{{ route('admin.orders.index',['view'=>'arranged']) }}">Arranged</a>
    <a class="{{ request('view') === 'all' ? 'active' : '' }}" href="{{ route('admin.orders.index',['view'=>'all']) }}">All orders</a>
</nav>
<form class="filters" method="get">
    <input type="hidden" name="view" value="{{ request('view','pending') }}">
    <input name="search" value="{{ request('search') }}" placeholder="Order, customer or mobile">
    <select name="status"><option value="">All order statuses</option>@foreach(['new','confirmed','packed','shipped','delivered','cancelled','returned','exchanged'] as $status)<option @selected(request('status')===$status)>{{ str($status)->headline() }}</option>@endforeach</select>
    <select name="payment_status"><option value="">All payments</option>@foreach(['unpaid','verification_pending','verified','rejected','partially_paid','refunded'] as $status)<option @selected(request('payment_status')===$status)>{{ str($status)->headline() }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" title="From date">
    <input type="date" name="to" value="{{ request('to') }}" title="To date">
    <div class="filter-actions"><button class="btn-small"><i data-lucide="search"></i>Filter</button><a class="icon-btn" href="{{ route('admin.orders.index',['view'=>request('view','pending')]) }}" title="Reset filters"><i data-lucide="rotate-ccw"></i></a></div>
</form>
<section class="panel data-panel"><div class="table-responsive"><table class="table">
    <thead><tr><th>Order</th><th>Customer</th><th>City</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead>
    <tbody>@forelse($orders as $order)
        <tr>
            <td><div class="order-list-product">
                <button type="button" class="order-list-images" data-order-gallery-open="{{ $order->id }}" aria-label="View all products in order {{ $order->order_number }}">
                    @foreach($order->items->take(3) as $item)
                        @php($image = $item->image ?: $item->variant?->image ?: $item->product?->primaryMedia?->path)
                        @if($image)<img src="{{ str_starts_with($image, 'http') ? $image : asset($image) }}" alt="{{ $item->product_name }}">@endif
                    @endforeach
                    @if($order->items_count > 3)<span>+{{ $order->items_count - 3 }}</span>@endif
                </button>
                <strong>{{ $order->order_number }}</strong>
            </div>
            @php($gallery = $order->items->map(function ($item) { $path = $item->image ?: $item->variant?->image ?: $item->product?->primaryMedia?->path; return $path ? ['src' => str_starts_with($path, 'http') ? $path : asset($path), 'name' => $item->product_name, 'variant' => $item->variant_name, 'quantity' => $item->quantity] : null; })->filter()->values())
            <script type="application/json" id="order-gallery-{{ $order->id }}">{!! $gallery->toJson(JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script></td>
            <td>{{ $order->customer_name }}<br><small>{{ $order->mobile }}</small></td>
            <td>{{ $order->city }}</td>
            <td><span class="status-badge status-badge--{{ $order->status }}">{{ str($order->status)->headline() }}</span></td>
            <td><span class="status-badge status-badge--{{ $order->payment_status }}">{{ str($order->payment_status)->headline() }}</span></td>
            <td>PKR {{ number_format($order->grand_total) }}</td>
            <td class="cell-actions"><div class="order-row-actions">
                <form method="post" action="{{ route('admin.orders.update',$order) }}">@csrf @method('PATCH')<input type="hidden" name="payment_status" value="{{ $order->payment_status }}"><select name="status" aria-label="Update order status" onchange="this.form.submit()">@foreach(['new','confirmed','packed','shipped','delivered','cancelled','returned','exchanged'] as $status)<option value="{{ $status }}" @selected($order->status===$status)>{{ str($status)->headline() }}</option>@endforeach</select></form>
                <a class="icon-btn" href="{{ route('admin.orders.show',$order) }}" title="View {{ $order->order_number }}"><i data-lucide="eye"></i></a>
                <form method="post" action="{{ route('admin.orders.destroy',$order) }}" onsubmit="return confirm('Delete this order permanently?')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger" title="Delete {{ $order->order_number }}"><i data-lucide="trash-2"></i></button></form>
            </div></td>
        </tr>
    @empty<tr><td colspan="7">No orders match these filters.</td></tr>@endforelse</tbody>
</table></div></section>{{ $orders->links() }}
<div class="modal fade order-gallery-modal" id="orderGalleryModal" tabindex="-1" aria-labelledby="orderGalleryTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content"><div class="modal-header"><div><small>Order products</small><h2 class="modal-title" id="orderGalleryTitle">Product images</h2></div><button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close"><i data-lucide="x"></i></button></div><div class="modal-body order-gallery-grid" data-order-gallery-grid></div></div></div></div>
@endsection
@push('scripts')<script>
document.addEventListener('click',event=>{const trigger=event.target.closest('[data-order-gallery-open]');if(!trigger)return;const source=document.querySelector(`#order-gallery-${trigger.dataset.orderGalleryOpen}`),grid=document.querySelector('[data-order-gallery-grid]');if(!source||!grid)return;const images=JSON.parse(source.textContent||'[]');grid.innerHTML=images.map(item=>`<figure><img src="${item.src}" alt=""><figcaption><strong>${escapeHtml(item.name)}</strong>${item.variant?`<span>${escapeHtml(item.variant)}</span>`:''}<small>Quantity: ${Number(item.quantity)||1}</small></figcaption></figure>`).join('');bootstrap.Modal.getOrCreateInstance(document.querySelector('#orderGalleryModal')).show()});
function escapeHtml(value){const node=document.createElement('div');node.textContent=value||'';return node.innerHTML}
</script>@endpush
