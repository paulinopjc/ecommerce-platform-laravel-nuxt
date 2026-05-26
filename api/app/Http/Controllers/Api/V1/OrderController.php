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
}