@extends('layouts.storefront')
@section('title', $title ?? $category?->seo_title ?? 'Products')
@section('description', $category?->seo_description ?? 'Shop TBrand products')
@section('content')
    <section class="page-title">
        <p class="eyebrow">{{ $category ? 'Collection' : 'Search' }}</p>
        <h1>{{ $title }}</h1>
        @if($category?->description)<p>{{ $category->description }}</p>@endif
    </section>
    <section class="section">
        <div class="product-grid">
            @forelse($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @empty
                <div class="empty">No products found.</div>
            @endforelse
        </div>
        {{ $products->links() }}
    </section>
@endsection
