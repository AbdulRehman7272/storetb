<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Support\StoreSettings;

class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->slug = $product->slug ?: Str::slug($product->name);
            $product->sku = $product->sku ?: 'TB-' . strtoupper(Str::random(8));
            $product->seo_title = $product->seo_title ?: $product->name;
            if ($product->status === 'published' && ! $product->published_at) {
                $product->published_at = now();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    public function primaryMedia()
    {
        return $this->hasOne(ProductMedia::class)->where('is_primary', true)->oldest('position');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class);
    }

    public function price(): float
    {
        return (float) ($this->sale_price ?: $this->regular_price);
    }

    public function imageUrl(): string
    {
        $path = $this->primaryMedia?->path ?: StoreSettings::get('default_product_image', 'admin-assets/img/product/product-1.jpg');
        return str_starts_with($path, 'http') ? $path : asset($path);
    }
}
