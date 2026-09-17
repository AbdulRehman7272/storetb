<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }
}
