<article class="product-card">
    <a class="product-image" href="{{ route('product.show', $product->slug) }}">
        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
        @if($product->sale_price)
            <span class="badge">Sale</span>
        @elseif($product->created_at?->gt(now()->subDays(14)))
            <span class="badge">New</span>
        @endif
    </a>
    <div class="product-info">
        <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
        <div class="price">
            @if($product->sale_price)
                <span>PKR {{ number_format($product->sale_price) }}</span>
                <del>PKR {{ number_format($product->regular_price) }}</del>
            @else
                <span>PKR {{ number_format($product->regular_price) }}</span>
            @endif
        </div>
        @php
            $swatches = $product->variants->flatMap->optionValues->filter(fn($value) => $value->swatch)->unique('id');
            $sizes = $product->variants->flatMap->optionValues->filter(fn($value) => !$value->swatch && strlen($value->value) <= 4)->unique('id');
        @endphp
        @if($swatches->isNotEmpty())
            <div class="swatches">
                @foreach($swatches as $value)
                    <span style="background: {{ $value->swatch }}" title="{{ $value->value }}"></span>
                @endforeach
            </div>
        @endif
        @if($sizes->isNotEmpty())
            <div class="sizes">{{ $sizes->pluck('value')->join(' / ') }}</div>
        @endif
        <form action="{{ route('cart.add', $product->slug) }}" method="post">
            @csrf
            <button class="btn-small">Add to Cart</button>
            <button class="btn-small alt" name="buy_now" value="1">Buy Now</button>
        </form>
    </div>
</article>
