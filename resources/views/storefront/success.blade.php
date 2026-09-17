@extends('layouts.storefront')
@section('title', 'Order placed - TBrand')
@section('content')
    <section class="section narrow success">
        <h1>Order placed</h1>
        <p>Your order number is <strong>{{ $order->order_number }}</strong>.</p>
        <p>Status: {{ ucfirst($order->status) }} | Payment: {{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</p>
        <a class="btn" href="{{ route('track.index') }}">Track Order</a>
    </section>
@endsection
