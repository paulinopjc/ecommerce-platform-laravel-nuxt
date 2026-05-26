<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkoutService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:' . implode(',', Order::PAYMENT_METHODS),
            'coupon_code' => 'nullable|string',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        try {
            $result = $this->checkoutService->checkout($user, $cart, $validated);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'order_id' => $result['order']->id,
                'order_number' => $result['order']->order_number,
                'payment_method' => $result['payment_method'],
                'checkout_url' => $result['checkout_url'],
            ],
        ], 201);
    }
}