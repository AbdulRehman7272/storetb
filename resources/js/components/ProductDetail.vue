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
                            v-for="item in centeredImages"
                            :key="`${item.image}-${item.index}`"
                            class="cylinder-slide"
                            :class="{ active: item.index === imageIndex }"
                            type="button"
                            @click="imageIndex = item.index"
                        >
                            <img :src="item.image" :alt="`${product.name} image ${item.index + 1}`">
                        </button>
                    </div>
                    <button class="icon-button" type="button" aria-label="Next product image" @click="moveImage(1)">›</button>
                </div>
            </section>

            <div v-if="product.colors?.length" class="mobile-variant-picker" aria-label="Choose product colour">
                <div class="option-group__head">
                    <strong>Color</strong><span>{{ selectedColor }}</span>
                </div>
                <div class="mobile-variant-picker__options">
                    <button v-for="color in product.colors" :key="color" class="mobile-color-option" :class="{ active: color === selectedColor }" type="button" @click="selectedColor = color">
                        <i :style="{ '--swatch': swatch(color) }" aria-hidden="true"></i><span>{{ color }}</span>
                    </button>
                </div>
            </div>

            <section class="product-panel">
                <p class="eyebrow">{{ category?.name }} · {{ product.subcategory }}</p>
                <h1 itemprop="name">{{ product.name }}</h1>
                <div class="price-row price-row--large">
                    <strong>{{ formatPrice(price) }}</strong>
                    <s v-if="product.original_price">{{ formatPrice(product.original_price) }}</s>
                    <span v-if="discountPercent(product)" class="discount-chip">-{{ discountPercent(product) }}%</span>
                </div>
                <dl class="product-facts">
                    <div><dt>Stock</dt><dd>{{ availability }}</dd></div>
                    <div><dt>SKU</dt><dd>{{ selectedVariant?.sku || product.sku }}</dd></div>
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
                        <strong>Size</strong>
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
                </div>

                <div class="trust-strip trust-strip--detail">
                    <span>Secure checkout</span>
                    <span>Easy 14-day exchange</span>
                    <span>Fabric care support</span>
                </div>
            </section>
        </div>

        <div class="product-content-grid">
            <section class="product-description-section" aria-labelledby="product-description-title">
                <p class="eyebrow">Product Information</p>
                <h2 id="product-description-title">Description</h2>
                <div class="rich-content" itemprop="description" v-html="product.description"></div>
            </section>

            <section class="product-reviews-section" aria-labelledby="product-reviews-title">
                <p class="eyebrow">Customer Feedback</p>
                <h2 id="product-reviews-title">Reviews</h2>
                <div class="empty-state"><strong>No reviews yet</strong></div>
            </section>
        </div>

        <section class="section-block">
            <div class="section-head"><div><p class="eyebrow">Pair It Well</p><h2>Frequently Bought Together</h2></div></div>
            <div class="mini-bundle">
                <a v-for="item in recommendations" :key="item.slug" :href="toUrl('/product/' + item.slug)">
                    <img :src="item.images[0]" :alt="item.name">
                    <span>{{ item.name }}</span>
                </a>
            </div>
        </section>

        <ProductGrid :products="related" title="Related Products" eyebrow="You May Also Like" />

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

const { data, addToCart, toggleWishlist, addRecentlyViewed, createWhatsAppUrl, toUrl } = useCommerce();
const requestedSku = new URLSearchParams(window.location.search).get('sku');
const requestedVariant = props.product.variants?.find((item) => item.sku === requestedSku);
const selectedColor = ref(requestedVariant?.color || props.product.colors[0]);
const selectedSize = ref(requestedVariant?.size || props.product.sizes[0]);
const quantity = ref(1);
const imageIndex = ref(0);
const viewer = ref(false);

const category = computed(() => categoryBySlug(props.product.category));
const selectedVariant = computed(() => props.product.variants?.find((item) => item.color === selectedColor.value && (!item.size || item.size === selectedSize.value)) || null);
const images = computed(() => selectedVariant.value?.images?.length
    ? selectedVariant.value.images
    : (selectedVariant.value?.image ? [selectedVariant.value.image] : (props.product.images || [])));
const centeredImages = computed(() => {
    const total = images.value.length;
    if (!total) return [];
    const center = Math.floor(total / 2);
    const start = (imageIndex.value - center + total) % total;
    return Array.from({ length: total }, (_, offset) => {
        const index = (start + offset) % total;
        return { image: images.value[index], index };
    });
});
const selectedImage = computed(() => images.value[imageIndex.value] || images.value[0]);
const availableSizes = computed(() => [...new Set((props.product.variants || []).filter((item) => item.color === selectedColor.value).map((item) => item.size).filter(Boolean))]);
const price = computed(() => selectedVariant.value?.price ?? props.product.price);
const availability = computed(() => selectedVariant.value ? (selectedVariant.value.stock_quantity > 0 ? 'In stock' : 'Out of stock') : props.product.stock);
const recommendationPool = [
    ...props.related,
    ...(data.allProducts || []).filter((item) => item.slug !== props.product.slug && !props.related.some((related) => related.slug === item.slug)),
];
const randomOrder = new Map(recommendationPool.map((item) => [item.slug, Math.random()]));
const randomizedRelated = [...props.related].sort((a, b) => randomOrder.get(a.slug) - randomOrder.get(b.slug));
const randomizedFallback = recommendationPool
    .filter((item) => !props.related.some((related) => related.slug === item.slug))
    .sort((a, b) => randomOrder.get(a.slug) - randomOrder.get(b.slug));
const recommendations = computed(() => [
    props.product,
    ...randomizedRelated,
    ...randomizedFallback,
].slice(0, 10));
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
    imageIndex.value = 0;
});

onMounted(() => {
    imageIndex.value = 0;
    addRecentlyViewed(props.product.slug);
});

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
