<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    public const SOURCE_XENDIT = 'xendit';
    public const SOURCE_SHOPEE = 'shopee';
    public const SOURCE_LAZADA = 'lazada';
    public const SOURCES = [self::SOURCE_XENDIT, self::SOURCE_SHOPEE, self::SOURCE_LAZADA];

    protected $fillable = [
        'source', 'event_id', 'type', 'payload', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
