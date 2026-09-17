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
                <fieldset>
                    <legend>Fabric</legend>
                    <label v-for="item in options.fabrics" :key="item">
                        <input v-model="filters.fabrics" type="checkbox" :value="item">
                        <span>{{ item }}</span>
                    </label>
                </fieldset>
                <fieldset>
                    <legend>More</legend>
                    <label><input v-model="filters.available" type="checkbox"> <span>In stock only</span></label>
                    <label><input v-model="filters.discounted" type="checkbox"> <span>Discounted</span></label>
                    <label><input v-model="filters.rating" type="checkbox"> <span>Rating 4.7+</span></label>
                </fieldset>
            </aside>

            <div>
                <div v-if="filtered.length" class="product-grid" :class="`product-grid--${display}`">
                    <ProductCard v-for="product in visible" :key="product.cardKey || product.slug" :product="product" :display="display" @quick-view="quickView = $event" />
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
            <div class="modal">
                <button class="icon-button modal__close" type="button" aria-label="Close quick view" @click="quickView = null">×</button>
                <img :src="quickView.images[0]" :alt="quickView.name" width="360" height="450">
                <div>
                    <p class="eyebrow">Quick View</p>
                    <h3>{{ quickView.name }}</h3>
                    <p>{{ quickView.short_description }}</p>
                    <p class="price-row"><strong>{{ formatPrice(quickView.price) }}</strong><s v-if="quickView.original_price">{{ formatPrice(quickView.original_price) }}</s></p>
                    <div class="modal__actions">
                        <a class="button button--ghost" :href="$toUrl('/product/' + quickView.slug)">View details</a>
                        <button class="button button--gold" type="button" @click="addToCart(quickView, { color: quickView.variantColor || quickView.colors?.[0] })">Add to cart</button>
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
const display = ref('grid');
const sort = ref('newest');
const sortOpen = ref(false);
const limit = ref(8);
const headingId = `shop-${Math.random().toString(36).slice(2)}`;
const sortOptions = [
    { value: 'newest', label: 'Newest' },
    { value: 'popularity', label: 'Popularity' },
    { value: 'price-low', label: 'Price: low to high' },
    { value: 'price-high', label: 'Price: high to low' },
    { value: 'discount', label: 'Discount' },
];

const filters = reactive({
    search: props.initialSearch,
    categories: props.categorySlug ? [props.categorySlug] : [],
    subcategories: [],
    colors: [],
    sizes: [],
    fabrics: [],
    min: 0,
    max: 12000,
    available: false,
    discounted: false,
    rating: false,
});

const categories = computed(() => data.categories || []);
const source = computed(() => {
    const products = props.products.length ? props.products : data.allProducts || [];

    if (!props.expandVariants) return products;

    return products.flatMap((product) => (product.colors?.length ? product.colors : ['Default']).map((color, index) => ({
        ...product,
        cardKey: `${product.slug}-${color}`,
        variantColor: color,
        colors: [color],
        images: product.images?.length ? [product.images[index % product.images.length], ...product.images.filter((_, imageIndex) => imageIndex !== index % product.images.length)] : [],
    })));
});

const options = computed(() => ({
    subcategories: unique(source.value.map((item) => item.subcategory)),
    colors: unique(source.value.flatMap((item) => item.colors || [])),
    sizes: unique(source.value.flatMap((item) => item.sizes || [])),
    fabrics: unique(source.value.map((item) => item.fabric)),
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
            && (!filters.fabrics.length || filters.fabrics.includes(product.fabric))
            && product.price >= filters.min
            && product.price <= filters.max
            && (!filters.available || product.stock === 'In stock')
            && (!filters.discounted || product.original_price)
            && (!filters.rating || product.rating >= 4.7);
    });

    return [...list].sort((a, b) => {
        if (sort.value === 'popularity') return b.reviews - a.reviews;
        if (sort.value === 'price-low') return a.price - b.price;
        if (sort.value === 'price-high') return b.price - a.price;
        if (sort.value === 'discount') return (discountPercent(b) || 0) - (discountPercent(a) || 0);
        return b.slug.localeCompare(a.slug);
    });
});

const visible = computed(() => filtered.value.slice(0, limit.value));
const sortLabel = computed(() => sortOptions.find((option) => option.value === sort.value)?.label || 'Newest');
const chips = computed(() => [
    ...filters.categories.map((value) => ({ key: 'categories', value, label: categoryName(value) })),
    ...filters.subcategories.map((value) => ({ key: 'subcategories', value, label: value })),
    ...filters.colors.map((value) => ({ key: 'colors', value, label: value })),
    ...filters.sizes.map((value) => ({ key: 'sizes', value, label: value })),
    ...filters.fabrics.map((value) => ({ key: 'fabrics', value, label: value })),
    filters.available ? { key: 'available', value: true, label: 'In stock' } : null,
    filters.discounted ? { key: 'discounted', value: true, label: 'Discounted' } : null,
    filters.rating ? { key: 'rating', value: true, label: '4.7+ rating' } : null,
].filter(Boolean));

function unique(values) {
    return [...new Set(values.filter(Boolean))].sort();
}

function categoryName(slug) {
    return categories.value.find((category) => category.slug === slug)?.name || slug;
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
    filters.fabrics = [];
    filters.min = 0;
    filters.max = 12000;
    filters.available = false;
    filters.discounted = false;
    filters.rating = false;
}

function setSort(value) {
    sort.value = value;
    sortOpen.value = false;
}
</script>
