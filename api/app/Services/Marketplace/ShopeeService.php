<?php

namespace App\Services\Marketplace;

use App\Contracts\MarketplaceInterface;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShopeeService implements MarketplaceInterface
{
    public function __construct(private array $credentials)
    {
    }

    public function authenticate(): void
    {
        // In production: exchange API key for access token via Shopee OAuth
        // For the tutorial: this is a stub
    }

    public function pushProduct(Product $product): array
    {
        // In production: POST to Shopee's product API
        return [
            'marketplace_product_id' => 'shopee_' . $product->id,
            'marketplace_sku' => $product->sku,
        ];
    }

    public function pullOrders(Carbon $since): Collection
    {
        // In production: GET from Shopee's order API
        return collect([]);
    }

    public function updateOrderStatus(string $marketplaceOrderId, string $status): bool
    {
        return true;
    }

    public function syncInventory(Product $product, int $quantity): bool
    {
        return true;
    }
}