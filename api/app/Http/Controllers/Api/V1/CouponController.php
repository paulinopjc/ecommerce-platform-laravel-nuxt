<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Coupon::orderBy('created_at', 'desc')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'            => 'required|string|unique:coupons,code',
            'type'            => 'required|in:' . implode(',', Coupon::TYPES),
            'discount_value'  => 'required|integer|min:1',
            'min_order_cents' => 'nullable|integer|min:0',
            'max_uses'        => 'nullable|integer|min:1',
            'expires_at'      => 'nullable|date|after:today',
            'is_active'       => 'boolean',
        ]);

        $coupon = Coupon::create($validated);
        return response()->json(['data' => $coupon], 201);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'code'            => 'sometimes|string|unique:coupons,code,' . $coupon->id,
            'type'            => 'sometimes|in:' . implode(',', Coupon::TYPES),
            'discount_value'  => 'sometimes|integer|min:1',
            'min_order_cents' => 'nullable|integer|min:0',
            'max_uses'        => 'nullable|integer|min:1',
            'expires_at'      => 'nullable|date',
            'is_active'       => 'boolean',
        ]);

        $coupon->update($validated);
        return response()->json(['data' => $coupon->fresh()]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        return response()->json(null, 204);
    }
}