<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_REFUNDED  = 'refunded';
    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_SUCCEEDED, self::STATUS_FAILED,
        self::STATUS_EXPIRED, self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'order_id', 
        'payment_method', 
        'xendit_invoice_id', 
        'xendit_invoice_url',
        'amount_cents', 
        'currency', 
        'status', 
        'failure_reason',
        'paid_at',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
