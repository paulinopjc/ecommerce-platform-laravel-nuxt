<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;

class CartService
{
    public function getOrCreateCart(?User $user, ?string $sessionId): Cart
    {
        if ($user) {
            $cart = Cart::where('user_id', $user->id)->first();
            if (!$cart) {
                $cart = Cart::create(['user_id' => $user->id]);
            }
            return $cart;
        }

        if ($sessionId) {
            $cart = Cart::where('session_id', $sessionId)->first();
            if (!$cart) {
                $cart = Cart::create(['session_id' => $sessionId]);
            }
            return $cart;
        }

        return Cart::create(['session_id' => bin2hex(random_bytes(16))]);
    }

    public function addItem(Cart $cart, int $variantId, int $quantity = 1): CartItem
    {
        $existing = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->increment('quantity', $quantity);
            return $existing->fresh();
        }

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        $item->update(['quantity' => $quantity]);
        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function getCartWithTotals(Cart $cart): array
    {
        $items = CartItem::where('cart_id', $cart->id)
            ->with(['productVariant.product.primaryImage'])
            ->get();

        $subtotal = 0;
        $cartItems = [];

        foreach ($items as $item) {
            $variant = $item->productVariant;
            $product = $variant->product;
            $price = $variant->price_cents ?? $product->base_price_cents;
            $lineTotal = $price * $item->quantity;
            $subtotal += $lineTotal;

            $cartItems[] = [
                'id' => $item->id,
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'image_url' => $product->primaryImage?->url,
                'unit_price_cents' => $price,
                'quantity' => $item->quantity,
                'line_total_cents' => $lineTotal,
                'stock_available' => $variant->stock_quantity - $variant->reserved_quantity,
            ];
        }

        return [
            'items' => $cartItems,
            'subtotal_cents' => $subtotal,
            'item_count' => $items->sum('quantity'),
        ];
    }

    public function mergeGuestCart(User $user, string $sessionId): void
    {
        $guestCart = Cart::where('session_id', $sessionId)->first();
        if (!$guestCart) return;

        $userCart = $this->getOrCreateCart($user, null);

        $guestItems = CartItem::where('cart_id', $guestCart->id)->get();
        foreach ($guestItems as $guestItem) {
            $this->addItem($userCart, $guestItem->product_variant_id, $guestItem->quantity);
        }

        $guestCart->delete();
    }
}