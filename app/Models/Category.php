<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name_bn', 'name_en', 'slug', 'icon', 'is_active', 'sort_order'];
    protected function casts(): array { return ['is_active' => 'boolean', 'sort_order' => 'integer']; }
    public function subcategories(): HasMany { return $this->hasMany(Subcategory::class)->orderBy('sort_order'); }
}
