<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Collection extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('storefront:collections:v1'));
        static::deleted(fn () => Cache::forget('storefront:collections:v1'));
    }
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
