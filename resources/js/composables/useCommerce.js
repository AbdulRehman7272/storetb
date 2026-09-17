import { computed, reactive, watch } from 'vue';

const data = window.TBRAND || {};

function makeUrl(path = '/') {
    if (!path) return data.baseUrl || '/';
    if (/^(https?:|mailto:|tel:|#)/.test(path)) return path;

    const base = (data.baseUrl || '').replace(/\/$/, '');
    const cleanPath = path.startsWith('/') ? path : `/${path}`;

    return `${base}${cleanPath}` || cleanPath;
}

function normalizeAssetUrls(value) {
    if (typeof value === 'string') {
        return value.startsWith('/assets/') ? makeUrl(value) : value;
    }

    if (Array.isArray(value)) {
        return value.map((item) => normalizeAssetUrls(item));
    }

    if (value && typeof value === 'object') {
        Object.keys(value).forEach((key) => {
            value[key] = normalizeAssetUrls(value[key]);
        });
    }

    return value;
}

normalizeAssetUrls(data);

const read = (key, fallback) => {
    try {
        return JSON.parse(localStorage.getItem(key)) ?? fallback;
    } catch {
        return fallback;
    }
};

const write = (key, value) => localStorage.setItem(key, JSON.stringify(value));

export const state = reactive({
    cart: read('tbrand_cart', []),
    wishlist: read('tbrand_wishlist', []),
    compare: read('tbrand_compare', []),
    recentlyViewed: read('tbrand_recently', []),
    order: read('tbrand_order', null),
    coupon: read('tbrand_coupon', null),
    toasts: [],
});

watch(() => state.cart, (value) => write('tbrand_cart', value), { deep: true });
watch(() => state.wishlist, (value) => write('tbrand_wishlist', value), { deep: true });
watch(() => state.compare, (value) => write('tbrand_compare', value), { deep: true });
watch(() => state.recentlyViewed, (value) => write('tbrand_recently', value), { deep: true });
watch(() => state.order, (value) => write('tbrand_order', value), { deep: true });
watch(() => state.coupon, (value) => write('tbrand_coupon', value), { deep: true });

export function formatPrice(value) {
    return `${data.store?.currency || 'Rs.'} ${Number(value || 0).toLocaleString('en-PK')}`;
}

export function discountPercent(product) {
    if (!product.original_price || product.original_price <= product.price) return null;
    return Math.round(((product.original_price - product.price) / product.original_price) * 100);
}

export function productBySlug(slug) {
    return (data.allProducts || data.products || []).find((product) => product.slug === slug);
}

export function categoryBySlug(slug) {
    return (data.categories || []).find((category) => category.slug === slug);
}

export function toUrl(path = '/') {
    return makeUrl(path);
}

export function useCommerce() {
    const toast = (message, type = 'success') => {
        const id = Date.now() + Math.random();
        state.toasts.push({ id, message, type });
        window.setTimeout(() => {
            state.toasts = state.toasts.filter((item) => item.id !== id);
        }, 3200);
    };

    const cartCount = computed(() => state.cart.reduce((sum, item) => sum + item.quantity, 0));
    const cartItems = computed(() => state.cart.map((item) => ({
        ...item,
        product: productBySlug(item.slug),
    })).filter((item) => item.product));
    const subtotal = computed(() => cartItems.value.reduce((sum, item) => sum + (item.price ?? item.product.price) * item.quantity, 0));
    const discount = computed(() => state.coupon?.amount || 0);
    const shipping = computed(() => subtotal.value >= data.store.free_shipping_threshold || subtotal.value === 0 ? 0 : data.store.shipping_fee);
    const total = computed(() => Math.max(0, subtotal.value - discount.value + shipping.value));

    const addToCart = (product, variant = {}) => {
        const color = variant.color || product.colors?.[0] || 'Default';
        const size = variant.size || product.sizes?.[0] || 'One Size';
        const selected = variant.variant || product.variants?.find((item) => item.color === color && (!item.size || item.size === size));
        const key = `${product.slug}:${selected?.id || color + ':' + size}`;
        const existing = state.cart.find((item) => item.key === key);

        if (existing) {
            existing.quantity += variant.quantity || 1;
        } else {
            state.cart.push({ key, slug: product.slug, product_id: product.id, variant_id: selected?.id || null, color, size, price: selected?.price ?? product.price, quantity: variant.quantity || 1 });
        }

        toast(`${product.name} added to cart`);
    };

    const updateQuantity = (key, quantity) => {
        const item = state.cart.find((cartItem) => cartItem.key === key);
        if (!item) return;
        item.quantity = Math.max(1, quantity);
    };

    const removeFromCart = (key) => {
        state.cart = state.cart.filter((item) => item.key !== key);
        toast('Item removed from cart', 'info');
    };

    const toggleList = (list, slug, limit = 99) => {
        const exists = state[list].includes(slug);
        state[list] = exists ? state[list].filter((item) => item !== slug) : [slug, ...state[list]].slice(0, limit);
        toast(exists ? 'Removed' : 'Saved');
    };

    const applyCoupon = (code) => {
        const coupon = data.store.coupon;
        if (code?.trim().toUpperCase() === coupon.code) {
            state.coupon = coupon;
            toast(`${coupon.code} applied`);
            return true;
        }
        toast('Coupon code is not valid', 'error');
        return false;
    };

    const moveToWishlist = (key) => {
        const item = state.cart.find((cartItem) => cartItem.key === key);
        if (!item) return;
        if (!state.wishlist.includes(item.slug)) state.wishlist.unshift(item.slug);
        removeFromCart(key);
        toast('Moved to wishlist');
    };

    const addRecentlyViewed = (slug) => {
        state.recentlyViewed = [slug, ...state.recentlyViewed.filter((item) => item !== slug)].slice(0, 12);
    };

    const clearCart = () => {
        state.cart = [];
        state.coupon = null;
    };

    const createWhatsAppUrl = (message) => `https://wa.me/${data.store.whatsapp}?text=${encodeURIComponent(message)}`;

    return {
        data,
        state,
        cartCount,
        cartItems,
        subtotal,
        discount,
        shipping,
        total,
        toast,
        addToCart,
        updateQuantity,
        removeFromCart,
        moveToWishlist,
        applyCoupon,
        toggleWishlist: (slug) => toggleList('wishlist', slug),
        toggleCompare: (slug) => toggleList('compare', slug, 4),
        addRecentlyViewed,
        clearCart,
        createWhatsAppUrl,
        formatPrice,
        discountPercent,
        productBySlug,
        categoryBySlug,
        toUrl,
    };
}
