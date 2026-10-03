import './bootstrap';
import {
    createIcons, ArrowLeft, ArrowRight, CalendarSearch, ChartColumn, CopyPlus, Eye, FileImage, FilePlus2,
    Files, FolderTree, Landmark, Layers3, LayoutDashboard, LogOut, MessageCircle,
    Package, Pencil, Phone, Play, Plus, RotateCcw, Save, Search, Settings,
    ShieldCheck, ShoppingBag, ShoppingCart, Trash2, TriangleAlert, Upload, Users, Warehouse, X, Zap,
} from 'lucide';

const adminIcons = { ArrowLeft, ArrowRight, CalendarSearch, ChartColumn, CopyPlus, Eye, FileImage, FilePlus2, Files, FolderTree, Landmark, Layers3, LayoutDashboard, LogOut, MessageCircle, Package, Pencil, Phone, Play, Plus, RotateCcw, Save, Search, Settings, ShieldCheck, ShoppingBag, ShoppingCart, Trash2, TriangleAlert, Upload, Users, Warehouse, X, Zap };
const renderIcons = () => createIcons({ icons: adminIcons, attrs: { 'stroke-width': 1.8 } });
renderIcons();
window.renderAdminIcons = renderIcons;

document.addEventListener('wheel', (event) => {
    if (event.target instanceof HTMLInputElement && event.target.type === 'number' && document.activeElement === event.target) event.target.blur();
}, { passive: true });
