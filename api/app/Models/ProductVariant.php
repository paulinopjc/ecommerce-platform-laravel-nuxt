<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 
        'name', 
        'sku', 
        'price_cents',
        'stock_quantity', 
        'reserved_quantity',
        'weight_grams', 
        'position',
        'option_1_name', 
        'option_1_value', 
        'option_2_name', 
        'option_2_value', 
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'              => 'boolean',
            'price_cents'            => 'integer',
            'stock_quantity'         => 'integer',
            'reserved_quantity'      => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
