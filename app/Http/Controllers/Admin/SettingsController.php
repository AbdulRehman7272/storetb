<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\StoreSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => Setting::query()->orderBy('group')->orderBy('key')->get()->pluck('value', 'key'),
            'categories' => Category::query()->where('is_active', true)->orderBy('display_order')->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'published')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $values = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'general_email' => ['nullable', 'email', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'show_footer_contact' => ['nullable', 'boolean'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'page_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'homepage_hero_slogan' => ['required', 'string', 'max:120'],
            'homepage_hero_title' => ['required', 'string', 'max:160'],
            'homepage_hero_description' => ['required', 'string', 'max:500'],
            'show_super_store' => ['nullable', 'boolean'],
            'homepage_category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'slider_content_type' => ['required', 'in:categories,products'],
            'slider_random' => ['nullable', 'boolean'],
            'slider_category_ids' => ['nullable', 'array'],
            'slider_category_ids.*' => ['integer', 'exists:categories,id'],
            'slider_product_ids' => ['nullable', 'array'],
            'slider_product_ids.*' => ['integer', 'exists:products,id'],
            'currency' => ['required', 'string', 'max:10'],
            'shipping_charge' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
            'advance_payment_free_shipping' => ['nullable', 'boolean'],
            'advance_payment_discount' => ['required', 'numeric', 'min:0'],
            'footer_credit' => ['nullable', 'string', 'max:255'],
            'footer_credit_url' => ['nullable', 'url', 'max:500'],
            'main_logo_upload' => ['nullable', 'image', 'max:4096'],
            'mobile_logo_upload' => ['nullable', 'image', 'max:4096'],
            'favicon_upload' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico', 'max:2048'],
            'default_product_image_upload' => ['nullable', 'image', 'max:4096'],
            'homepage_hero_image_upload' => ['nullable', 'image', 'max:8192'],
        ]);

        $values['show_footer_contact'] = $request->boolean('show_footer_contact');
        $values['show_super_store'] = $request->boolean('show_super_store');
        $values['slider_random'] = $request->boolean('slider_random');
        $values['advance_payment_free_shipping'] = $request->boolean('advance_payment_free_shipping');
        $values['slider_category_ids'] = $values['slider_category_ids'] ?? [];
        $values['slider_product_ids'] = $values['slider_product_ids'] ?? [];
        $groups = ['store_name' => 'branding', 'whatsapp' => 'contact', 'general_email' => 'contact', 'support_email' => 'contact', 'address' => 'contact', 'show_footer_contact' => 'contact', 'primary_color' => 'branding', 'accent_color' => 'branding', 'page_text_color' => 'branding', 'button_text_color' => 'branding', 'homepage_hero_slogan' => 'homepage', 'homepage_hero_title' => 'homepage', 'homepage_hero_description' => 'homepage', 'show_super_store' => 'homepage', 'homepage_category_slug' => 'homepage', 'slider_content_type' => 'slider', 'slider_random' => 'slider', 'slider_category_ids' => 'slider', 'slider_product_ids' => 'slider', 'currency' => 'store', 'shipping_charge' => 'shipping', 'free_shipping_threshold' => 'shipping', 'advance_payment_free_shipping' => 'shipping', 'advance_payment_discount' => 'shipping', 'footer_credit' => 'branding', 'footer_credit_url' => 'branding'];
        foreach ($groups as $key => $group) {
            $type = in_array($key, ['show_footer_contact', 'show_super_store', 'slider_random', 'advance_payment_free_shipping']) ? 'boolean' : (in_array($key, ['slider_category_ids', 'slider_product_ids']) ? 'json' : (in_array($key, ['shipping_charge', 'free_shipping_threshold', 'advance_payment_discount']) ? 'number' : 'text'));
            StoreSettings::put($key, $values[$key] ?? null, $group, $type);
        }

        foreach (['main_logo', 'mobile_logo', 'favicon', 'default_product_image', 'homepage_hero_image'] as $key) {
            $upload = $key.'_upload';
            if ($request->hasFile($upload)) {
                $group = $key === 'default_product_image' ? 'catalog' : ($key === 'homepage_hero_image' ? 'homepage' : 'branding');
                StoreSettings::put($key, 'storage/'.$request->file($upload)->store('settings', 'public'), $group, 'image');
            }
        }

        return back()->with('status', 'Settings updated and active on the storefront.');
    }
}
