@extends('layouts.storefront')
@section('title', 'Checkout - TBrand')
@section('content')
    <section class="checkout">
        <form method="post" action="{{ route('checkout.store') }}" enctype="multipart/form-data" class="checkout-form">
            @csrf
            <h1>Checkout</h1>
            <div class="form-grid">
                <input name="full_name" placeholder="Full name" value="{{ old('full_name') }}" required>
                <input name="mobile" placeholder="Mobile number" value="{{ old('mobile') }}" required>
                <input name="secondary_mobile" placeholder="Secondary number">
                <input name="email" type="email" placeholder="Email">
                <input name="province" placeholder="Province" required>
                <input name="city" placeholder="City" required>
                <textarea name="address" placeholder="Complete address" required></textarea>
                <input name="landmark" placeholder="Landmark">
                <textarea name="notes" placeholder="Order notes"></textarea>
            </div>
            <h2>Payment</h2>
            <label><input type="radio" name="payment_method" value="cod" checked> Cash on Delivery</label>
            <label><input type="radio" name="payment_method" value="manual"> Bank / wallet transfer</label>
            <select name="payment_account_id">
                <option value="">Select payment account</option>
                @foreach($paymentAccounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }} - {{ $account->account_title }}</option>
                @endforeach
            </select>
            <input name="transaction_reference" placeholder="Transaction/reference ID">
            <input type="file" name="payment_screenshot" accept=".jpg,.jpeg,.png,.webp,.pdf">
            <button class="btn">Place Order</button>
        </form>
        <aside class="order-summary">
            <h2>Order Summary</h2>
            @php($subtotal = 0)
            @foreach($cart->items as $item)
                @php($line = $item->quantity * $item->unit_price) @php($subtotal += $line)
                <div><span>{{ $item->product->name }} x {{ $item->quantity }}</span><strong>PKR {{ number_format($line) }}</strong></div>
            @endforeach
            @php($shippingTotal = $shipping && (!$shipping->free_threshold || $subtotal < $shipping->free_threshold) ? $shipping->charge : 0)
            <div><span>Shipping</span><strong>PKR {{ number_format($shippingTotal) }}</strong></div>
            <div class="summary"><strong>Total</strong><strong>PKR {{ number_format($subtotal + $shippingTotal) }}</strong></div>
        </aside>
    </section>
@endsection
