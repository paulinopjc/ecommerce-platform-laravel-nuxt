<?php

namespace App\Services;

use App\Models\Coupon;

class PricingService
{
    public function calculateDiscount(int $subtotalCents, ?string $couponCode): array
    {
        if (!$couponCode) {
            return ['discount_cents' => 0, 'coupon' => null];
        }

        $coupon = Coupon::where('code', strtoupper($couponCode))
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return ['discount_cents' => 0, 'coupon' => null, 'error' => 'Invalid coupon code'];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return ['discount_cents' => 0, 'coupon' => null, 'error' => 'Coupon has expired'];
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return ['discount_cents' => 0, 'coupon' => null, 'error' => 'Coupon is not yet active'];
        }

        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            return ['discount_cents' => 0, 'coupon' => null, 'error' => 'Coupon usage limit reached'];
        }

        if ($subtotalCents < $coupon->min_order_cents) {
            $minOrder = number_format($coupon->min_order_cents / 100, 2);
            return ['discount_cents' => 0, 'coupon' => null, 'error' => "Minimum order amount is {$minOrder}"];
        }

        $discount = match ($coupon->type) {
            Coupon::TYPE_PERCENTAGE => (int) round($subtotalCents * ($coupon->value / 100)),
            Coupon::TYPE_FIXED      => min($coupon->value, $subtotalCents),
            default => 0,
        };

        return ['discount_cents' => $discount, 'coupon' => $coupon];
    }
}