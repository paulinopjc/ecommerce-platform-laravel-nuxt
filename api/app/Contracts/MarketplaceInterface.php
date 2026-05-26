<?php

namespace App\Contracts;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface MarketplaceInterface
{
    public function authenticate(): void;
    public function pushProduct(Product $product): array;
    public function pullOrders(Carbon $since): Collection;
    public function updateOrderStatus(string $marketplaceOrderId, string $status): bool;
    public function syncInventory(Product $product, int $quantity): bool;
}