<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'is_active'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function markets(): HasMany
    {
        return $this->hasMany(Market::class, 'category', 'slug');
    }
}
