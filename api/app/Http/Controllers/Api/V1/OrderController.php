<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'items'])->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        // Customers can only view their own orders
        if (!$request->user()->isManager() && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $order->load(['items', 'payments', 'statusHistory'])]);
    }

    public function markAsPaid(Request $request, Order $order): JsonResponse
    {
        if ($order->payment_method !== Order::PAYMENT_COD) {
            return response()->json(['message' => 'Only COD orders can be marked paid manually'], 422);
        }

        if ($order->status === Order::STATUS_PAID) {
            return response()->json(['message' => 'Order already paid'], 422);
        }

        // Deduct stock
        foreach ($order->items as $item) {
            if ($item->productVariant) {
                $item->productVariant->decrement('stock_quantity', $item->quantity);
                $item->productVariant->decrement('reserved_quantity', $item->quantity);
            }
        }

        Payment::where('order_id', $order->id)->update([
            'status' => Payment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);

        $order->update([
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => Order::STATUS_PENDING_PAYMENT,
            'to_status' => Order::STATUS_PAID,
            'note' => 'Cash collected, marked paid by admin',
            'changed_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $order->fresh()]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Order::STATUSES),
            'note'   => 'nullable|string',
        ]);

        $orderService = app(\App\Services\OrderService::class);

        try {
            $updated = $orderService->updateStatus($order, $validated['status'], $request->user(), $validated['note'] ?? null);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $updated]);
    }
}