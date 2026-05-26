<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceOrder extends Model
{
    protected $fillable = [
        'marketplace_connection_id', 
        'order_id',
        'marketplace_order_id', 
        'raw_data', 
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data'   => 'array',
            'synced_at'  => 'datetime',
        ];
    }

    public function connection()
    {
        return $this->belongsTo(MarketplaceConnection::class, 'marketplace_connection_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
