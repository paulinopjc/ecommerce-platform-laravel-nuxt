<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProductMapping extends Model
{
    protected $fillable = [
        'marketplace_connection_id', 
        'product_id',
        'marketplace_product_id', 
        'marketplace_sku',
        'last_synced_at', 
        'sync_status',
    ];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    public function connection()
    {
        return $this->belongsTo(MarketplaceConnection::class, 'marketplace_connection_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
