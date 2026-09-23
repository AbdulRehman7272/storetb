<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $images = ['ladies-suiting' => 'assets/catalog/product-noor-luxury-embroidered-3-piece.webp', 'gents-suiting' => 'assets/catalog/product-classic-shalwar-suit.webp', 'bedsheets' => 'assets/catalog/product-royal-bedsheet-set.webp', 'watches' => 'assets/catalog/product-gold-dial-watch.webp', 'shoes' => 'assets/catalog/product-leather-formal-shoes.webp', 'bags' => 'assets/catalog/product-signature-accessory-box.webp'];
        $categories = DB::table('categories')->pluck('slug', 'id');
        foreach (DB::table('products')->get() as $product) {
            $path = $images[$categories[$product->category_id] ?? 'bags'];
            DB::table('product_media')->where('product_id', $product->id)->where('path', 'like', 'admin-assets/%')->update(['path' => $path, 'alt_text' => $product->name, 'updated_at' => now()]);
        }
    }
    public function down(): void {}
};
