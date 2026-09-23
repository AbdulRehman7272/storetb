<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\PaymentAccount;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\Setting;
use App\Models\Vendor;
use App\Support\StoreSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            'view-dashboard', 'manage-products', 'manage-categories', 'manage-orders',
            'manage-payments', 'manage-customers', 'manage-reports', 'manage-settings',
            'manage-staff', 'manage-pages', 'manage-inventory',
        ])->mapWithKeys(fn ($slug) => [$slug => Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::headline($slug)]
        )]);

        foreach ([
            'owner' => 'Owner',
            'administrator' => 'Administrator',
            'manager' => 'Manager',
            'product-manager' => 'Product Manager',
            'order-manager' => 'Order Manager',
            'customer-support' => 'Customer Support',
            'accountant' => 'Accountant',
            'reports-only' => 'Reports Only',
        ] as $slug => $name) {
            $role = Role::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
            $role->permissions()->sync($slug === 'reports-only'
                ? $permissions->only(['view-dashboard', 'manage-reports'])->pluck('id')
                : $permissions->pluck('id'));
        }

        (new AdminUserSeeder())->run();

        foreach ([
            ['store_name', 'TBrand', 'branding'],
            ['whatsapp', '03076690892', 'contact'],
            ['general_email', 'info@tbrand.pk', 'contact'],
            ['support_email', 'support@tbrand.pk', 'contact'],
            ['address', 'Pakistan', 'contact'],
            ['seo_title', 'TBrand | Premium Pakistani Super Store', 'seo'],
            ['seo_description', 'Shop premium fashion and lifestyle products online in Pakistan.', 'seo'],
            ['show_footer_contact', true, 'contact'],
            ['footer_credit', 'Powered by BoostupLive', 'branding'],
            ['footer_credit_url', 'https://boostuplive.com', 'branding'],
            ['main_logo', 'brand/tbrand/01_Main_Horizontal_Black.png', 'branding'],
            ['mobile_logo', 'brand/tbrand/03_Compact_Black.png', 'branding'],
            ['favicon', 'brand/tbrand/favicon.ico', 'branding'],
            ['default_product_image', 'admin-assets/img/product/product-1.jpg', 'catalog'],
            ['currency', 'PKR', 'store'],
            ['low_stock_threshold', 5, 'inventory'],
            ['shipping_charge', 250, 'shipping'],
            ['free_shipping_threshold', 5000, 'shipping'],
            ['primary_color', '#111111', 'branding'],
            ['accent_color', '#c8a45d', 'branding'],
            ['page_text_color', '#f7f2e7', 'branding'],
            ['button_text_color', '#161207', 'branding'],
            ['homepage_hero_slogan', 'Style · Quality · Trust', 'homepage'],
            ['homepage_hero_title', 'Premium TBrand Store', 'homepage'],
            ['homepage_hero_description', 'Explore quality products selected for your store.', 'homepage'],
            ['show_super_store', true, 'homepage'],
            ['homepage_category_slug', null, 'homepage'],
            ['slider_content_type', 'categories', 'slider'],
            ['slider_random', false, 'slider'],
            ['slider_category_ids', [], 'slider'],
            ['slider_product_ids', [], 'slider'],
        ] as [$key, $value, $group]) {
            if (! Setting::query()->where('key', $key)->exists()) {
                StoreSettings::put($key, $value, $group, is_numeric($value) ? 'number' : 'text', true);
            }
        }

        // Demo records are opt-in so production seeding never creates sample products or placeholder payment details.
        if (! filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $brand = Brand::query()->firstOrCreate(['slug' => 'tbrand'], ['name' => 'TBrand', 'is_active' => true]);
        $vendor = Vendor::query()->firstOrCreate(['slug' => 'tbrand-main'], ['name' => 'TBrand Main Store', 'is_active' => true]);

        $categories = collect(['Ladies Suiting', 'Bedsheets', 'Bags', 'Watches', 'Gents Suiting', 'Shoes'])
            ->values()
            ->map(function ($name, $index) {
                return Category::query()->firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'description' => 'Curated ' . strtolower($name) . ' collection for TBrand customers.',
                        'display_order' => $index + 1,
                        'is_active' => true,
                        'show_in_navbar' => true,
                        'is_featured' => true,
                        'seo_title' => $name . ' - TBrand',
                    ]
                );
            });

        $colorOption = ProductOption::query()->firstOrCreate(['slug' => 'colour'], ['name' => 'Colour']);
        $sizeOption = ProductOption::query()->firstOrCreate(['slug' => 'size'], ['name' => 'Size']);
        $colors = collect([
            ['Black', '#111111'], ['Gold', '#c8a45d'], ['Maroon', '#7f1d1d'], ['Navy', '#172554'],
        ])->map(fn ($item) => ProductOptionValue::query()->firstOrCreate(
            ['product_option_id' => $colorOption->id, 'slug' => Str::slug($item[0])],
            ['value' => $item[0], 'swatch' => $item[1]]
        ));
        $sizes = collect(['S', 'M', 'L', 'XL'])->map(fn ($size) => ProductOptionValue::query()->firstOrCreate(
            ['product_option_id' => $sizeOption->id, 'slug' => Str::slug($size)],
            ['value' => $size]
        ));

        foreach ([
            ['Embroidered Lawn Suit', 'Ladies Suiting', 3490, 2990, true],
            ['Premium Cotton Bedsheet Set', 'Bedsheets', 2590, null, false],
            ['Everyday Shoulder Bag', 'Bags', 1890, 1590, true],
            ['Classic Minimal Watch', 'Watches', 3990, 3490, false],
            ['Wash & Wear Gents Suit', 'Gents Suiting', 4290, null, true],
            ['Comfort Walk Sneakers', 'Shoes', 2990, 2690, true],
        ] as $index => [$name, $categoryName, $price, $sale, $withVariants]) {
            $category = $categories->firstWhere('name', $categoryName);
            $product = Product::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'vendor_id' => $vendor->id,
                    'name' => $name,
                    'sku' => 'TB-DEMO-' . ($index + 1),
                    'description' => 'A polished TBrand demo product with mobile-friendly presentation, stock controls and checkout support.',
                    'regular_price' => $price,
                    'sale_price' => $sale,
                    'cost_price' => round($price * 0.55),
                    'stock' => 20 + ($index * 5),
                    'status' => 'published',
                    'is_featured' => $index < 4,
                    'published_at' => now()->subDays($index),
                    'seo_title' => $name . ' - TBrand',
                    'seo_description' => 'Shop ' . $name . ' at TBrand.',
                ]
            );

            ProductMedia::query()->updateOrCreate(
                ['product_id' => $product->id, 'position' => 0],
                [
                    'path' => 'admin-assets/img/product/product-' . (($index % 6) + 1) . '.jpg',
                    'type' => 'image',
                    'alt_text' => $name,
                    'is_primary' => true,
                ]
            );

            if ($withVariants) {
                foreach ($colors->take(2) as $color) {
                    foreach ($sizes->take(3) as $size) {
                        $variant = ProductVariant::query()->updateOrCreate(
                            ['sku' => $product->sku . '-' . strtoupper($color->slug) . '-' . strtoupper($size->slug)],
                            [
                                'product_id' => $product->id,
                                'name' => $color->value . ' / ' . $size->value,
                                'regular_price' => $price,
                                'sale_price' => $sale,
                                'cost_price' => round($price * 0.55),
                                'stock' => 5,
                                'image' => 'admin-assets/img/product/product-' . (($index % 6) + 1) . '.jpg',
                                'is_enabled' => true,
                            ]
                        );
                        $variant->optionValues()->syncWithoutDetaching([$color->id, $size->id]);
                    }
                }
            }
        }

        ShippingMethod::query()->firstOrCreate(['name' => 'Standard Delivery'], [
            'charge' => 250,
            'free_threshold' => 5000,
            'is_active' => true,
        ]);

        PaymentAccount::query()->updateOrCreate(['name' => 'TBrand Bank Transfer'], [
            'type' => 'bank',
            'account_title' => 'TBrand',
            'account_number' => '1234567890',
            'iban' => 'PK00TBRA0000001234567890',
            'branch_name' => 'Main Branch',
            'branch_code' => '0001',
            'instructions' => 'Upload your payment screenshot after sending the transfer.',
            'display_order' => 1,
            'is_active' => true,
        ]);

        foreach ([
            'about' => ['About', 'TBrand is a configurable e-commerce installation for fashion and lifestyle retail.'],
            'contact' => ['Contact', 'WhatsApp: 03076690892. Email: info@tbrand.pk.'],
            'faq' => ['FAQ', 'Common questions about ordering, delivery and returns.'],
            'shipping-policy' => ['Shipping Policy', 'Orders are shipped through available courier services.'],
            'return-exchange-policy' => ['Return/Exchange Policy', 'Return and exchange requests are reviewed according to order condition and timeline.'],
            'privacy-policy' => ['Privacy Policy', 'Customer information is used only to process and support orders.'],
            'terms-and-conditions' => ['Terms and Conditions', 'By placing an order you agree to TBrand store terms.'],
        ] as $slug => [$title, $body]) {
            Page::query()->updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'body' => '<p>' . e($body) . '</p>',
                'is_published' => true,
                'seo_title' => $title . ' - TBrand',
            ]);
        }

        HomepageSection::query()->updateOrCreate(['type' => 'hero'], [
            'title' => 'TBrand New Season',
            'payload' => ['subtitle' => 'Premium everyday fashion, ready to ship.', 'button' => 'Shop New Arrivals'],
            'display_order' => 1,
            'is_enabled' => true,
        ]);
        HomepageSection::query()->updateOrCreate(['type' => 'featured_categories'], [
            'title' => 'Featured Categories',
            'payload' => ['category_slugs' => $categories->pluck('slug')->values()->all()],
            'display_order' => 2,
            'is_enabled' => true,
        ]);
    }
}
