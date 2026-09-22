<template>
    <section class="two-column-page">
        <div>
            <p class="eyebrow">Shopping Cart</p>
            <h1>Your Cart</h1>
            <div v-if="cartItems.length" class="line-items">
                <article v-for="item in cartItems" :key="item.key" class="line-item">
                    <img :src="item.image" :alt="`${item.product.name} - ${item.color}`">
                    <div>
                        <a :href="$toUrl('/product/' + item.product.slug)"><strong>{{ item.product.name }}</strong></a>
                        <p>{{ item.color }} / {{ item.size }}</p>
                        <button type="button" @click="moveToWishlist(item.key)">Move to wishlist</button>
                    </div>
                    <div class="quantity">
                        <button type="button" aria-label="Decrease quantity" @click="updateQuantity(item.key, item.quantity - 1)">−</button>
                        <input :value="item.quantity" type="number" min="1" aria-label="Quantity" @change="updateQuantity(item.key, Number($event.target.value))">
                        <button type="button" aria-label="Increase quantity" @click="updateQuantity(item.key, item.quantity + 1)">+</button>
                    </div>
                    <strong>{{ formatPrice(item.price * item.quantity) }}</strong>
                    <button class="icon-button" type="button" aria-label="Remove item" @click="removeFromCart(item.key)">×</button>
                </article>
            </div>
            <div v-else class="empty-state">
                <strong>Your cart is empty</strong>
                <p>Explore the latest products from our store categories.</p>
                <a class="button button--gold" :href="$toUrl('/shop')">Shop now</a>
            </div>
        </div>
        <OrderSummary v-if="cartItems.length" />
    </section>
</template>

<script setup>
import OrderSummary from './OrderSummary.vue';
import { useCommerce } from '../composables/useCommerce';

const { cartItems, updateQuantity, removeFromCart, moveToWishlist, formatPrice } = useCommerce();
</script>
