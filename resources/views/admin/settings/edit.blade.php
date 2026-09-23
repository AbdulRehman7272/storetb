@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
@php
    $imageSettings = [
        'main_logo' => ['Main logo', 'Used in the desktop storefront and admin.', 'image/*'],
        'mobile_logo' => ['Mobile logo', 'Compact logo for small screens.', 'image/*'],
        'favicon' => ['Browser icon', 'Shown in browser tabs.', 'image/*,.ico'],
        'default_product_image' => ['Default product image', 'Used when a product has no uploaded image.', 'image/*'],
        'seo_social_image' => ['Social sharing image', 'Default image used when pages are shared.', 'image/*'],
    ];
@endphp
<form class="settings-form" method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">@csrf
    <section class="panel settings-section"><div class="settings-heading"><div><h2>Store Identity</h2><p>Choose images directly. Existing active files remain until you save replacements.</p></div></div><div class="asset-grid">
        @foreach($imageSettings as $key => [$label,$help,$accept])
            @php $current = $settings[$key] ?? null; @endphp
            <label class="asset-picker" data-image-picker>
                <span class="asset-picker__preview"><img src="{{ $current ? asset($current) : asset('assets/brand/monogram-gold-round.png') }}" alt="{{ $label }} preview" data-preview><em>Active</em></span>
                <span class="asset-picker__body"><strong>{{ $label }}</strong><small>{{ $help }}</small><span class="action-btn secondary"><i data-lucide="upload"></i>Choose file</span><input type="file" name="{{ $key }}_upload" accept="{{ $accept }}" data-file-input></span>
            </label>
        @endforeach
    </div></section>

    <section class="panel settings-section"><div class="settings-heading"><div><h2>Search and Social</h2><p>Default metadata used when a product, category, or page does not provide its own values.</p></div></div><div class="settings-fields"><label class="wide">SEO title<input name="seo_title" maxlength="70" value="{{ old('seo_title',$settings['seo_title'] ?? 'TBrand | Premium Pakistani Super Store') }}" required></label><label class="wide">SEO description<textarea name="seo_description" maxlength="160" rows="3" required>{{ old('seo_description',$settings['seo_description'] ?? 'Shop premium fashion and lifestyle products online in Pakistan.') }}</textarea></label></div></section>

    <section class="panel settings-section"><div class="settings-heading"><div><h2>Homepage Hero</h2><p>Manage the main Super Store banner. Keep the subject toward the right side of the image.</p></div></div><div class="hero-settings-grid">
        <label class="asset-picker" data-image-picker>
            @php $heroImage = $settings['homepage_hero_image'] ?? null; @endphp
            <span class="asset-picker__preview asset-picker__preview--hero"><img src="{{ $heroImage ? asset($heroImage) : asset('assets/catalog/banner-accessories.webp') }}" alt="Homepage hero preview" data-preview><em>{{ $heroImage ? 'Active' : 'Fallback' }}</em></span>
            <span class="asset-picker__body"><strong>Hero image</strong><small>Landscape images work best. The focal subject appears on the right.</small><span class="action-btn secondary"><i data-lucide="upload"></i>Choose file</span><input type="file" name="homepage_hero_image_upload" accept="image/*" data-file-input></span>
        </label>
        <div class="settings-fields">
            <label>Hero slogan<input name="homepage_hero_slogan" value="{{ old('homepage_hero_slogan',$settings['homepage_hero_slogan'] ?? 'Style · Quality · Trust') }}" required></label>
            <label>Hero heading<input name="homepage_hero_title" value="{{ old('homepage_hero_title',$settings['homepage_hero_title'] ?? 'Premium TBrand Store') }}" required></label>
            <label class="wide">Hero description<textarea name="homepage_hero_description" rows="4" required>{{ old('homepage_hero_description',$settings['homepage_hero_description'] ?? 'Explore quality products selected for your store.') }}</textarea></label>
            <label class="wide check-card"><input type="hidden" name="show_super_store" value="0"><input type="checkbox" name="show_super_store" value="1" @checked(old('show_super_store', $settings['show_super_store'] ?? ($categories->count() > 1)))><span><strong>Enable Super Store page</strong><small>Show the Super Store link in desktop and mobile navigation.</small></span></label>
            <label class="wide">Default page for /<select name="homepage_category_slug"><option value="">Super Store page</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(old('homepage_category_slug', $settings['homepage_category_slug'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select><small>Select a category to show its complete landing page and products at the main website address.</small></label>
        </div>
    </div></section>

    <section class="panel settings-section"><div class="settings-heading"><div><h2>Landing Page Slider</h2><p>Choose exactly what moves above and below category product sections.</p></div></div><div class="settings-fields" data-slider-settings>
        <label>Slider content<select name="slider_content_type" data-slider-type><option value="categories" @selected(old('slider_content_type',$settings['slider_content_type'] ?? 'categories') === 'categories')>Categories</option><option value="products" @selected(old('slider_content_type',$settings['slider_content_type'] ?? 'categories') === 'products')>Products</option></select></label>
        <label class="check-card"><input type="hidden" name="slider_random" value="0"><input type="checkbox" name="slider_random" value="1" data-slider-random @checked(old('slider_random',$settings['slider_random'] ?? false))><span><strong>Use random items</strong><small>Automatically rotate random saved items of the selected type.</small></span></label>
        <label class="wide" data-slider-selection="categories">Choose saved categories<select class="admin-multiselect" name="slider_category_ids[]" multiple>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(in_array($category->id, old('slider_category_ids',$settings['slider_category_ids'] ?? [])))>{{ $category->name }}</option>@endforeach</select><small>Used when Categories is selected and Random is off.</small></label>
        <label class="wide" data-slider-selection="products">Choose saved products<select class="admin-multiselect" name="slider_product_ids[]" multiple>@foreach($products as $product)<option value="{{ $product->id }}" @selected(in_array($product->id, old('slider_product_ids',$settings['slider_product_ids'] ?? [])))>{{ $product->name }} ({{ $product->sku }})</option>@endforeach</select><small>Used when Products is selected and Random is off.</small></label>
    </div></section>

    <div class="admin-grid">
        <section class="panel settings-section"><div class="settings-heading"><h2>Store Details</h2></div><div class="settings-fields"><label>Store name<input name="store_name" value="{{ old('store_name',$settings['store_name'] ?? 'TBrand') }}" required></label><label>WhatsApp number<input name="whatsapp" value="{{ old('whatsapp',$settings['whatsapp'] ?? '') }}" placeholder="03001234567"></label><label>General email<input type="email" name="general_email" value="{{ old('general_email',$settings['general_email'] ?? '') }}"></label><label>Support email<input type="email" name="support_email" value="{{ old('support_email',$settings['support_email'] ?? '') }}"></label><label class="wide">Business address<textarea name="address" rows="3">{{ old('address',$settings['address'] ?? '') }}</textarea></label><label class="wide check-card"><input type="hidden" name="show_footer_contact" value="0"><input type="checkbox" name="show_footer_contact" value="1" @checked(old('show_footer_contact', $settings['show_footer_contact'] ?? true))><span><strong>Show contact details in footer</strong><small>Email, WhatsApp number, and business address</small></span></label></div></section>
        <section class="panel settings-section"><div class="settings-heading"><h2>Checkout</h2></div><div class="settings-fields"><label>Currency<input name="currency" value="{{ old('currency',$settings['currency'] ?? 'PKR') }}" required></label><label>Shipping charge<input type="number" min="0" step="0.01" name="shipping_charge" value="{{ old('shipping_charge',$settings['shipping_charge'] ?? 250) }}" required></label><label>Free shipping from<input type="number" min="0" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold',$settings['free_shipping_threshold'] ?? 5000) }}" required></label><label>Advance payment discount<input type="number" min="0" step="0.01" name="advance_payment_discount" value="{{ old('advance_payment_discount',$settings['advance_payment_discount'] ?? 0) }}" required><small>Fixed amount deducted for Bank / Wallet Transfer.</small></label><label class="wide check-card"><input type="hidden" name="advance_payment_free_shipping" value="0"><input type="checkbox" name="advance_payment_free_shipping" value="1" @checked(old('advance_payment_free_shipping',$settings['advance_payment_free_shipping'] ?? false))><span><strong>Free shipping for advance payment</strong><small>Waive shipping for Bank / Wallet Transfer orders.</small></span></label></div></section>
    </div>

    <div class="admin-grid">
        <section class="panel settings-section"><div class="settings-heading"><div><h2>Brand Colors</h2><p>Control the storefront palette and text contrast.</p></div><button class="action-btn secondary" type="button" data-reset-colors><i data-lucide="rotate-ccw"></i>Restore defaults</button></div><div class="color-settings"><label><input type="color" name="primary_color" value="{{ old('primary_color',$settings['primary_color'] ?? '#111111') }}"><span><strong>Primary color</strong><small>Main dark brand color</small></span></label><label><input type="color" name="accent_color" value="{{ old('accent_color',$settings['accent_color'] ?? '#c8a45d') }}"><span><strong>Accent color</strong><small>Buttons and highlights</small></span></label><label><input type="color" name="page_text_color" value="{{ old('page_text_color',$settings['page_text_color'] ?? '#f7f2e7') }}" required><span><strong>Page text color</strong><small>Main storefront text</small></span></label><label><input type="color" name="button_text_color" value="{{ old('button_text_color',$settings['button_text_color'] ?? '#161207') }}" required><span><strong>Button text color</strong><small>Text on accent buttons</small></span></label></div></section>
        <section class="panel settings-section"><div class="settings-heading"><h2>Footer Credit</h2></div><div class="settings-fields"><label>Credit text<input name="footer_credit" value="{{ old('footer_credit',$settings['footer_credit'] ?? '') }}"></label><label>Credit link<input type="url" name="footer_credit_url" value="{{ old('footer_credit_url',$settings['footer_credit_url'] ?? '') }}"></label></div></section>
    </div>
    <div class="settings-save"><button class="btn"><i data-lucide="save"></i>Save and Apply Settings</button></div>
</form>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-image-picker]').forEach(picker=>{const input=picker.querySelector('[data-file-input]'),preview=picker.querySelector('[data-preview]'),state=picker.querySelector('em');input.addEventListener('change',()=>{const file=input.files?.[0];if(!file)return;const reader=new FileReader();reader.onload=e=>{preview.src=e.target.result;state.textContent='Selected';picker.classList.add('has-selection')};reader.readAsDataURL(file)})});
document.querySelector('[data-reset-colors]')?.addEventListener('click',()=>{const defaults={primary_color:'#111111',accent_color:'#c8a45d',page_text_color:'#f7f2e7',button_text_color:'#161207'};Object.entries(defaults).forEach(([name,value])=>{document.querySelector(`[name="${name}"]`).value=value})});
(()=>{const root=document.querySelector('[data-slider-settings]');if(!root)return;const type=root.querySelector('[data-slider-type]'),random=root.querySelector('[data-slider-random]');const refresh=()=>root.querySelectorAll('[data-slider-selection]').forEach(field=>field.hidden=random.checked||field.dataset.sliderSelection!==type.value);type.addEventListener('change',refresh);random.addEventListener('change',refresh);refresh()})();
</script>
@endpush
