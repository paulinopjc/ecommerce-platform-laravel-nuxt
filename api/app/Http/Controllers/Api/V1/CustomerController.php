<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json(['data' => $customer]);
    }

    public function orders(Request $request): JsonResponse
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $orders = $customer->orders()
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->paginate(20);

        return response()->json($orders);
    }

    public function addresses(Request $request): JsonResponse
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json(['data' => $customer->addresses()->get()]);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $customer = $request->user();
        if (!$customer instanceof Customer) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'label'       => 'nullable|string|max:50',
            'line_1'      => 'required|string|max:255',
            'line_2'      => 'nullable|string|max:255',
            'city'        => 'required|string|max:100',
            'province'    => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country'     => 'nullable|string|size:2',
            'is_default'  => 'boolean',
        ]);

        $address = Address::create(array_merge($validated, ['customer_id' => $customer->id]));
        return response()->json(['data' => $address], 201);
    }
}
