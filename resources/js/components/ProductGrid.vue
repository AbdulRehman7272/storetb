<template>
    <section class="shop-layout" :aria-labelledby="headingId">
        <div class="section-head section-head--shop">
            <div>
                <p class="eyebrow">{{ eyebrow }}</p>
                <component :is="asPage ? 'h1' : 'h2'" :id="headingId">{{ title }}</component>
                <p class="muted">{{ filtered.length }} products found</p>
            </div>
            <div class="shop-tools">
                <label class="search-field">
                    <span class="sr-only">Search products</span>
                    <input v-model="filters.search" type="search" placeholder="Search products">
                </label>
                <div class="sort-menu" :class="{ open: sortOpen }">
                    <button type="button" aria-haspopup="listbox" :aria-expanded="sortOpen" @click="sortOpen = !sortOpen">
                        <span>{{ sortLabel }}</span>
                        <span aria-hidden="true">⌄</span>
                    </button>
                    <div v-if="sortOpen" class="sort-menu__list" role="listbox" aria-label="Sort products">
                        <button
                            v-for="option in sortOptions"
                            :key="option.value"
                            type="button"
                            role="option"
                            :aria-selected="sort === option.value"
                            :class="{ active: sort === option.value }"
                            @click="setSort(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
                <div class="segmented" aria-label="Display mode">
                    <button type="button" :class="{ active: display === 'grid' }" @click="display = 'grid'">Grid</button>
                    <button type="button" :class="{ active: display === 'list' }" @click="display = 'list'">List</button>
                </div>
                <button class="button button--ghost filter-toggle" type="button" @click="drawer = true">Filters</button>
            </div>
        </div>

        <div class="active-filters" v-if="chips.length">
            <button v-for="chip in chips" :key="chip.key" type="button" @click="removeChip(chip)">{{ chip.label }} ×</button>
            <button type="button" @click="clearFilters">Clear all</button>
        </div>

        <div class="shop-layout__grid">
            <aside class="filter-panel" :class="{ open: drawer }" aria-label="Product filters">
                <div class="filter-panel__head">
                    <strong>Filters</strong>
                    <button class="icon-button" type="button" aria-label="Close filters" @click="drawer = false">×</button>
                </div>
                <fieldset>
                    <legend>Category</legend>
                    <label v-for="category in categories" :key="category.slug">
                        <input v-model="filters.categories" type="checkbox" :value="category.slug">
                        <span>{{ category.name }}</span>
                    </label>
                </fieldset>
                <fieldset>
                    <legend>Subcategory</legend>
                    <label v-for="item in options.subcategories" :key="item">
                        <input v-model="filters.subcategories" type="checkbox" :value="item">
                        <span>{{ item }}</span>
                    </label>
                </fieldset>
                <fieldset>
                    <legend>Price range</legend>
                    <div class="range-row">
                        <input v-model.number="filters.min" type="number" min="0" step="500" aria-label="Minimum price">
                        <input v-model.number="filters.max" type="number" min="0" step="500" aria-label="Maximum price">
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Color</legend>
                    <label v-for="item in options.colors" :key="item">
                        <input v-model="filters.colors" type="checkbox" :value="item">
                        <span>{{ item }}</span>
                    </label>
                </fieldset>
                <fieldset>
                    <legend>Size</legend>
                    <label v-for="item in options.sizes" :key="item">
                        <input v-model="filters.sizes" type="checkbox" :value="item">
                        <span>{{ item }}</span>
                    </label>
                </fieldset>
                <fieldset v-if="options.collections.length">
                    <legend>Collection</legend>
                    <label v-for="item in options.collections" :key="item.slug">
                        <input v-model="filters.collections" type="checkbox" :value="item.slug">
                        <span>{{ item.name }}</span>
                    </label>
                </fieldset>
                <fieldset>
                    <legend>More</legend>
                    <label><input v-model="filters.available" type="checkbox"> <span>In stock only</span></label>
                    <label><input v-model="filters.discounted" type="checkbox"> <span>Discounted</span></label>
                </fieldset>
            </aside>

            <div>
                <div v-if="filtered.length" class="product-grid" :class="`product-grid--${display}`">
                    <ProductCard v-for="product in visible" :key="product.cardKey || product.slug" :product="product" :display="display" @quick-view="openQuickView" />
                </div>
                <div v-else class="empty-state">
                    <strong>No matching products</strong>
                    <p>Try removing a filter or searching a broader product name.</p>
                    <button class="button button--gold" type="button" @click="clearFilters">Clear filters</button>
                </div>
                <div v-if="visible.length < filtered.length" class="load-more">
                    <button class="button button--ghost" type="button" @click="limit += 8">Load more</button>
                </div>
            </div>
        </div>

        <div v-if="quickView" class="modal-backdrop" role="dialog" aria-modal="true" aria-label="Quick view">
            <div class="modal quick-view-modal">
                <button class="icon-button modal__close" type="button" aria-label="Close quick view" @click="quickView = null">×</button>
                <div class="quick-view-gallery">
                    <img class="quick-view-gallery__main" :src="quickImage || quickImages[0]" :alt="quickView.name">
                </div>
                <div class="quick-view-info">
                    <p class="eyebrow">Quick View</p>
                    <h3>{{ quickView.name }}</h3>
                    <p>{{ quickView.short_description }}</p>
                    <p class="price-row"><strong>{{ formatPrice(quickVariant?.price ?? quickView.price) }}</strong><s v-if="quickVariant?.original_price ?? quickView.original_price">{{ formatPrice(quickVariant?.original_price ?? quickView.original_price) }}</s></p>
                    <div class="quick-view-colors" v-if="quickView.colors?.length">
                        <strong>Colour</strong>
                        <button v-for="color in quickView.colors" :key="color" type="button" :class="{ selected: quickColor === color }" @click="selectQuickColor(color)">
                            <span :style="{ '--swatch': quickView.colorSwatches?.[color] || '#777' }"></span>{{ color }}
                        </button>
                    </div>
                    <div class="quick-view-thumbs" aria-label="Variant images">
                        <button v-for="image in quickImages" :key="image" type="button" :class="{ selected: quickImage === image }" @click="quickImage = image"><img :src="image" alt=""></button>
                    </div>
                    <div class="modal__actions">
                        <a class="button button--ghost" :href="$toUrl('/product/' + quickView.slug)">View details</a>
                        <button class="button button--gold" type="button" @click="addQuickToCart">Add to cart</button>
                        <button class="button button--ghost" type="button" @click="buyQuickNow">Buy now</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import ProductCard from './ProductCard.vue';
import { discountPercent, formatPrice, useCommerce } from '../composables/useCommerce';

const props = defineProps({
    products: { type: Array, default: () => [] },
    title: { type: String, default: 'Shop Products' },
    eyebrow: { type: String, default: 'Curated Shopping' },
    initialSearch: { type: String, default: '' },
    categorySlug: { type: String, default: '' },
    asPage: { type: Boolean, default: false },
    expandVariants: { type: Boolean, default: false },
});

const { data, addToCart } = useCommerce();
const drawer = ref(false);
const quickView = ref(null);
const quickColor = ref('');
const quickImage = ref('');
const display = ref('grid');
const sort = ref('random');
const sortOpen = ref(false);
const limit = ref(8);
const headingId = `shop-${Math.random().toString(36).slice(2)}`;
const sortOptions = [
    { value: 'random', label: 'Shuffled' },
    { value: 'newest', label: 'Newest' },
    { value: 'popularity', label: 'Popularity' },
    { value: 'price-low', label: 'Price: low to high' },
    { value: 'price-high', label: 'Price: high to low' },
    { value: 'discount', label: 'Discount' },
];
const shuffleRanks = new Map();
function shuffleRank(product) {
    const key = product.cardKey || product.variantSku || product.slug;
    if (!shuffleRanks.has(key)) shuffleRanks.set(key, Math.random());
    return shuffleRanks.get(key);
}
const quickVariant = computed(() => quickView.value?.variants?.find((variant) => variant.color === quickColor.value) || null);
const quickImages = computed(() => quickVariant.value?.images?.length ? quickVariant.value.images : (quickView.value?.images || []));

const filters = reactive({
    search: props.initialSearch,
    categories: props.categorySlug ? [props.categorySlug] : [],
    subcategories: [],
    colors: [],
    sizes: [],
    collections: [],
    min: 0,
    max: 12000,
    available: false,
    discounted: false,
});

const categories = computed(() => data.categories || []);
const source = computed(() => {
    const products = props.products.length ? props.products : data.allProducts || [];

    if (!props.expandVariants) return products;

    return products.flatMap((product) => (product.colors?.length ? product.colors : ['Default']).map((color, index) => {
        const variant = product.variants?.find((item) => item.color === color);
        return {
            ...product,
            cardKey: `${product.slug}-${color}`,
            variantColor: color,
            variantSku: variant?.sku,
            colors: product.colors,
            images: variant?.images?.length ? variant.images : (product.images?.length ? [variant?.image || product.images[index % product.images.length], ...product.images.filter((image) => image !== (variant?.image || product.images[index % product.images.length]))] : []),
        };
    }));
});

const options = computed(() => ({
    subcategories: unique(source.value.map((item) => item.subcategory)),
    colors: unique(source.value.flatMap((item) => item.colors || [])),
    sizes: unique(source.value.flatMap((item) => item.sizes || [])),
    collections: (data.collections || []).filter((collection) => source.value.some((product) => product.collections?.includes(collection.slug))),
}));

const filtered = computed(() => {
    const query = filters.search.trim().toLowerCase();
    const list = source.value.filter((product) => {
        const searchable = `${product.name} ${product.subcategory} ${product.fabric}`.toLowerCase();
        return (!query || searchable.includes(query))
            && (!filters.categories.length || filters.categories.includes(product.category))
            && (!filters.subcategories.length || filters.subcategories.includes(product.subcategory))
            && (!filters.colors.length || product.colors.some((item) => filters.colors.includes(item)))
            && (!filters.sizes.length || product.sizes.some((item) => filters.sizes.includes(item)))
            && (!filters.collections.length || product.collections?.some((item) => filters.collections.includes(item)))
            && product.price >= filters.min
            && product.price <= filters.max
            && (!filters.available || product.stock === 'In stock')
            && (!filters.discounted || product.original_price);
    });

    return [...list].sort((a, b) => {
        if (sort.value === 'random') return shuffleRank(a) - shuffleRank(b);
        if (sort.value === 'popularity') return b.reviews - a.reviews;
        if (sort.value === 'price-low') return a.price - b.price;
        if (sort.value === 'price-high') return b.price - a.price;
        if (sort.value === 'discount') return (discountPercent(b) || 0) - (discountPercent(a) || 0);
        return b.slug.localeCompare(a.slug);
    });
});

const visible = computed(() => filtered.value.slice(0, limit.value));
const sortLabel = computed(() => sortOptions.find((option) => option.value === sort.value)?.label || 'Shuffled');
const chips = computed(() => [
    ...filters.categories.map((value) => ({ key: 'categories', value, label: categoryName(value) })),
    ...filters.subcategories.map((value) => ({ key: 'subcategories', value, label: value })),
    ...filters.colors.map((value) => ({ key: 'colors', value, label: value })),
    ...filters.sizes.map((value) => ({ key: 'sizes', value, label: value })),
    ...filters.collections.map((value) => ({ key: 'collections', value, label: collectionName(value) })),
    filters.available ? { key: 'available', value: true, label: 'In stock' } : null,
    filters.discounted ? { key: 'discounted', value: true, label: 'Discounted' } : null,
].filter(Boolean));

function unique(values) {
    return [...new Set(values.filter(Boolean))].sort();
}

function categoryName(slug) {
    return categories.value.find((category) => category.slug === slug)?.name || slug;
}

function collectionName(slug) {
    return data.collections?.find((collection) => collection.slug === slug)?.name || slug;
}

function removeChip(chip) {
    if (Array.isArray(filters[chip.key])) {
        filters[chip.key] = filters[chip.key].filter((value) => value !== chip.value);
    } else {
        filters[chip.key] = false;
    }
}

function clearFilters() {
    filters.search = '';
    filters.categories = props.categorySlug ? [props.categorySlug] : [];
    filters.subcategories = [];
    filters.colors = [];
    filters.sizes = [];
    filters.collections = [];
    filters.min = 0;
    filters.max = 12000;
    filters.available = false;
    filters.discounted = false;
}

function setSort(value) {
    sort.value = value;
    sortOpen.value = false;
}

function openQuickView(product) {
    quickView.value = product;
    quickColor.value = product.variantColor || product.colors?.[0] || '';
    quickImage.value = (product.variants?.find((variant) => variant.color === quickColor.value)?.images || product.images || [])[0] || '';
}

function selectQuickColor(color) {
    quickColor.value = color;
    quickImage.value = quickImages.value[0] || '';
}

function addQuickToCart() {
    addToCart(quickView.value, { color: quickColor.value, variant: quickVariant.value });
}

function buyQuickNow() {
    addQuickToCart();
    window.location.href = `${data.baseUrl || ''}/checkout`;
}
</script>
