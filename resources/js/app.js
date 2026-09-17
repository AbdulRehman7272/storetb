import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import App from './App.vue';
import { toUrl } from './composables/useCommerce';

const app = createApp(App);

app.config.globalProperties.$toUrl = toUrl;
app.mount('#tbrand-app');
