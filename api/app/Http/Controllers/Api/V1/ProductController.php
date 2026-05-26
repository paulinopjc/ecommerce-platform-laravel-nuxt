<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list($request->all());
        return response()->json($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->productService->findBySlug($slug);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        return response()->json(['data' => $product]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'base_price_cents' => 'required|integer|min:0',
            'compare_at_price_cents' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $product = $this->productService->create($validated);
        return response()->json(['data' => $product], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = \App\Models\Product::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'base_price_cents' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $product = $this->productService->update($product, $validated);
        return response()->json(['data' => $product]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = \App\Models\Product::findOrFail($id);
        $product->delete(); // Soft delete
        return response()->json(null, 204);
    }
}