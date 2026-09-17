@extends('layouts.storefront')
@section('title', ($settings['store_name'] ?? 'TBrand').' - Premium Fashion Store')
@section('content')
    <section class="hero">
        <div>
            <p class="eyebrow">New season edit</p>
            <h1>TBrand</h1>
            <p>Premium everyday fashion, simple checkout, and quick WhatsApp support.</p>
            <a class="btn" href="{{ route('search') }}">Shop New Arrivals</a>
        </div>
        <img src="{{ asset('brand/tbrand/05_Gold_Black.png') }}" alt="TBrand">
    </section>

    <section class="section">
        <div class="section-head"><h2>Featured Categories</h2></div>
        <div class="category-grid">
            @foreach($categories as $category)
                <a href="{{ route('category.show', $category->slug) }}">{{ $category->name }}</a>
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="section-head"><h2>Featured Products</h2><a href="{{ route('search') }}">View all</a></div>
        <div class="product-grid">
            @foreach($featured as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>

    <section class="section muted">
        <div class="section-head"><h2>New Arrivals</h2></div>
        <div class="product-grid">
            @foreach($newArrivals as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
@endsection
