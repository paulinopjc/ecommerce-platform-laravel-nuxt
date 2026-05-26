<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;

class OrderService
{
    private const VALID_TRANSITIONS = [
        Order::STATUS_PENDING_PAYMENT => [Order::STATUS_CANCELLED],
        Order::STATUS_PAID            => [Order::STATUS_PROCESSING, Order::STATUS_CANCELLED],
        Order::STATUS_PROCESSING      => [Order::STATUS_SHIPPED],
        Order::STATUS_SHIPPED         => [Order::STATUS_DELIVERED],
    ];

    public function updateStatus(Order $order, string $newStatus, User $changedBy, ?string $note = null): Order
    {
        $allowed = self::VALID_TRANSITIONS[$order->status] ?? [];
        if (!in_array($newStatus, $allowed)) {
            throw new \Exception("Cannot transition from {$order->status} to {$newStatus}");
        }

        $oldStatus = $order->status;

        $updates = ['status' => $newStatus];
        match ($newStatus) {
            Order::STATUS_SHIPPED   => $updates['shipped_at'] = now(),
            Order::STATUS_DELIVERED => $updates['delivered_at'] = now(),
            Order::STATUS_CANCELLED => $updates['cancelled_at'] = now(),
            default => null,
        };

        $order->update($updates);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $changedBy->id,
            'note' => $note,
        ]);

        return $order->fresh();
    }

    public function listForUser(User $user, array $filters = [])
    {
        $query = Order::where('user_id', $user->id)
            ->with(['items'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(20);
    }

    public function listAll(array $filters = [])
    {
        $query = Order::with(['user', 'items'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        return $query->paginate(20);
    }
}