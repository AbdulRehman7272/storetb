<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $images = ['ladies-suiting' => ['assets/catalog/product-noor-luxury-embroidered-3-piece.webp', 'assets/catalog/product-saira-black-gold-lawn-suit.webp'], 'gents-suiting' => ['assets/catalog/product-classic-shalwar-suit.webp', 'assets/catalog/product-premium-kurta.webp'], 'bedsheets' => ['assets/catalog/product-royal-bedsheet-set.webp'], 'watches' => ['assets/catalog/product-gold-dial-watch.webp'], 'shoes' => ['assets/catalog/product-leather-formal-shoes.webp'], 'bags' => ['assets/catalog/product-signature-accessory-box.webp']];
        $categories = DB::table('categories')->pluck('slug', 'id');
        foreach (DB::table('products')->orderBy('id')->get() as $product) {
            if (DB::table('product_media')->where('product_id', $product->id)->exists()) continue;
            $choices = $images[$categories[$product->category_id] ?? 'bags'] ?? $images['bags'];
            DB::table('product_media')->insert(['product_id' => $product->id, 'path' => $choices[$product->id % count($choices)], 'type' => 'image', 'alt_text' => $product->name, 'position' => 0, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void {}
};
