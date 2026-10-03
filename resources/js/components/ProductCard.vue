<template>
    <article class="product-card" :class="{ 'product-card--list': display === 'list' }">
        <a class="product-card__media" :href="productUrl" @click="addRecentlyViewed(product.slug)">
            <span v-if="discountPercent(product)" class="badge badge--discount">-{{ discountPercent(product) }}%</span>
            <img
                :src="activeImage"
                :alt="product.name"
                width="450"
                height="563"
                :loading="priority ? 'eager' : 'lazy'"
                :fetchpriority="priority ? 'high' : 'auto'"
                decoding="async"
            >
            <div v-if="cardImages.length > 1" class="card-carousel" aria-label="Product variant carousel">
                <button type="button" class="icon-button" aria-label="Previous product image" @click.prevent="move(-1)"><span aria-hidden="true">←</span></button>
                <div class="dots" aria-hidden="true">
                    <span v-for="(_, dot) in cardImages" :key="dot" :class="{ active: dot === index }"></span>
                </div>
                <button type="button" class="icon-button" aria-label="Next product image" @click.prevent="move(1)"><span aria-hidden="true">→</span></button>
            </div>
        </a>
        <div class="product-card__body">
            <div class="product-card__actions">
                <button class="icon-button" type="button" :aria-label="wishlistLabel" :title="wishlistLabel" :class="{ selected: state.wishlist.includes(savedKey) }" @click="toggleWishlist(savedKey)">♡</button>
                <button class="button button--ghost product-card__view" type="button" :title="`${imageCount} ${imageCount === 1 ? 'image' : 'images'}`" :aria-label="`Open ${imageCount} product ${imageCount === 1 ? 'image' : 'images'}`" @click="$emit('quick-view', product)">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.06 12.35a1 1 0 0 1 0-.7C3.73 7.6 7.6 5 12 5c4.4 0 8.27 2.6 9.94 6.65a1 1 0 0 1 0 .7C20.27 16.4 16.4 19 12 19c-4.4 0-8.27-2.6-9.94-6.65Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>{{ imageCount }}</span>
                </button>
            </div>
            <a class="product-card__title" :href="productUrl">{{ displayName }}</a>
            <div class="price-row">
                <strong>{{ formatPrice(product.price) }}</strong>
                <s v-if="product.original_price">{{ formatPrice(product.original_price) }}</s>
            </div>
            <div class="swatches" aria-label="Available colors">
                <span v-for="color in visibleColors" :key="color" :title="color" :class="{ selected: color === product.variantColor }" :style="{ '--swatch': swatch(color) }"></span>
            </div>
            <div class="product-card__purchase">
                <button class="button button--gold" type="button" @click="addVariantToCart">Add to cart</button>
                <button class="button button--ghost" type="button" @click="buyNow">Buy now</button>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useCommerce } from '../composables/useCommerce';

const props = defineProps({
    product: { type: Object, required: true },
    display: { type: String, default: 'grid' },
    priority: { type: Boolean, default: false },
});

defineEmits(['quick-view']);

const { state, addToCart, toggleWishlist, addRecentlyViewed, formatPrice, discountPercent, toUrl } = useCommerce();
const index = ref(0);

const cardImages = computed(() => {
    if (props.product.variantColor) {
        const selectedVariant = (props.product.variants || []).find((variant) => variant.color === props.product.variantColor);
        if (selectedVariant?.images?.length) return selectedVariant.images;
        if (selectedVariant?.image) return [selectedVariant.image];
        return props.product.images || [];
    }

    const seen = new Set();
    const variantImages = (props.product.variants || []).flatMap((variant) => {
        const key = variant.color || variant.id;
        if (seen.has(key)) return [];
        seen.add(key);
        const image = variant.images?.[0] || variant.image;
        return image ? [image] : [];
    });

    return variantImages.length ? variantImages : (props.product.images || []);
});
const activeImage = computed(() => cardImages.value[index.value] || cardImages.value[0]);
const visibleColors = computed(() => props.product.colors || []);
const displayName = computed(() => props.product.variantColor ? `${props.product.name} - ${props.product.variantColor}` : props.product.name);
const productUrl = computed(() => `${toUrl('/product/' + props.product.slug)}${props.product.variantSku ? `?sku=${encodeURIComponent(props.product.variantSku)}` : ''}`);
const savedKey = computed(() => props.product.variantSku ? `${props.product.slug}::${props.product.variantSku}` : props.product.slug);
const wishlistLabel = computed(() => state.wishlist.includes(savedKey.value) ? 'Remove from wishlist' : 'Add to wishlist');
const imageCount = computed(() => {
    if (props.product.variantColor) {
        return (props.product.variants || []).find((variant) => variant.color === props.product.variantColor)?.image_count || props.product.image_count || 1;
    }
    return props.product.image_count || 1;
});

function move(direction) {
    const total = cardImages.value.length || 1;
    index.value = (index.value + direction + total) % total;
}

function addVariantToCart() {
    addToCart(props.product, { color: props.product.variantColor || props.product.colors?.[0] });
}

function buyNow() {
    addVariantToCart();
    window.location.href = toUrl('/checkout');
}

function swatch(color) {
    if (props.product.colorSwatches?.[color]) return props.product.colorSwatches[color];

    return {
        Black: '#050505',
        Gold: '#d7ad3f',
        Ivory: '#f4efe1',
        Maroon: '#6c1f2f',
        Emerald: '#0d5d45',
        Beige: '#c7aa78',
        Navy: '#111d35',
        White: '#f7f7f7',
        Brown: '#5a3621',
    }[color] || '#777';
}
</script>
