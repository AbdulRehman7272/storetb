<template>
    <article class="product-card" :class="{ 'product-card--list': display === 'list' }">
        <a class="product-card__media" :href="productUrl" @click="addRecentlyViewed(product.slug)">
            <span v-if="product.badge" class="badge">{{ product.badge }}</span>
            <span v-if="discountPercent(product)" class="badge badge--discount">-{{ discountPercent(product) }}%</span>
            <img
                :src="activeImage"
                :alt="product.name"
                width="450"
                height="563"
                loading="eager"
                decoding="async"
            >
            <div v-if="product.images?.length > 1" class="card-carousel" aria-label="Product image carousel">
                <button type="button" class="icon-button" aria-label="Previous product image" @click.prevent="move(-1)"><i data-lucide="arrow-left" aria-hidden="true"></i></button>
                <div class="dots" aria-hidden="true">
                    <span v-for="(_, dot) in product.images" :key="dot" :class="{ active: dot === index }"></span>
                </div>
                <button type="button" class="icon-button" aria-label="Next product image" @click.prevent="move(1)"><i data-lucide="arrow-right" aria-hidden="true"></i></button>
            </div>
        </a>
        <div class="product-card__body">
            <div class="product-card__actions">
                <button class="icon-button" type="button" :aria-label="wishlistLabel" :title="wishlistLabel" :class="{ selected: state.wishlist.includes(savedKey) }" @click="toggleWishlist(savedKey)">♡</button>
                <button class="button button--ghost product-card__view" type="button" title="Open quick view" @click="$emit('quick-view', product)">View</button>
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
});

defineEmits(['quick-view']);

const { state, addToCart, toggleWishlist, addRecentlyViewed, formatPrice, discountPercent, toUrl } = useCommerce();
const index = ref(0);

const activeImage = computed(() => props.product.images?.[index.value] || props.product.images?.[0]);
const visibleColors = computed(() => props.product.colors || []);
const displayName = computed(() => props.product.variantColor ? `${props.product.name} - ${props.product.variantColor}` : props.product.name);
const productUrl = computed(() => `${toUrl('/product/' + props.product.slug)}${props.product.variantSku ? `?sku=${encodeURIComponent(props.product.variantSku)}` : ''}`);
const savedKey = computed(() => props.product.variantSku ? `${props.product.slug}::${props.product.variantSku}` : props.product.slug);
const wishlistLabel = computed(() => state.wishlist.includes(savedKey.value) ? 'Remove from wishlist' : 'Add to wishlist');

function move(direction) {
    const total = props.product.images?.length || 1;
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
