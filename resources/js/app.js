import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import App from './App.vue';
import { toUrl } from './composables/useCommerce';
import {
    createIcons, ArrowLeft, ArrowRight, CalendarSearch, ChartColumn, CopyPlus, Eye, FileImage, FilePlus2,
    Files, FolderTree, Landmark, Layers3, LayoutDashboard, LogOut, MessageCircle,
    Package, Pencil, Phone, Play, Plus, RotateCcw, Save, Search, Settings,
    ShieldCheck, ShoppingBag, Trash2, TriangleAlert, Upload, Users, Warehouse, X, Zap,
} from 'lucide';

const storefront = document.querySelector('#tbrand-app');
if (storefront) {
    const app = createApp(App);
    app.config.globalProperties.$toUrl = toUrl;
    app.mount(storefront);
}

const adminIcons = { ArrowLeft, ArrowRight, CalendarSearch, ChartColumn, CopyPlus, Eye, FileImage, FilePlus2, Files, FolderTree, Landmark, Layers3, LayoutDashboard, LogOut, MessageCircle, Package, Pencil, Phone, Play, Plus, RotateCcw, Save, Search, Settings, ShieldCheck, ShoppingBag, Trash2, TriangleAlert, Upload, Users, Warehouse, X, Zap };
const renderIcons = () => createIcons({ icons: adminIcons, attrs: { 'stroke-width': 1.8 } });
renderIcons();
window.renderAdminIcons = renderIcons;

document.addEventListener('wheel', (event) => {
    if (event.target instanceof HTMLInputElement && event.target.type === 'number' && document.activeElement === event.target) {
        event.target.blur();
    }
}, { passive: true });
