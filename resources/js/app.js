import { createApp } from 'vue/dist/vue.esm-bundler.js';
import App from './App.vue';
import { toUrl } from './composables/useCommerce';

const storefront = document.querySelector('#tbrand-app');
if (storefront) {
    const app = createApp(App);
    app.config.globalProperties.$toUrl = toUrl;
    app.mount(storefront);
}
