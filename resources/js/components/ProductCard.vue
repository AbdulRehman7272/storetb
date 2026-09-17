<template>
    <article class="product-card" :class="{ 'product-card--list': display === 'list' }" @mouseenter="hovered = true" @mouseleave="hovered = false">
        <a class="product-card__media" :href="$toUrl('/product/' + product.slug)" @click="addRecentlyViewed(product.slug)">
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
                <button type="button" class="icon-button" aria-label="Previous product image" @click.prevent="move(-1)">‹</button>
                <div class="dots" aria-hidden="true">
                    <span v-for="(_, dot) in product.images" :key="dot" :class="{ active: dot === index }"></span>
                </div>
                <button type="button" class="icon-button" aria-label="Next product image" @click.prevent="move(1)">›</button>
            </div>
        </a>
        <div class="product-card__body">
            <div class="product-card__actions">
                <button class="icon-button" type="button" :aria-label="wishlistLabel" :class="{ selected: state.wishlist.includes(product.slug) }" @click="toggleWishlist(product.slug)">♡</button>
                <button class="icon-button" type="button" aria-label="Quick view product" @click="$emit('quick-view', product)">◎</button>
                <button class="icon-button" type="button" :aria-label="compareLabel" :class="{ selected: state.compare.includes(product.slug) }" @click="toggleCompare(product.slug)">⇄</button>
            </div>
            <a class="product-card__title" :href="$toUrl('/product/' + product.slug)">{{ displayName }}</a>
            <p class="muted">{{ product.subcategory }} · {{ product.fabric }}</p>
            <div class="price-row">
                <strong>{{ formatPrice(product.price) }}</strong>
                <s v-if="product.original_price">{{ formatPrice(product.original_price) }}</s>
            </div>
            <div class="rating" :aria-label="`${product.rating} rating from ${product.reviews} reviews`">
                <span aria-hidden="true">★★★★★</span>
                <small>{{ product.rating }} ({{ product.reviews }})</small>
            </div>
            <div class="swatches" aria-label="Available colors">
                <span v-for="color in visibleColors" :key="color" :title="color" :style="{ '--swatch': swatch(color) }"></span>
            </div>
            <button class="button button--gold button--full product-card__cart" type="button" @click="addVariantToCart">Add to cart</button>
        </div>
    </article>
</template>

<script setup>
import { computed, ref, watchEffect } from 'vue';
import { useCommerce } from '../composables/useCommerce';

const props = defineProps({
    product: { type: Object, required: true },
    display: { type: String, default: 'grid' },
});

defineEmits(['quick-view']);

const { state, addToCart, toggleWishlist, toggleCompare, addRecentlyViewed, formatPrice, discountPercent } = useCommerce();
const index = ref(0);
const hovered = ref(false);
let timer = null;

watchEffect((onCleanup) => {
    clearInterval(timer);
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (hovered.value && !reduced && props.product.images?.length > 1) {
        timer = window.setInterval(() => move(1), 1800);
    }
    onCleanup(() => clearInterval(timer));
});

const activeImage = computed(() => props.product.images?.[index.value] || props.product.images?.[0]);
const visibleColors = computed(() => props.product.variantColor ? [props.product.variantColor] : props.product.colors || []);
const displayName = computed(() => props.product.variantColor ? `${props.product.name} - ${props.product.variantColor}` : props.product.name);
const wishlistLabel = computed(() => state.wishlist.includes(props.product.slug) ? 'Remove from wishlist' : 'Add to wishlist');
const compareLabel = computed(() => state.compare.includes(props.product.slug) ? 'Remove from compare' : 'Compare product');

function move(direction) {
    const total = props.product.images?.length || 1;
    index.value = (index.value + direction + total) % total;
}

function addVariantToCart() {
    addToCart(props.product, { color: props.product.variantColor || props.product.colors?.[0] });
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
        Brown: '#5a3621',
    }[color] || '#777';
}
</script>
