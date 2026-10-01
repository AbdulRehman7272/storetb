<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('storefront:categories:v1'));
        static::deleted(fn () => Cache::forget('storefront:categories:v1'));
    }
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_navbar' => 'boolean',
            'is_featured' => 'boolean',
            'margin_value' => 'decimal:2',
            'discount_value' => 'decimal:2',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
