<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        return response()->json(['data' => $cart->load('items')]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1|max:99',
        ]);

        $variant = ProductVariant::findOrFail($validated['variant_id']);
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $this->cartService->addItem($cart, $variant, $validated['quantity']);

        return response()->json(['data' => $cart->fresh()->load('items')]);
    }

    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        $item->delete();
        return response()->json(null, 204);
    }
}