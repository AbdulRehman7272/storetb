<template>
    <section v-if="page === 'wishlist' || page === 'compare' || page === 'recently-viewed'" class="section-block">
        <div class="section-head">
            <div>
                <p class="eyebrow">{{ label.eyebrow }}</p>
                <h1>{{ label.title }}</h1>
                <p class="muted">{{ label.description }}</p>
            </div>
        </div>
        <div v-if="items.length" class="product-grid" :class="{ 'compare-grid': page === 'compare' }">
            <ProductCard v-for="product in items" :key="product.cardKey || product.slug" :product="product" />
        </div>
        <div v-else class="empty-state">
            <strong>{{ label.empty }}</strong>
            <p>Explore the shop and save products you like.</p>
            <a class="button button--gold" :href="$toUrl('/shop')">Shop products</a>
        </div>
    </section>

    <section v-else-if="page === 'order-confirmation'" class="confirmation">
        <div class="confirmation__mark">✓</div>
        <p class="eyebrow">Order Confirmed</p>
        <h1>{{ order ? `Thank you, ${order.customer.full_name || 'Customer'}` : 'Order confirmation' }}</h1>
        <p>{{ order ? `Your frontend order ${order.number} has been created.` : 'No recent mock order was found in this browser.' }}</p>
        <dl v-if="order" class="totals confirmation__details">
            <div><dt>Order number</dt><dd>{{ order.number }}</dd></div>
            <div><dt>Payment</dt><dd>{{ order.customer.payment_method === 'cod' ? 'Cash on Delivery' : 'Bank / Wallet Transfer' }}</dd></div>
            <div><dt>Total</dt><dd>{{ formatPrice(order.total) }}</dd></div>
        </dl>
        <a class="button button--gold" :href="$toUrl('/shop')">Continue shopping</a>
    </section>

    <section v-else-if="page === 'track-order'" class="two-column-page utility-page">
        <div>
            <p class="eyebrow">Track Order</p>
            <h1>Track Your Order</h1>
            <p>Enter your order number and phone to preview the customer tracking flow.</p>
            <form class="support-form" @submit.prevent="tracked = true">
                <label>Order number<input v-model="track.number" type="text" placeholder="TB-123456"></label>
                <label>Phone number<input v-model="track.phone" type="tel" placeholder="0300 1234567"></label>
                <button class="button button--gold" type="submit">Track order</button>
            </form>
        </div>
        <div class="summary-card">
            <h2>{{ tracked ? 'In Transit' : 'Tracking Preview' }}</h2>
            <ol class="timeline">
                <li class="done">Order placed</li>
                <li :class="{ done: tracked }">Packed by TBrand</li>
                <li>Out for delivery</li>
                <li>Delivered</li>
            </ol>
        </div>
    </section>

    <section v-else-if="page === 'size-guide'" class="section-block utility-page">
        <p class="eyebrow">Size Guide</p>
        <h1>TBrand Size Guide</h1>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Size</th><th>Chest</th><th>Waist</th><th>Length</th><th>Best for</th></tr></thead>
                <tbody>
                    <tr><td>XS</td><td>34 in</td><td>28 in</td><td>38 in</td><td>Petite fit</td></tr>
                    <tr><td>S</td><td>36 in</td><td>30 in</td><td>39 in</td><td>Regular small</td></tr>
                    <tr><td>M</td><td>38 in</td><td>32 in</td><td>40 in</td><td>Regular medium</td></tr>
                    <tr><td>L</td><td>41 in</td><td>35 in</td><td>41 in</td><td>Relaxed fit</td></tr>
                    <tr><td>XL</td><td>44 in</td><td>38 in</td><td>42 in</td><td>Extra relaxed</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section v-else-if="page === 'contact'" class="two-column-page utility-page">
        <div>
            <p class="eyebrow">Contact Us</p>
            <h1>Customer Care</h1>
            <p>Need help with sizing, delivery, exchange, or product selection? Send a message and our team will guide you.</p>
            <form class="support-form" @submit.prevent="toast('Thanks. Your message is ready for the future backend.', 'success')">
                <label>Name<input type="text" required></label>
                <label>Email<input type="email" required></label>
                <label>Message<textarea rows="5" required></textarea></label>
                <button class="button button--gold" type="submit">Send message</button>
            </form>
        </div>
        <div class="summary-card">
            <h2>TBrand Support</h2>
            <p>{{ data.store.address }}</p>
            <p>{{ data.store.phone }}</p>
            <p>{{ data.store.email }}</p>
            <a class="button button--whatsapp button--full" :href="createWhatsAppUrl('Hello TBrand, I need help with an order.')" target="_blank" rel="noreferrer">Chat on WhatsApp</a>
        </div>
    </section>

    <section v-else-if="page === 'faq'" class="section-block utility-page">
        <p class="eyebrow">FAQ</p>
        <h1>Frequently Asked Questions</h1>
        <div class="accordions">
            <details v-for="item in faqs" :key="item.q" open>
                <summary>{{ item.q }}</summary>
                <p>{{ item.a }}</p>
            </details>
        </div>
    </section>

    <section v-else-if="page === 'policy'" class="section-block utility-page policy-page">
        <p class="eyebrow">Policy</p>
        <h1>{{ context.policy.title }}</h1>
        <div class="rich-content policy-content" v-html="context.policy.description"></div>
        <ul class="policy-list">
            <li v-for="item in context.policy.items" :key="item">{{ item }}</li>
        </ul>
    </section>

    <section v-else class="section-block prose-page utility-page">
        <p class="eyebrow">{{ content?.title || 'TBrand' }}</p>
        <h1>{{ content?.title || 'TBrand' }}</h1>
        <div v-if="page === 'about'" class="about-grid">
            <img v-if="data.categories?.[0]" :src="data.categories[0].hero" :alt="data.categories[0].name">
            <div>
                <p>TBrand is built around style, quality, and trust for Pakistani customers who want premium presentation without a complicated shopping experience.</p>
                <p>Explore {{ categorySummary }} through one simple, consistent shopping experience.</p>
            </div>
        </div>
        <div v-else class="rich-content policy-content" v-html="content?.description"></div>
    </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import ProductCard from './ProductCard.vue';
import { useCommerce } from '../composables/useCommerce';

const props = defineProps({
    page: { type: String, required: true },
    context: { type: Object, default: () => ({}) },
});

const { data, state, productBySlug, createWhatsAppUrl, toast, formatPrice } = useCommerce();
const tracked = ref(false);
const track = reactive({ number: '', phone: '' });

const order = computed(() => state.order);
const content = computed(() => props.context.contentPage);
const categorySummary = computed(() => {
    const names = (data.categories || []).map((category) => category.name);
    if (!names.length) return 'our latest products';
    if (names.length === 1) return names[0];
    if (names.length === 2) return names.join(' and ');
    const visible = names.slice(0, 4);
    const last = visible.pop();
    return `${visible.join(', ')} and ${last}${names.length > 4 ? ', plus more' : ''}`;
});
const label = computed(() => ({
    wishlist: { eyebrow: 'Saved Pieces', title: 'Wishlist', description: 'Products you saved for later.', empty: 'No wishlist items yet' },
    compare: { eyebrow: 'Side by Side', title: 'Compare Products', description: 'Compare up to four selected products.', empty: 'No products selected for compare' },
    'recently-viewed': { eyebrow: 'Your Trail', title: 'Recently Viewed', description: 'Products opened in this browser.', empty: 'No recently viewed products yet' },
}[props.page] || {}));

const items = computed(() => {
    if (props.page === 'wishlist') return state.wishlist.map(productBySlug).filter(Boolean);
    if (props.page === 'compare') return state.compare.map(productBySlug).filter(Boolean);
    if (props.page === 'recently-viewed') return state.recentlyViewed.map(productBySlug).filter(Boolean);
    return [];
});

const faqs = [
    { q: 'Do you deliver across Pakistan?', a: 'Yes. TBrand delivers to supported locations across Pakistan through available courier services.' },
    { q: 'Can I exchange a size?', a: 'Eligible unused products can be exchanged within 14 days according to the return and exchange policy.' },
    { q: 'Is Cash on Delivery available?', a: 'Cash on Delivery is available when shown during checkout. Bank or wallet transfer may also be offered.' },
    { q: 'How long does delivery take?', a: 'Most confirmed orders are expected within 2-5 working days, depending on the destination and courier service.' },
];
</script>
