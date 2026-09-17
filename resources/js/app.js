import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import App from './App.vue';
import { toUrl } from './composables/useCommerce';
import {
    createIcons, CalendarSearch, ChartColumn, CopyPlus, Eye, FileImage, FilePlus2,
    Files, FolderTree, Landmark, Layers3, LayoutDashboard, LogOut, MessageCircle,
    Package, Pencil, Phone, Play, Plus, RotateCcw, Save, Search, Settings,
    ShieldCheck, ShoppingBag, Trash2, Users, Warehouse, Zap,
} from 'lucide';

const storefront = document.querySelector('#tbrand-app');
if (storefront) {
    const app = createApp(App);
    app.config.globalProperties.$toUrl = toUrl;
    app.mount(storefront);
}

const adminIcons = { CalendarSearch, ChartColumn, CopyPlus, Eye, FileImage, FilePlus2, Files, FolderTree, Landmark, Layers3, LayoutDashboard, LogOut, MessageCircle, Package, Pencil, Phone, Play, Plus, RotateCcw, Save, Search, Settings, ShieldCheck, ShoppingBag, Trash2, Users, Warehouse, Zap };
const renderIcons = () => createIcons({ icons: adminIcons, attrs: { 'stroke-width': 1.8 } });
renderIcons();
window.renderAdminIcons = renderIcons;
