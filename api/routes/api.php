<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\XenditWebhookController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::get('auth/google', [AuthController::class, 'redirect']);
    Route::get('auth/google/callback', [AuthController::class, 'callback']);
    Route::get('config', [ConfigController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('webhooks/xendit', [XenditWebhookController::class, 'handle']);

    // Authenticated (any logged-in user)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::delete('cart/items/{item}', [CartController::class, 'removeItem']);

        Route::post('checkout', [CheckoutController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
    });

    // Admin / Manager only
    Route::middleware(['auth:sanctum', 'role:' . User::ROLE_ADMIN . ',' . User::ROLE_MANAGER])->group(function () {
        Route::post('orders/{order}/mark-paid', [OrderController::class, 'markAsPaid']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('orders', [OrderController::class, 'index']);

        Route::post('products', [ProductController::class, 'store']);
        Route::patch('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);

        Route::post('categories', [CategoryController::class, 'store']);
        Route::patch('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::patch('users/{user}', [UserController::class, 'update']);

        Route::apiResource('coupons', CouponController::class);
    });
});