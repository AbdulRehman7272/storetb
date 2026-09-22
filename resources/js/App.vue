<template>
    <div class="site-shell">
        <header class="site-header">
            <a class="logo" :href="$toUrl('/')" aria-label="TBrand home"><img :src="data.store.logo" alt="TBrand"></a>
            <nav class="desktop-nav" aria-label="Main navigation">
                <a v-if="data.store.show_super_store" :href="$toUrl('/super-store')">Super Store</a>
                <div class="mega">
                    <button type="button">SHOP</button>
                    <div class="mega__panel">
                        <div v-for="group in categoryGroups" :key="group.name">
                            <strong>{{ group.name }}</strong>
                            <a v-for="category in group.items" :key="category.slug" :href="$toUrl('/category/' + category.slug)">{{ category.name }}</a>
                        </div>
                    </div>
                </div>
                <a :href="$toUrl('/about')">About</a>
                <a :href="$toUrl('/contact')">Contact</a>
            </nav>
            <div class="header-actions">
                <button class="icon-button" type="button" aria-label="Search" @click="searchOpen = true">⌕</button>
                <a class="icon-button" :href="$toUrl('/wishlist')" aria-label="Wishlist">♡<span v-if="state.wishlist.length">{{ state.wishlist.length }}</span></a>
                <button class="icon-button" type="button" aria-label="Account placeholder" @click="toast('Account login will arrive in a later backend phase.', 'info')">♙</button>
                <button class="icon-button" type="button" aria-label="Open cart" @click="cartOpen = true">▣<span v-if="cartCount">{{ cartCount }}</span></button>
                <button class="icon-button mobile-only" type="button" aria-label="Open menu" @click="menuOpen = true">☰</button>
            </div>
        </header>

        <div v-if="menuOpen" class="mobile-menu" role="dialog" aria-modal="true" aria-label="Mobile menu">
            <button class="icon-button modal__close" type="button" aria-label="Close menu" @click="menuOpen = false">×</button>
            <a v-if="data.store.show_super_store" :href="$toUrl('/super-store')">Super Store</a>
            <a :href="$toUrl('/categories')">All Categories</a>
            <a :href="$toUrl('/shop')">SHOP</a>
            <a v-for="category in data.categories" :key="category.slug" :href="$toUrl('/category/' + category.slug)">{{ category.name }}</a>
            <a :href="$toUrl('/about')">About</a>
            <a :href="$toUrl('/contact')">Contact</a>
        </div>

        <main>
            <HomePage v-if="page === 'home'" />
            <CategoriesPage v-else-if="page === 'categories'" />
            <CategoryPage v-else-if="page === 'category'" :category="context.category" :products="context.products" />
            <ProductGrid v-else-if="page === 'shop'" :products="data.allProducts" title="Shop All Products" eyebrow="TBrand Store" as-page />
            <CollectionPage v-else-if="page === 'collection'" :collection="context.collection" :products="context.products" />
            <ProductGrid v-else-if="page === 'search'" :products="data.allProducts" title="Search Results" eyebrow="Find Your Piece" :initial-search="context.query || ''" as-page />
            <ProductDetail v-else-if="page === 'product'" :product="context.product" :related="context.related" />
            <CartPage v-else-if="page === 'cart'" />
            <CheckoutPage v-else-if="page === 'checkout'" />
            <UtilityPage v-else-if="utilityPages.includes(page)" :page="page" :context="context" />
            <NotFound v-else />
        </main>

        <footer class="site-footer">
            <div>
                <img :src="data.store.logo" alt="TBrand">
                <p>Premium shopping shaped around style, quality, and trust.</p>
                <div v-if="data.store.show_footer_contact" class="footer-contact">
                    <a v-if="data.store.general_email" :href="'mailto:' + data.store.general_email">{{ data.store.general_email }}</a>
                    <a v-if="data.store.support_email" :href="'mailto:' + data.store.support_email">{{ data.store.support_email }}</a>
                    <a v-if="data.store.whatsapp" :href="'https://wa.me/' + data.store.whatsapp" target="_blank" rel="noreferrer">WhatsApp {{ data.store.whatsapp }}</a>
                    <span v-if="data.store.address">{{ data.store.address }}</span>
                </div>
            </div>
            <div>
                <strong>Shop</strong>
                <a :href="$toUrl('/categories')">All Categories</a>
                <a :href="$toUrl('/shop')">Products</a>
                <a :href="$toUrl('/wishlist')">Wishlist</a>
            </div>
            <div>
                <strong>Support</strong>
                <a :href="$toUrl('/faq')">FAQ</a>
                <a :href="$toUrl('/track-order')">Track Order</a>
                <a :href="$toUrl('/size-guide')">Size Guide</a>
                <a :href="$toUrl('/policies/shipping-policy')">Shipping Policy</a>
            </div>
            <div>
                <strong>Policies</strong>
                <a :href="$toUrl('/policies/return-exchange-policy')">Returns & Exchange</a>
                <a :href="$toUrl('/policies/privacy-policy')">Privacy Policy</a>
                <a :href="$toUrl('/policies/terms-conditions')">Terms & Conditions</a>
            </div>
        </footer>

        <CartDrawer :open="cartOpen" @close="cartOpen = false" />
        <SearchOverlay :open="searchOpen" @close="searchOpen = false" />
        <ToastStack />
    </div>
</template>

<script setup>
import { computed, defineComponent, ref } from 'vue';
import CategoryCard from './components/CategoryCard.vue';
import ProductCard from './components/ProductCard.vue';
import ProductGrid from './components/ProductGrid.vue';
import ProductDetail from './components/ProductDetail.vue';
import CartPage from './components/CartPage.vue';
import CheckoutPage from './components/CheckoutPage.vue';
import OrderSummary from './components/OrderSummary.vue';
import UtilityPage from './components/UtilityPage.vue';
import { formatPrice, toUrl, useCommerce } from './composables/useCommerce';

const { data, state, cartCount, cartItems, addToCart, removeFromCart, updateQuantity, toast, subtotal, total, createWhatsAppUrl } = useCommerce();
const page = data.page;
const context = data.pageContext || {};
const cartOpen = ref(false);
const searchOpen = ref(false);
const menuOpen = ref(false);
const utilityPages = ['wishlist', 'compare', 'recently-viewed', 'order-confirmation', 'about', 'contact', 'faq', 'track-order', 'size-guide', 'policy'];

const categoryGroups = computed(() => {
    const groups = {};
    data.categories.forEach((category) => {
        groups[category.group] = groups[category.group] || [];
        groups[category.group].push(category);
    });
    return Object.entries(groups).map(([name, items]) => ({ name, items }));
});

const TrustStrip = defineComponent({
    template: `<section class="trust-strip"><span>Easy returns</span><span>Secure payment</span><span>Fast delivery</span></section>`,
});

const ProductShelf = defineComponent({
    components: { ProductCard },
    props: { title: String, eyebrow: String, products: Array },
    template: `<section class="section-block"><div class="section-head"><div><p class="eyebrow">{{ eyebrow }}</p><h2>{{ title }}</h2></div><a :href="$toUrl('/shop')">View all</a></div><div class="product-grid"><ProductCard v-for="product in products" :key="product.slug" :product="product" /></div></section>`,
});

const HomePage = defineComponent({
    components: { CategoryCard, ProductCard, TrustStrip, ProductShelf },
    setup() {
        const heroCategory = computed(() => data.categories?.[0] || null);
        return { data, addToCart, formatPrice, heroCategory };
    },
    template: `
        <section class="hero hero--home">
            <picture><img class="hero__image" :src="data.store.hero_image" :alt="data.store.hero_title" fetchpriority="high"></picture>
            <div class="hero__content">
                <p class="eyebrow">{{ data.store.hero_slogan }}</p>
                <h1>{{ data.store.hero_title }}</h1>
                <p>{{ data.store.hero_description }}</p>
                <div class="hero__actions"><a v-if="heroCategory" class="button button--gold" :href="$toUrl('/category/' + heroCategory.slug)">Shop {{ heroCategory.name }}</a><a class="button button--ghost" :href="$toUrl('/shop')">Explore Store</a></div>
            </div>
        </section>
        <TrustStrip />
        <section class="section-block"><div class="section-head"><div><p class="eyebrow">Shop by Category</p><h2>Main Shopping Categories</h2></div><a :href="$toUrl('/categories')">View all</a></div><div class="category-grid"><CategoryCard v-for="category in data.categories" :key="category.slug" :category="category" /></div></section>
        <section class="promo-grid"><a v-for="collection in data.collections" :key="collection.slug" :href="$toUrl('/collection/' + collection.slug)" class="promo-card"><img :src="collection.image" :alt="collection.name"><span><small>Collection</small><strong>{{ collection.name }}</strong><em>{{ collection.description }}</em></span></a></section>
        <ProductShelf title="New Arrivals" eyebrow="Fresh Drops" :products="data.newArrivals" />
        <ProductShelf title="Best Sellers" eyebrow="Customer Favorites" :products="data.bestSellers" />
        <ProductShelf title="Trending Products" eyebrow="Popular Now" :products="data.featuredProducts" />
        <section class="newsletter"><div><p class="eyebrow">TBrand Updates</p><h2>Get new drops and private offers</h2></div><form @submit.prevent=""><label class="sr-only" for="newsletter">Email</label><input id="newsletter" type="email" placeholder="Email address"><button class="button button--gold">Subscribe</button></form></section>
        <section class="social-gallery"><img v-for="product in data.allProducts.slice(0, 6)" :key="product.slug" :src="product.images[0]" :alt="product.name"></section>
    `,
});

const CategoriesPage = defineComponent({
    components: { CategoryCard },
    setup() { return { data }; },
    template: `<section class="section-block categories-page"><p class="eyebrow">All Categories</p><h1 class="categories-page__title">Shop by Category</h1><p class="muted">Explore our complete range and find what suits you.</p><div class="category-grid category-grid--wide"><CategoryCard v-for="category in data.categories" :key="category.slug" :category="category" /></div></section>`,
});

const LatestStockSlider = defineComponent({
    props: { category: Object, products: Array },
    setup(props) {
        const active = ref(0);
        const latest = computed(() => [...(props.products || [])]
            .filter((product) => product.stock !== 'Out of stock')
            .sort((a, b) => {
                if (a.badge === 'New' && b.badge !== 'New') return -1;
                if (b.badge === 'New' && a.badge !== 'New') return 1;
                return b.reviews - a.reviews;
            }));
        const current = computed(() => latest.value[active.value % Math.max(latest.value.length, 1)]);
        const move = (direction) => {
            const total = latest.value.length || 1;
            active.value = (active.value + direction + total) % total;
        };

        return { active, latest, current, move, formatPrice, addToCart };
    },
    template: `
        <section v-if="current" class="latest-stock" aria-label="Latest in stock products">
            <div class="latest-stock__media">
                <img :src="current.images[0]" :alt="current.name">
                <span class="badge">{{ current.badge || 'In Stock' }}</span>
            </div>
            <div class="latest-stock__content">
                <p class="eyebrow">Latest In Stock</p>
                <h2>{{ current.name }}</h2>
                <p>{{ current.short_description }}</p>
                <dl class="latest-stock__facts">
                    <div><dt>Stock</dt><dd>{{ current.stock }}</dd></div>
                    <div><dt>Material</dt><dd>{{ current.fabric }}</dd></div>
                    <div><dt>Colors</dt><dd>{{ current.colors.length }} options</dd></div>
                    <div><dt>Sizes</dt><dd>{{ current.sizes.slice(0, 3).join(', ') }}</dd></div>
                </dl>
                <div class="price-row price-row--large">
                    <strong>{{ formatPrice(current.price) }}</strong>
                    <s v-if="current.original_price">{{ formatPrice(current.original_price) }}</s>
                </div>
                <div class="latest-stock__actions">
                    <a class="button button--ghost" :href="$toUrl('/product/' + current.slug)">View details</a>
                    <button class="button button--gold" type="button" @click="addToCart(current)">Add to cart</button>
                </div>
            </div>
            <div class="latest-stock__next" v-if="latest.length > 1">
                <div class="latest-stock__next-head">
                    <strong>Latest products</strong>
                    <span>
                        <button class="icon-button" type="button" aria-label="Previous latest product" @click="move(-1)">‹</button>
                        <button class="icon-button" type="button" aria-label="Next latest product" @click="move(1)">›</button>
                    </span>
                </div>
                <div class="latest-stock__track">
                    <button
                        v-for="(product, index) in latest"
                        :key="product.slug"
                        class="latest-stock__item"
                        :class="{ active: product.slug === current.slug }"
                        type="button"
                        @click="active = index"
                    >
                        <img :src="product.images[0]" :alt="product.name">
                        <span>{{ product.name }}</span>
                        <small>{{ formatPrice(product.price) }}</small>
                    </button>
                </div>
            </div>
        </section>
    `,
});

const CategoryRibbon = defineComponent({
    setup() { return { items: data.sliderItems || [] }; },
    template: `<section v-if="items.length" class="category-ribbon" aria-label="Explore store items"><div class="category-ribbon__track"><a v-for="item in items" :key="item.type + '-' + item.id" :href="item.url"><img :src="item.image" :alt="item.name"><span>{{ item.name }}</span></a></div></section>`,
});

const CategoryPage = defineComponent({
    components: { ProductGrid, TrustStrip, LatestStockSlider, CategoryRibbon },
    props: { category: Object, products: Array },
    template: `
        <section class="hero hero--category"><picture><img class="hero__image" :src="category.hero" :alt="category.name" fetchpriority="high"></picture><div class="hero__content"><p class="eyebrow">Category Storefront</p><h1>{{ category.name }}</h1><p>{{ category.description }}</p><div class="hero__actions"><a class="button button--gold" href="#listing">Shop now</a><a class="button button--ghost" :href="$toUrl('/categories')">All categories</a></div></div></section>
        <TrustStrip />
        <section v-if="category.sections.length" class="section-block category-sections"><div class="section-head section-head--compact"><div><p class="eyebrow">Browse Sections</p><h2>{{ category.name }}</h2></div></div><div class="chips"><a href="#listing">All Products</a><a v-for="section in category.sections" :key="section" href="#listing">{{ section }}</a></div></section>
        <CategoryRibbon />
        <LatestStockSlider :category="category" :products="products" />
        <ProductGrid id="listing" :products="products" title="Products" :eyebrow="category.name" :category-slug="category.slug" expand-variants />
        <CategoryRibbon />
    `,
});

const CollectionPage = defineComponent({
    components: { ProductGrid },
    props: { collection: Object, products: Array },
    template: `<section class="hero hero--category"><picture><img class="hero__image" :src="collection.image" :alt="collection.name" fetchpriority="high"></picture><div class="hero__content"><p class="eyebrow">Collection</p><h1>{{ collection.name }}</h1><p>{{ collection.description }}</p></div></section><ProductGrid :products="products" :title="collection.name" eyebrow="Collection Products" />`,
});

const CartDrawer = defineComponent({
    props: { open: Boolean },
    emits: ['close'],
    setup() { return { cartItems, removeFromCart, updateQuantity, subtotal, total, formatPrice }; },
    template: `
        <div v-if="open" class="drawer-backdrop" @click.self="$emit('close')">
            <aside class="cart-drawer" aria-label="Cart drawer">
                <div class="filter-panel__head"><strong>Cart</strong><button class="icon-button" type="button" aria-label="Close cart" @click="$emit('close')">×</button></div>
                <div v-if="cartItems.length" class="line-items line-items--drawer"><article v-for="item in cartItems" :key="item.key" class="line-item"><img :src="item.image" :alt="item.product.name + ' - ' + item.color"><div><strong>{{ item.product.name }}</strong><p>{{ item.color }} / {{ item.size }}</p><div class="quantity"><button @click="updateQuantity(item.key, item.quantity - 1)">−</button><input :value="item.quantity" readonly><button @click="updateQuantity(item.key, item.quantity + 1)">+</button></div></div><button class="icon-button" @click="removeFromCart(item.key)">×</button></article></div>
                <div v-else class="empty-state"><strong>Your cart is empty</strong><a class="button button--gold" :href="$toUrl('/shop')">Shop now</a></div>
                <div v-if="cartItems.length" class="drawer-total"><span>Subtotal</span><strong>{{ formatPrice(subtotal) }}</strong><a class="button button--gold button--full" :href="$toUrl('/checkout')">Checkout</a><a class="button button--ghost button--full" :href="$toUrl('/cart')">View cart</a></div>
            </aside>
        </div>
    `,
});

const SearchOverlay = defineComponent({
    props: { open: Boolean },
    emits: ['close'],
    setup() {
        const query = ref('');
        const results = computed(() => !query.value.trim() ? [] : data.allProducts.filter((product) => product.name.toLowerCase().includes(query.value.toLowerCase())).slice(0, 6));
        const submit = () => { if (query.value.trim()) window.location.href = toUrl(`/search?q=${encodeURIComponent(query.value.trim())}`); };
        return { query, results, submit };
    },
    template: `
        <div v-if="open" class="modal-backdrop search-overlay" role="dialog" aria-modal="true" aria-label="Search">
            <div class="modal modal--search">
                <button class="icon-button modal__close" type="button" aria-label="Close search" @click="$emit('close')">×</button>
                <form @submit.prevent="submit"><label class="sr-only" for="site-search">Search products</label><input id="site-search" v-model="query" type="search" autofocus placeholder="Search products and categories..."><button class="button button--gold">Search</button></form>
                <a v-for="product in results" :key="product.slug" class="search-result" :href="$toUrl('/product/' + product.slug)"><img :src="product.images[0]" :alt="product.name"><span>{{ product.name }}</span></a>
            </div>
        </div>
    `,
});

const ToastStack = defineComponent({
    setup() { return { state }; },
    template: `<div class="toast-stack" aria-live="polite"><div v-for="toast in state.toasts" :key="toast.id" class="toast" :class="'toast--' + toast.type">{{ toast.message }}</div></div>`,
});

const NotFound = defineComponent({
    template: `<section class="confirmation"><div class="confirmation__mark">404</div><p class="eyebrow">Page Not Found</p><h1>This TBrand page is not available</h1><p>The link may be old, or the product may arrive in a later collection.</p><a class="button button--gold" :href="$toUrl('/shop')">Shop products</a></section>`,
});
</script>
