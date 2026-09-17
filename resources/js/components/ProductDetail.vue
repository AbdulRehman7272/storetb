<template>
    <article class="product-detail" itemscope itemtype="https://schema.org/Product">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a :href="$toUrl('/')">Home</a>
            <span>›</span>
            <a :href="$toUrl('/category/' + category?.slug)">{{ category?.name }}</a>
            <span>›</span>
            <span>{{ product.name }}</span>
        </nav>
        <div class="product-detail__grid">
            <section class="gallery" aria-label="Product gallery">
                <button class="gallery__main" type="button" @click="viewer = true" aria-label="Open full-screen image viewer">
                    <img :src="selectedImage" :alt="`${product.name} in ${selectedColor}`" itemprop="image">
                    <span>Zoom</span>
                </button>
                <div class="cylinder-slider" aria-label="Product image cylinder slider">
                    <button class="icon-button" type="button" aria-label="Previous product image" @click="moveImage(-1)">‹</button>
                    <div class="cylinder-slider__stage">
                        <button
                            v-for="(image, i) in cylinderImages"
                            :key="`${image}-${i}`"
                            class="cylinder-slide"
                            :class="{ active: cylinderPosition(i) === 0 }"
                            :style="slideStyle(i)"
                            type="button"
                            @click="imageIndex = i % images.length"
                        >
                            <img :src="image" :alt="`${product.name} image ${i + 1}`">
                        </button>
                    </div>
                    <button class="icon-button" type="button" aria-label="Next product image" @click="moveImage(1)">›</button>
                </div>
            </section>

            <section class="product-panel">
                <p class="eyebrow">{{ category?.name }} · {{ product.subcategory }}</p>
                <h1 itemprop="name">{{ product.name }}</h1>
                <div class="rating">
                    <span aria-hidden="true">★★★★★</span>
                    <small>{{ product.rating }} ({{ product.reviews }} reviews)</small>
                </div>
                <div class="price-row price-row--large">
                    <strong>{{ formatPrice(price) }}</strong>
                    <s v-if="product.original_price">{{ formatPrice(product.original_price) }}</s>
                    <span v-if="discountPercent(product)" class="discount-chip">-{{ discountPercent(product) }}%</span>
                </div>
                <p>{{ product.short_description }}</p>
                <dl class="product-facts">
                    <div><dt>Stock</dt><dd>{{ availability }}</dd></div>
                    <div><dt>SKU</dt><dd>{{ product.sku }}</dd></div>
                    <div><dt>Delivery</dt><dd>Estimated 2-5 working days in Pakistan</dd></div>
                </dl>

                <div class="option-group">
                    <div class="option-group__head">
                        <strong>Color</strong><span>{{ selectedColor }}</span>
                    </div>
                    <button v-for="color in product.colors" :key="color" class="swatch-button" :class="{ active: color === selectedColor }" type="button" :style="{ '--swatch': swatch(color) }" @click="selectedColor = color">
                        <span class="sr-only">{{ color }}</span>
                    </button>
                </div>

                <div class="option-group">
                    <div class="option-group__head">
                        <strong>Size</strong><a :href="$toUrl('/size-guide')">Size guide</a>
                    </div>
                    <button v-for="size in availableSizes" :key="size" class="size-button" :class="{ active: size === selectedSize }" type="button" @click="selectedSize = size">{{ size }}</button>
                </div>

                <div class="buy-box">
                    <div class="quantity">
                        <button type="button" aria-label="Decrease quantity" @click="quantity = Math.max(1, quantity - 1)">−</button>
                        <input v-model.number="quantity" type="number" min="1" aria-label="Quantity">
                        <button type="button" aria-label="Increase quantity" @click="quantity += 1">+</button>
                    </div>
                    <button class="button button--gold" type="button" @click="add">Add to cart</button>
                    <button class="button button--ghost" type="button" @click="buyNow">Buy now</button>
                    <a class="button button--whatsapp" :href="whatsappUrl" target="_blank" rel="noreferrer">Buy through WhatsApp</a>
                </div>

                <div class="inline-actions">
                    <button type="button" @click="toggleWishlist(product.slug)">♡ Wishlist</button>
                    <button type="button" @click="toggleCompare(product.slug)">⇄ Compare</button>
                </div>

                <div class="trust-strip trust-strip--detail">
                    <span>Secure checkout</span>
                    <span>Easy 14-day exchange</span>
                    <span>Fabric care support</span>
                </div>
            </section>
        </div>

        <section class="accordions">
            <details open>
                <summary>Full Description</summary>
                <p itemprop="description">{{ product.description }}</p>
            </details>
            <details>
                <summary>Fabric and Care</summary>
                <p>{{ product.fabric }} fabric. {{ product.care }}</p>
            </details>
            <details>
                <summary>Shipping and Returns</summary>
                <p>Free delivery applies above {{ formatPrice(data.store.free_shipping_threshold) }}. Exchanges are available within 14 days for eligible unused items.</p>
            </details>
        </section>

        <section class="section-block">
            <div class="section-head"><div><p class="eyebrow">Pair It Well</p><h2>Frequently Bought Together</h2></div></div>
            <div class="mini-bundle">
                <div v-for="item in bundle" :key="item.slug">
                    <img :src="item.images[0]" :alt="item.name">
                    <span>{{ item.name }}</span>
                </div>
                <strong>{{ formatPrice(bundle.reduce((sum, item) => sum + item.price, 0)) }}</strong>
                <button class="button button--gold" type="button" @click="bundle.forEach((item) => addToCart(item))">Add bundle</button>
            </div>
        </section>

        <ProductGrid :products="related" title="Related Products" eyebrow="You May Also Like" />

        <section class="reviews section-block">
            <div class="section-head"><div><p class="eyebrow">Customer Notes</p><h2>Reviews</h2></div></div>
            <div class="review-grid">
                <article><strong>Beautiful finish</strong><p>The embroidery feels premium and the sizing was accurate.</p><small>Ayesha · Lahore</small></article>
                <article><strong>Fast delivery</strong><p>Packaging was neat and the product matched the pictures.</p><small>Hina · Karachi</small></article>
                <article><strong>Worth the price</strong><p>Fabric quality is solid for the price range.</p><small>Sana · Islamabad</small></article>
            </div>
        </section>

        <div v-if="viewer" class="modal-backdrop" role="dialog" aria-modal="true" aria-label="Full-screen product image viewer">
            <div class="viewer">
                <button class="icon-button modal__close" type="button" aria-label="Close viewer" @click="viewer = false">×</button>
                <img :src="selectedImage" :alt="product.name">
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import ProductGrid from './ProductGrid.vue';
import { categoryBySlug, discountPercent, formatPrice, useCommerce } from '../composables/useCommerce';

const props = defineProps({
    product: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const { data, addToCart, toggleWishlist, toggleCompare, addRecentlyViewed, createWhatsAppUrl, toUrl } = useCommerce();
const selectedColor = ref(props.product.colors[0]);
const selectedSize = ref(props.product.sizes[0]);
const quantity = ref(1);
const imageIndex = ref(0);
const viewer = ref(false);

const category = computed(() => categoryBySlug(props.product.category));
const images = computed(() => props.product.images || []);
const cylinderImages = computed(() => images.value);
const selectedImage = computed(() => images.value[imageIndex.value] || images.value[0]);
const selectedVariant = computed(() => props.product.variants?.find((item) => item.color === selectedColor.value && (!item.size || item.size === selectedSize.value)) || null);
const availableSizes = computed(() => [...new Set((props.product.variants || []).filter((item) => item.color === selectedColor.value).map((item) => item.size).filter(Boolean))]);
const price = computed(() => selectedVariant.value?.price ?? props.product.price);
const availability = computed(() => selectedVariant.value ? (selectedVariant.value.stock_quantity > 0 ? `${selectedVariant.value.stock_quantity} in stock` : 'Out of stock') : props.product.stock);
const bundle = computed(() => [props.product, ...props.related.slice(0, 2)]);
const whatsappUrl = computed(() => createWhatsAppUrl([
    `TBrand product inquiry`,
    `Product: ${props.product.name}`,
    `Color: ${selectedColor.value}`,
    `Size: ${selectedSize.value}`,
    `Quantity: ${quantity.value}`,
    `Price: ${formatPrice(price.value)}`,
    `URL: ${window.location.href}`,
].join('\n')));

watch(selectedColor, () => {
    selectedSize.value = availableSizes.value[0] || 'One Size';
    const variantImage = selectedVariant.value?.image;
    if (variantImage) imageIndex.value = Math.max(0, images.value.indexOf(variantImage));
});

onMounted(() => addRecentlyViewed(props.product.slug));

function add() {
    if (selectedVariant.value && selectedVariant.value.stock_quantity < quantity.value) return;
    addToCart(props.product, { color: selectedColor.value, size: selectedSize.value, quantity: quantity.value, variant: selectedVariant.value });
}

function buyNow() {
    add();
    window.location.href = toUrl('/checkout');
}

function moveImage(direction) {
    const total = images.value.length || 1;
    imageIndex.value = (imageIndex.value + direction + total) % total;
}

function cylinderPosition(index) {
    const total = images.value.length || 1;
    const normalized = index % total;
    let distance = normalized - imageIndex.value;

    if (distance > total / 2) distance -= total;
    if (distance < -total / 2) distance += total;

    return distance;
}

function slideStyle(index) {
    const position = cylinderPosition(index);
    const hidden = Math.abs(position) > 2;

    return {
        transform: `translateX(${position * 74}px) translateZ(${120 - Math.abs(position) * 34}px) rotateY(${-position * 24}deg) scale(${1 - Math.abs(position) * 0.08})`,
        opacity: hidden ? 0 : 1 - Math.abs(position) * 0.22,
        zIndex: 10 - Math.abs(position),
        pointerEvents: hidden ? 'none' : 'auto',
    };
}

function swatch(color) {
    return {
        Black: '#050505',
        Gold: '#d7ad3f',
        Ivory: '#f4efe1',
        Maroon: '#6c1f2f',
        Emerald: '#0d5d45',
        Beige: '#c7aa78',
        Navy: '#111d35',
        White: '#f7f7f7',
    }[color] || props.product.colorSwatches?.[color] || '#777';
}
</script>
