<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'marketplace_connection_id',
        'direction',
        'entity_type',
        'entity_id',
        'status',
        'error_message',
        'request_body',
        'response_body',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'request_body'  => 'array',
            'response_body' => 'array',
            'created_at'    => 'datetime',
        ];
    }

    public function connection()
    {
        return $this->belongsTo(MarketplaceConnection::class, 'marketplace_connection_id');
    }
}
