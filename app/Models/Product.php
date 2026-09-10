<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'subcategory_id','name_bn','name_en','slug','sku','price','compare_price','short_description','description_html',
        'stock_qty','is_active','is_featured','sort_order','meta_title','meta_description','main_image_path','video_path','video_url'
    ];
    protected function casts(): array { return ['price'=>'decimal:2','compare_price'=>'decimal:2','stock_qty'=>'integer','is_active'=>'boolean','is_featured'=>'boolean','sort_order'=>'integer']; }
    public function subcategory(): BelongsTo { return $this->belongsTo(Subcategory::class); }
    public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
}
