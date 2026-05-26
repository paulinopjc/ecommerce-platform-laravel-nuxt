<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class XenditWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Verify Xendit callback token
        $callbackToken = $request->header('X-CALLBACK-TOKEN');
        if ($callbackToken !== config('services.xendit.webhook_token')) {
            return response('Unauthorized', 401);
        }

        $payload = $request->all();
        $eventId = $payload['id'] ?? null;

        if (!$eventId) {
            return response('Missing event ID', 400);
        }

        // Idempotency check
        $existing = WebhookEvent::where('event_id', $eventId)->first();
        if ($existing && $existing->processed_at) {
            return response('Already processed', 200);
        }

        $webhookEvent = WebhookEvent::updateOrCreate(
            ['event_id' => $eventId],
            [
                'source' => WebhookEvent::SOURCE_XENDIT,
                'type' => $payload['status'] ?? 'unknown',
                'payload' => $payload,
            ]
        );

        $status = $payload['status'] ?? null;

        match ($status) {
            'PAID', 'SETTLED' => $this->handlePaid($payload),
            'EXPIRED' => $this->handleExpired($payload),
            default => null,
        };

        $webhookEvent->update(['processed_at' => now()]);

        return response('OK', 200);
    }

    private function handlePaid(array $payload): void
    {
        $invoiceId = $payload['id'];

        $payment = Payment::where('xendit_invoice_id', $invoiceId)->first();
        if (!$payment) return;

        $order = Order::find($payment->order_id);
        if (!$order || $order->status !== Order::STATUS_PENDING_PAYMENT) return;

        // Deduct stock
        foreach ($order->items as $item) {
            if ($item->productVariant) {
                $item->productVariant->decrement('stock_quantity', $item->quantity);
                $item->productVariant->decrement('reserved_quantity', $item->quantity);
            }
        }

        $payment->update([
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
            'note' => 'Payment confirmed via Xendit (' . ($payload['payment_method'] ?? 'online') . ')',
        ]);
    }

    private function handleExpired(array $payload): void
    {
        $invoiceId = $payload['id'];

        $payment = Payment::where('xendit_invoice_id', $invoiceId)->first();
        if (!$payment) return;

        $order = Order::find($payment->order_id);
        if (!$order || $order->status !== Order::STATUS_PENDING_PAYMENT) return;

        // Release reserved stock
        foreach ($order->items as $item) {
            if ($item->productVariant) {
                $item->productVariant->decrement('reserved_quantity', $item->quantity);
            }
        }

        $payment->update(['status' => Payment::STATUS_EXPIRED]);
        $order->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => Order::STATUS_PENDING_PAYMENT,
            'to_status' => Order::STATUS_CANCELLED,
            'note' => 'Xendit Invoice expired without payment',
        ]);
    }
}