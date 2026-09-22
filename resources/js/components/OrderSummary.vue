<template>
    <aside class="summary-card">
        <div class="section-head section-head--compact">
            <div>
                <p class="eyebrow">Order Summary</p>
                <h2>{{ cartItems.length }} items</h2>
            </div>
            <a :href="$toUrl('/cart')">Edit cart</a>
        </div>

        <div class="progress">
            <span :style="{ width: `${progress}%` }"></span>
        </div>
        <p class="success" v-if="advanceFreeShipping">Free shipping applied for advance payment.</p>
        <p class="muted" v-else-if="data.store.free_shipping_threshold > 0 && subtotal < data.store.free_shipping_threshold">
            Add {{ formatPrice(data.store.free_shipping_threshold - subtotal) }} for free shipping.
        </p>
        <p class="success" v-else-if="!shipping">Free shipping unlocked.</p>

        <div class="coupon">
            <label for="coupon">Coupon</label>
            <div>
                <input id="coupon" v-model="couponCode" type="text" placeholder="TBRAND500">
                <button type="button" @click="applyCoupon(couponCode)">Apply</button>
            </div>
        </div>

        <dl class="totals">
            <div><dt>Subtotal</dt><dd>{{ formatPrice(subtotal) }}</dd></div>
            <div><dt>Shipping</dt><dd>{{ shipping ? formatPrice(shipping) : 'Free' }}</dd></div>
            <div><dt>Discount</dt><dd>-{{ formatPrice(discount) }}</dd></div>
            <div v-if="advanceDiscount"><dt>Advance payment discount</dt><dd>-{{ formatPrice(advanceDiscount) }}</dd></div>
            <div class="totals__total"><dt>Total</dt><dd>{{ formatPrice(total) }}</dd></div>
        </dl>

        <a class="button button--gold button--full" :href="$toUrl('/checkout')">Checkout</a>
        <a class="button button--whatsapp button--full" :href="whatsappUrl" target="_blank" rel="noreferrer">WhatsApp checkout</a>
    </aside>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useCommerce } from '../composables/useCommerce';

const { data, cartItems, subtotal, shipping, discount, advanceDiscount, advanceFreeShipping, total, applyCoupon, createWhatsAppUrl, formatPrice } = useCommerce();
const couponCode = ref('');
const progress = computed(() => data.store.free_shipping_threshold > 0
    ? Math.min(100, (subtotal.value / data.store.free_shipping_threshold) * 100)
    : 100);
const whatsappUrl = computed(() => createWhatsAppUrl([
    'TBrand cart checkout',
    ...cartItems.value.map((item) => `${item.product.name} (${item.color}/${item.size}) x ${item.quantity} - ${formatPrice(item.product.price * item.quantity)}`),
    `Total: ${formatPrice(total.value)}`,
].join('\n')));
</script>
