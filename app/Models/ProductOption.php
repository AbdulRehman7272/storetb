<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $guarded = [];

    public function values()
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
