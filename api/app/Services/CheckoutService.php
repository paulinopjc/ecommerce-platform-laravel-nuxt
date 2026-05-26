<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Xendit\Xendit;
use Xendit\Invoice;

class CheckoutService
{
    private PricingService $pricing;

    public function __construct(PricingService $pricing)
    {
        $this->pricing = $pricing;
        Xendit::setApiKey(config('services.xendit.secret_key'));
    }

    public function checkout(User $user, Cart $cart, array $data): array
    {
        $paymentMethod = $data['payment_method'] ?? Order::PAYMENT_COD;

        if (!in_array($paymentMethod, Order::PAYMENT_METHODS)) {
            throw new \InvalidArgumentException('Invalid payment method');
        }

        return DB::transaction(function () use ($user, $cart, $data, $paymentMethod) {
            $items = CartItem::where('cart_id', $cart->id)
                ->with(['productVariant.product'])
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            // Check stock and reserve
            $subtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $variant = $item->productVariant;
                $available = $variant->stock_quantity - $variant->reserved_quantity;

                if ($available < $item->quantity) {
                    throw new \Exception("Not enough stock for {$variant->product->name} ({$variant->name})");
                }

                $variant->increment('reserved_quantity', $item->quantity);

                $price = $variant->price_cents ?? $variant->product->base_price_cents;
                $lineTotal = $price * $item->quantity;
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'sku' => $variant->sku ?? $variant->product->sku,
                    'unit_price_cents' => $price,
                    'quantity' => $item->quantity,
                    'subtotal_cents' => $lineTotal,
                ];
            }

            // Apply coupon
            $couponResult = $this->pricing->calculateDiscount(
                $subtotal,
                $data['coupon_code'] ?? null
            );

            if (isset($couponResult['error'])) {
                throw new \Exception($couponResult['error']);
            }

            $discount = $couponResult['discount_cents'];
            $total = $subtotal - $discount;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => Order::STATUS_PENDING_PAYMENT,
                'source' => Order::SOURCE_WEB,
                'payment_method' => $paymentMethod,
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'total_cents' => $total,
                'shipping_name' => $data['shipping_name'] ?? $user->name,
                'shipping_address' => $data['shipping_address'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            // Record coupon usage
            if ($couponResult['coupon']) {
                $couponResult['coupon']->increment('used_count');
                CouponUsage::create([
                    'coupon_id' => $couponResult['coupon']->id,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'discount_cents' => $discount,
                ]);
            }

            // Clear the cart
            CartItem::where('cart_id', $cart->id)->delete();

            if ($paymentMethod === Order::PAYMENT_COD) {
                return $this->checkoutCod($order);
            }

            return $this->checkoutXendit($order, $user);
        });
    }

    private function checkoutCod(Order $order): array
    {
        Payment::create([
            'order_id' => $order->id,
            'payment_method' => Order::PAYMENT_COD,
            'amount_cents' => $order->total_cents,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => Order::STATUS_PENDING_PAYMENT,
            'note' => 'Order placed with Cash on Delivery',
        ]);

        return [
            'order' => $order,
            'payment_method' => Order::PAYMENT_COD,
            'checkout_url' => null,
        ];
    }

    private function checkoutXendit(Order $order, User $user): array
    {
        $amountPhp = $order->total_cents / 100;

        $invoice = Invoice::create([
            'external_id' => "order-{$order->id}",
            'amount' => $amountPhp,
            'payer_email' => $user->email,
            'description' => "Order {$order->order_number}",
            'invoice_duration' => 86400, // 24 hours
            'currency' => 'PHP',
            'success_redirect_url' => config('app.frontend_url') . "/order-confirmation/{$order->id}",
            'failure_redirect_url' => config('app.frontend_url') . '/cart?payment_failed=true',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method' => Order::PAYMENT_XENDIT,
            'xendit_invoice_id' => $invoice['id'],
            'xendit_invoice_url' => $invoice['invoice_url'],
            'amount_cents' => $order->total_cents,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => Order::STATUS_PENDING_PAYMENT,
            'note' => 'Xendit Invoice created, awaiting payment',
        ]);

        return [
            'order' => $order,
            'payment_method' => Order::PAYMENT_XENDIT,
            'checkout_url' => $invoice['invoice_url'],
        ];
    }
}