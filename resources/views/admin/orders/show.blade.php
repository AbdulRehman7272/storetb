@extends('layouts.admin')
@section('title', 'Order '.$order->order_number)
@section('content')
<div class="admin-grid">
    <section class="panel"><h2>Customer</h2><p><strong>{{ $order->customer_name }}</strong><br>{{ $order->mobile }}<br>{{ $order->city }}, {{ $order->province }}</p><p>{{ $order->address }}</p><div class="page-actions" style="justify-content:flex-start"><a class="action-btn secondary" href="tel:{{ $order->mobile }}"><i data-lucide="phone"></i>Call</a><a class="action-btn" href="https://wa.me/92{{ ltrim($order->mobile,'0') }}" target="_blank"><i data-lucide="message-circle"></i>WhatsApp</a></div></section>
    <section class="panel"><h2>Order Status</h2><form class="status-form" method="post" action="{{ route('admin.orders.update',$order) }}">@csrf @method('PATCH')<select name="status">@foreach(['new','confirmed','packed','shipped','delivered','cancelled','returned','exchanged'] as $s)<option @selected($order->status===$s)>{{ $s }}</option>@endforeach</select><select name="payment_status">@foreach(['unpaid','verification_pending','verified','rejected','partially_paid','refunded'] as $s)<option @selected($order->payment_status===$s)>{{ $s }}</option>@endforeach</select><input name="courier" value="{{ $order->courier }}" placeholder="Courier"><input name="tracking_number" value="{{ $order->tracking_number }}" placeholder="Tracking number"><textarea name="internal_notes" placeholder="Internal notes">{{ $order->internal_notes }}</textarea><input name="note" placeholder="Timeline note"><button class="btn"><i data-lucide="save"></i>Update Order</button></form></section>
</div>

<section class="panel data-panel order-items-panel">
    <div class="panel-heading"><h2>Items</h2></div>
    <div class="table-responsive"><table class="table"><thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Total</th></tr></thead><tbody>
    @foreach($order->items as $item)
        @php
            $image = $item->image ?: $item->variant?->image ?: $item->product?->imageUrl();
            $variantOptions = $item->variant?->optionValues?->mapWithKeys(fn($value) => [$value->option?->name ?: 'Option' => $value->value]) ?? collect();
            $fullPrice = $item->variant?->regular_price ?: $item->product?->regular_price;
            $salePrice = $item->variant?->sale_price ?: $item->product?->sale_price;
        @endphp
        <tr class="order-product-row" data-order-item="{{ $item->id }}">
            <td><button type="button" class="order-product-trigger" data-order-product-open="{{ $item->id }}"><img src="{{ $image ? asset($image) : '' }}" alt="{{ $item->product_name }}"><span><strong>{{ $item->product_name }}</strong><small>{{ $item->variant_name }}</small><em>View details</em></span></button></td>
            <td>{{ $item->sku }}</td><td>{{ $item->quantity }}</td><td>PKR {{ number_format($item->line_total) }}</td>
        </tr>
        @push('scripts')
        <script type="application/json" id="order-item-data-{{ $item->id }}">{!! json_encode([
            'name' => $item->product_name,
            'variant' => $item->variant_name,
            'image' => $image ? asset($image) : null,
            'sku' => $item->sku,
            'options' => $variantOptions,
            'full_price' => $fullPrice ? 'PKR '.number_format($fullPrice) : null,
            'sale_price' => $salePrice ? 'PKR '.number_format($salePrice) : null,
            'unit_price' => 'PKR '.number_format($item->unit_price),
            'quantity' => $item->quantity,
            'line_total' => 'PKR '.number_format($item->line_total),
            'description' => strip_tags($item->product?->description ?: ''),
        ], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
        @endpush
    @endforeach
    </tbody></table></div>
    <div class="summary"><strong>Order Total</strong><strong>PKR {{ number_format($order->grand_total) }}</strong></div>
</section>

<section class="panel"><h2>Payment Proofs</h2><div class="proof-list">@forelse($order->proofs as $proof)<a class="action-btn secondary" href="{{ route('admin.proofs.show',$proof) }}"><i data-lucide="file-image"></i>{{ $proof->original_name }}</a>@empty<p>No proof uploaded.</p>@endforelse</div></section>

<div class="modal fade order-product-modal" id="orderProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header"><div><small>Ordered product</small><h2 class="modal-title" data-order-modal-name></h2></div><button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close"><i data-lucide="x"></i></button></div>
        <div class="modal-body order-product-modal__body">
            <img data-order-modal-image alt="Ordered product">
            <div class="order-product-modal__details"><h3 data-order-modal-variant></h3><div class="order-product-price"><strong data-order-modal-price></strong><s data-order-modal-full-price></s></div><dl data-order-modal-facts></dl><p data-order-modal-description></p></div>
        </div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-order-product-open]').forEach((button) => button.addEventListener('click', () => {
    const data = JSON.parse(document.querySelector(`#order-item-data-${button.dataset.orderProductOpen}`).textContent);
    const modalElement = document.querySelector('#orderProductModal');
    modalElement.querySelector('[data-order-modal-name]').textContent = data.name;
    modalElement.querySelector('[data-order-modal-variant]').textContent = data.variant || 'Standard product';
    const image = modalElement.querySelector('[data-order-modal-image]');
    image.src = data.image || '';
    image.hidden = !data.image;
    modalElement.querySelector('[data-order-modal-price]').textContent = data.sale_price || data.unit_price;
    const fullPrice = modalElement.querySelector('[data-order-modal-full-price]');
    fullPrice.textContent = data.sale_price && data.full_price ? data.full_price : '';
    fullPrice.hidden = !fullPrice.textContent;
    const facts = [['SKU',data.sku], ...Object.entries(data.options || {}), ['Quantity',data.quantity], ['Line total',data.line_total]];
    modalElement.querySelector('[data-order-modal-facts]').innerHTML = facts.filter(([,value]) => value).map(([label,value]) => `<div><dt>${label}</dt><dd>${value}</dd></div>`).join('');
    modalElement.querySelector('[data-order-modal-description]').textContent = data.description || '';
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}));
</script>
@endpush
