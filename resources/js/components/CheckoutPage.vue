<template>
    <div><section class="checkout-page">
        <div class="checkout-form">
            <div class="brand-lockup"><img :src="data.store.logo" alt="TBrand"></div><p class="eyebrow">Secure Checkout</p><h1>Guest Checkout</h1>
            <form novalidate @submit.prevent="placeOrder">
                <details open><summary><span>1</span> Contact Information</summary><div class="form-grid">
                    <label>Full name *<input v-model.trim="form.full_name" type="text" autocomplete="name" :class="{ invalid: errors.full_name }"><small>{{ errors.full_name }}</small></label>
                    <label>Phone number *<input v-model.trim="form.mobile" type="tel" autocomplete="tel" placeholder="03XXXXXXXXX" :class="{ invalid: errors.mobile }"><small>{{ errors.mobile }}</small></label>
                    <label>Email (optional)<input v-model.trim="form.email" type="email" autocomplete="email" :class="{ invalid: errors.email }"><small>{{ errors.email }}</small></label>
                </div></details>
                <details open><summary><span>2</span> Shipping Address</summary><div class="form-grid">
                    <label class="span-2">Complete address *<input v-model.trim="form.address" autocomplete="street-address" :class="{ invalid: errors.address }"><small>{{ errors.address }}</small></label>
                    <label>Province *<select v-model="form.province" :class="{ invalid: errors.province }"><option value="">Select province</option><option>Punjab</option><option>Sindh</option><option>Khyber Pakhtunkhwa</option><option>Balochistan</option><option>Islamabad Capital Territory</option><option>Gilgit-Baltistan</option><option>Azad Kashmir</option></select><small>{{ errors.province }}</small></label>
                    <label>City *<input v-model.trim="form.city" autocomplete="address-level2" :class="{ invalid: errors.city }"><small>{{ errors.city }}</small></label>
                </div></details>
                <details open><summary><span>3</span> Payment Method</summary>
                    <div class="payment-choice"><label class="radio-card"><input v-model="form.payment_method" type="radio" value="cod"> <span><strong>Cash on Delivery</strong><small>Pay when your order arrives</small></span></label><label class="radio-card"><input v-model="form.payment_method" type="radio" value="manual"> <span><strong>Bank / Wallet Transfer</strong><small>Send payment and upload proof</small></span></label></div>
                    <div v-if="form.payment_method === 'manual'" class="bank-payment-panel">
                        <label>Payment account *<select v-model="form.payment_account_id" :class="{ invalid: errors.payment_account_id }"><option value="">Select account</option><option v-for="account in data.paymentAccounts" :key="account.id" :value="account.id">{{ account.name }} · {{ account.account_title }}</option></select><small>{{ errors.payment_account_id }}</small></label>
                        <article v-if="selectedAccount" class="account-details"><strong>{{ selectedAccount.name }}</strong><span>{{ selectedAccount.account_title }}</span><span v-if="selectedAccount.account_number">Account: {{ selectedAccount.account_number }}</span><span v-if="selectedAccount.iban">IBAN: {{ selectedAccount.iban }}</span><p>{{ selectedAccount.instructions }}</p></article>
                        <div class="form-grid"><label>Transaction reference<input v-model.trim="form.transaction_reference"></label><label>Payment screenshot<input type="file" accept="image/*,.pdf" @change="proof = $event.target.files[0]"></label></div>
                    </div>
                    <label>Order notes<textarea v-model.trim="form.notes" rows="3" placeholder="Delivery instructions"></textarea></label>
                </details>
                <div v-if="submitError" class="checkout-alert" role="alert">{{ submitError }}</div>
                <button class="button button--gold button--full" type="submit" :disabled="submitting || !cartItems.length">{{ submitting ? 'Placing order…' : 'Place order' }}</button>
                <a class="button button--whatsapp button--full" :href="whatsappUrl" target="_blank" rel="noreferrer">Order through WhatsApp</a>
            </form>
        </div><OrderSummary />
    </section><ProductGrid :products="data.featuredProducts.slice(0, 4)" title="You May Also Like" eyebrow="Add Before You Order" /></div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import OrderSummary from './OrderSummary.vue';
import ProductGrid from './ProductGrid.vue';
import { useCommerce } from '../composables/useCommerce';
const { data, cartItems, total, state, clearCart, createWhatsAppUrl, toast, formatPrice, toUrl } = useCommerce();
const form = reactive({ full_name:'', mobile:'', email:'', address:'', province:'', city:'', payment_method:'cod', payment_account_id:'', transaction_reference:'', notes:'' });
const errors = reactive({}); const proof = ref(null); const submitting = ref(false); const submitError = ref('');
const selectedAccount = computed(()=>data.paymentAccounts?.find(a=>String(a.id)===String(form.payment_account_id)));
const whatsappUrl = computed(()=>createWhatsAppUrl(['TBrand checkout order',`Customer: ${form.full_name||'Guest'}`,`Phone: ${form.mobile||'-'}`,`City: ${form.city||'-'}`,...cartItems.value.map(i=>`${i.product.name} (${i.color}/${i.size}) x ${i.quantity}`),`Total: ${formatPrice(total.value)}`].join('\n')));
function validate(){Object.keys(errors).forEach(k=>delete errors[k]);if(!form.full_name)errors.full_name='Full name is required.';if(!/^(\+92|0)?3\d{9}$/.test(form.mobile.replace(/\s/g,'')))errors.mobile='Enter a valid Pakistani mobile number.';if(form.email&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email))errors.email='Enter a valid email address.';if(!form.address)errors.address='Complete address is required.';if(!form.province)errors.province='Select a province.';if(!form.city)errors.city='City is required.';if(form.payment_method==='manual'&&!form.payment_account_id)errors.payment_account_id='Choose the account you paid.';return !Object.keys(errors).length}
async function placeOrder(){submitError.value='';if(!validate()){submitError.value='Please correct the highlighted fields above.';document.querySelector('.invalid')?.scrollIntoView({behavior:'smooth',block:'center'});return}if(!cartItems.value.length){submitError.value='Your cart is empty.';return}submitting.value=true;const body=new FormData();Object.entries(form).forEach(([k,v])=>body.append(k,v??''));cartItems.value.forEach((item,i)=>{body.append(`items[${i}][product_id]`,item.product.id);if(item.variant_id)body.append(`items[${i}][variant_id]`,item.variant_id);body.append(`items[${i}][quantity]`,item.quantity)});if(proof.value)body.append('payment_screenshot',proof.value);try{const response=await fetch(toUrl('/checkout/order'),{method:'POST',headers:{'X-CSRF-TOKEN':data.csrfToken,'Accept':'application/json'},body});const result=await response.json();if(!response.ok){const first=result.errors?Object.values(result.errors)[0]?.[0]:result.message;throw new Error(first||'Order could not be placed.')}state.order={number:result.order_number,customer:{...form},items:cartItems.value,total:total.value};clearCart();window.location.href=toUrl('/order-confirmation')}catch(error){submitError.value=error.message;toast(error.message,'error')}finally{submitting.value=false}}
</script>
