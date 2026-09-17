<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoryDesign = [
            'ladies-suiting' => ['Fashion', 'assets/catalog/category-ladies.webp', 'assets/catalog/hero-ladies.webp', 'assets/catalog/banner-ladies.webp'],
            'gents-suiting' => ['Fashion', 'assets/catalog/category-gents.webp', 'assets/catalog/hero-gents.webp', 'assets/catalog/banner-gents.webp'],
            'bedsheets' => ['Home', 'assets/catalog/category-bedsheets.webp', 'assets/catalog/hero-bedsheets.webp', 'assets/catalog/banner-bedsheets.webp'],
            'bags' => ['Accessories', 'assets/catalog/category-accessories.webp', 'assets/catalog/hero-accessories.webp', 'assets/catalog/banner-accessories.webp'],
            'watches' => ['Accessories', 'assets/catalog/category-accessories.webp', 'assets/catalog/hero-accessories.webp', 'assets/catalog/banner-accessories.webp'],
            'shoes' => ['Accessories', 'assets/catalog/category-accessories.webp', 'assets/catalog/hero-accessories.webp', 'assets/catalog/banner-accessories.webp'],
        ];
        foreach ($categoryDesign as $slug => [$group, $image, $hero, $banner]) DB::table('categories')->where('slug', $slug)->update(['group_name' => $group, 'main_image' => $image, 'hero_image' => $hero, 'banner' => $banner]);

        $collections = [
            ['name' => 'Luxury Collection', 'slug' => 'luxury-collection', 'description' => 'Elevated pieces with refined finishing.', 'image' => 'assets/catalog/banner-ladies.webp', 'display_order' => 1],
            ['name' => 'New Arrivals', 'slug' => 'new-arrivals', 'description' => 'Fresh drops across the TBrand store.', 'image' => 'assets/catalog/banner-gents.webp', 'display_order' => 2],
            ['name' => 'Seasonal Edit', 'slug' => 'seasonal-edit', 'description' => 'Limited seasonal picks for events and gifting.', 'image' => 'assets/catalog/banner-bedsheets.webp', 'display_order' => 3],
            ['name' => 'Gold Signature', 'slug' => 'gold-signature', 'description' => 'Black-and-gold statement pieces from every category.', 'image' => 'assets/catalog/banner-accessories.webp', 'display_order' => 4],
        ];
        foreach ($collections as $collection) DB::table('collections')->updateOrInsert(['slug' => $collection['slug']], $collection + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $newArrivals = DB::table('collections')->where('slug', 'new-arrivals')->value('id');
        foreach (DB::table('products')->where('status', 'published')->orderByDesc('created_at')->limit(12)->pluck('id') as $productId) DB::table('collection_product')->insertOrIgnore(['collection_id' => $newArrivals, 'product_id' => $productId]);
    }

    public function down(): void {}
};
