<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->orderBy('position')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|unique:categories,slug',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|url',
            'position'    => 'integer|min:0',
            'is_active'   => 'boolean',
            'parent_id'   => 'nullable|exists:categories,id',
        ]);

        $category = Category::create($validated);
        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'slug'        => 'sometimes|string|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'position'    => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $category->update($validated);
        return response()->json(['data' => $category->fresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(null, 204);
    }
}