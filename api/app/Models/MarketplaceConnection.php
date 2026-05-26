<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceConnection extends Model
{
    protected $fillable = [
        'platform', 
        'credentials', 
        'is_active', 
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'credentials'    => 'array',
            'is_active'      => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function productMappings()
    {
        return $this->hasMany(MarketplaceProductMapping::class);
    }

    public function orders()
    {
        return $this->hasMany(MarketplaceOrder::class);
    }
}
