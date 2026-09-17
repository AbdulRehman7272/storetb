@extends('layouts.storefront')
@section('title', 'Track Order - TBrand')
@section('content')
    <section class="section narrow">
        <h1>Track Order</h1>
        <form method="post" action="{{ route('track.show') }}" class="track-form">
            @csrf
            <input name="order_number" placeholder="Order number" value="{{ old('order_number') }}" required>
            <input name="mobile" placeholder="Mobile number" value="{{ old('mobile') }}" required>
            <button class="btn">Track</button>
        </form>
        @isset($order)
            @if($order)
                <div class="status-card"><strong>{{ $order->order_number }}</strong><p>{{ ucfirst($order->status) }} | {{ str_replace('_', ' ', $order->payment_status) }}</p></div>
            @else
                <div class="empty">No order found with those details.</div>
            @endif
        @endisset
    </section>
@endsection
