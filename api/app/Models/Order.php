<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID            = 'paid';
    public const STATUS_PROCESSING      = 'processing';
    public const STATUS_SHIPPED         = 'shipped';
    public const STATUS_DELIVERED       = 'delivered';
    public const STATUS_CANCELLED       = 'cancelled';
    public const STATUS_REFUNDED        = 'refunded';
    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT, self::STATUS_PAID, self::STATUS_PROCESSING,
        self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED, self::STATUS_REFUNDED,
    ];

    public const SOURCE_WEB    = 'web';
    public const SOURCE_SHOPEE = 'shopee';
    public const SOURCE_LAZADA = 'lazada';
    public const SOURCE_TIKTOK = 'tiktok';
    public const SOURCES = [self::SOURCE_WEB, self::SOURCE_SHOPEE, self::SOURCE_LAZADA, self::SOURCE_TIKTOK];

    public const PAYMENT_COD     = 'cod';
    public const PAYMENT_XENDIT  = 'xendit';
    public const PAYMENT_METHODS = [self::PAYMENT_COD, self::PAYMENT_XENDIT];

    protected $fillable = [
        'customer_id', 'order_number', 'status', 'source',
        'subtotal_cents', 'discount_cents', 'shipping_cents',
        'tax_cents', 'total_cents', 'currency',
        'shipping_name', 'shipping_address', 'billing_address',
        'notes', 'payment_method',
        'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address'  => 'array',
            'paid_at'          => 'datetime',
            'shipped_at'       => 'datetime',
            'delivered_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->max('id') ?? 0;
        return sprintf('ORD-%s-%05d', $year, $last + 1);
    }
}
