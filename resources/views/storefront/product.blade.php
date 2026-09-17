@extends('layouts.storefront')
@section('title', $product->seo_title ?: $product->name)
@section('description', $product->seo_description ?: str($product->description)->limit(150))
@section('content')
    <section class="product-page">
        <div class="gallery">
            @forelse($product->media as $media)
                <img src="{{ asset($media->path) }}" alt="{{ $media->alt_text ?: $product->name }}">
            @empty
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
            @endforelse
        </div>
        <div class="purchase-panel">
            <p class="eyebrow">{{ $product->category?->name }}</p>
            <h1>{{ $product->name }}</h1>
            <div class="price big" id="variant-price">PKR {{ number_format($product->price()) }}</div>
            <p>{{ $product->description }}</p>
            <form action="{{ route('cart.add', $product->slug) }}" method="post">
                @csrf
                @if($product->variants->isNotEmpty())
                    <label>Variant</label>
                    <select name="variant_id" id="variant-select">
                        @foreach($product->variants as $variant)
                            <option value="{{ $variant->id }}" data-price="{{ number_format($variant->price()) }}" data-stock="{{ $variant->stock }}" data-sku="{{ $variant->sku }}">{{ $variant->name }} - PKR {{ number_format($variant->price()) }}</option>
                        @endforeach
                    </select>
                @endif
                <label>Quantity</label>
                <input type="number" name="quantity" value="1" min="1">
                <div class="stock" id="variant-stock">Stock: {{ $product->variants->first()?->stock ?? $product->stock }}</div>
                <button class="btn">Add to Cart</button>
                <button class="btn secondary" name="buy_now" value="1">Buy Now</button>
            </form>
            <a class="whatsapp" href="https://wa.me/92{{ ltrim(\App\Support\StoreSettings::get('whatsapp', '03076690892'), '0') }}?text={{ urlencode('I want to order '.$product->name) }}">Order on WhatsApp</a>
        </div>
    </section>
    <section class="section">
        <div class="section-head"><h2>Related Products</h2></div>
        <div class="product-grid">
            @foreach($related as $item)
                @include('storefront.partials.product-card', ['product' => $item])
            @endforeach
        </div>
    </section>
@endsection
