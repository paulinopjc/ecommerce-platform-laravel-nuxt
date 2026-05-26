<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\XenditWebhookController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('auth/google', [AuthController::class, 'redirect']);
    Route::get('auth/google/callback', [AuthController::class, 'callback']);
    Route::get('config', [ConfigController::class, 'index']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);

    Route::post('v1/webhooks/xendit', [XenditWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::post('v1/checkout', [CheckoutController::class, 'store']);
        Route::post('v1/orders/{order}/mark-paid', [OrderController::class, 'markAsPaid']);
    });

    Route::middleware(['auth:sanctum', 'role:' . User::ROLE_ADMIN . ',' . User::ROLE_MANAGER])->group(function () {
        Route::post('products', [ProductController::class, 'store']);
        Route::patch('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
    });
});