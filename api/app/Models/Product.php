<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'base_price_cents',
        'compare_at_price_cents', 'sku', 'barcode', 'weight_grams',
        'is_active', 'is_featured', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active'               => 'boolean',
            'is_featured'             => 'boolean',
            'base_price_cents'        => 'integer',
            'compare_at_price_cents'  => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)->orderBy('position');
    }
}
