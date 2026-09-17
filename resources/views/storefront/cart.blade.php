@extends('layouts.storefront')
@section('title', 'Cart - TBrand')
@section('content')
    <section class="section narrow">
        <h1>Cart</h1>
        <form method="post" action="{{ route('cart.update') }}">
            @csrf @method('PATCH')
            @php($total = 0)
            @forelse($cart->items as $item)
                @php($line = $item->quantity * $item->unit_price) @php($total += $line)
                <div class="cart-row">
                    <img src="{{ $item->product->imageUrl() }}" alt="{{ $item->product->name }}">
                    <div><strong>{{ $item->product->name }}</strong><p>{{ $item->variant?->name }}</p></div>
                    <input type="number" name="quantities[{{ $item->id }}]" value="{{ $item->quantity }}" min="1">
                    <span>PKR {{ number_format($line) }}</span>
                    <button form="remove-{{ $item->id }}" class="link-danger">Remove</button>
                </div>
            @empty
                <div class="empty">Your cart is empty.</div>
            @endforelse
            @if($cart->items->isNotEmpty())
                <div class="summary"><strong>Subtotal</strong><strong>PKR {{ number_format($total) }}</strong></div>
                <button class="btn">Update Cart</button>
                <a class="btn secondary" href="{{ route('checkout.index') }}">Checkout</a>
            @endif
        </form>
        @foreach($cart->items as $item)
            <form id="remove-{{ $item->id }}" method="post" action="{{ route('cart.remove', $item) }}">@csrf @method('DELETE')</form>
        @endforeach
    </section>
@endsection
