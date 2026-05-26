<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED      = 'fixed';
    public const TYPES = [self::TYPE_PERCENTAGE, self::TYPE_FIXED];

    protected $fillable = [
        'code', 
        'type', 
        'value', 
        'min_order_cents',
        'max_uses', 
        'used_count', 
        'starts_at', 
        'expires_at', 
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'starts_at'  => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
