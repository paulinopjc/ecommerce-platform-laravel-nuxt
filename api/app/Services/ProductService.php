<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Str;

class ProductService
{
    public function list(array $filters = []): CursorPaginator
    {
        $query = Product::with(['primaryImage', 'defaultVariant', 'category'])
            ->where('is_active', true);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if (!empty($filters['min_price'])) {
            $query->where('base_price_cents', '>=', $filters['min_price'] * 100);
        }

        if (!empty($filters['max_price'])) {
            $query->where('base_price_cents', '<=', $filters['max_price'] * 100);
        }

        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', true);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'price_asc' => $query->orderBy('base_price_cents', 'asc'),
            'price_desc' => $query->orderBy('base_price_cents', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->cursorPaginate($filters['limit'] ?? 20);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::with(['variants', 'images', 'category'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    public function create(array $data): Product
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $existingSlug = Product::where('slug', $data['slug'])->exists();
        if ($existingSlug) {
            $data['slug'] .= '-' . Str::random(4);
        }

        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }
}