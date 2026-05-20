<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BasketController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TraderDashboardController;
use App\Http\Controllers\Api\TraderProductController;
use App\Http\Controllers\PickupRfidController;
use Illuminate\Support\Facades\Route;

// Public auth endpoints
Route::prefix('auth')->group(function (): void {
    Route::post('/register/customer', [AuthController::class, 'registerCustomer']);
    Route::post('/register/trader', [AuthController::class, 'registerTrader']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify/{token}', [AuthController::class, 'verifyEmail']);
});

// Catalog endpoints (public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{productId}', [ProductController::class, 'show']);
Route::post('/pickup-rfid/confirm', [PickupRfidController::class, 'confirm']);

// Authenticated user endpoints
Route::middleware(['auth'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Customer routes
    Route::middleware(['role:customer'])->group(function (): void {
        Route::get('/basket', [BasketController::class, 'show']);
        Route::post('/basket/items', [BasketController::class, 'addItem']);
        Route::put('/basket/items/{basketItemId}', [BasketController::class, 'updateItem']);
        Route::delete('/basket/items/{basketItemId}', [BasketController::class, 'removeItem']);

        Route::post('/checkout', [CheckoutController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::patch('/orders/{orderId}/cancel', [OrderController::class, 'cancel']);
        Route::post('/reviews', [ReviewController::class, 'store']);
    });

    // Trader routes
    Route::middleware(['role:trader'])->prefix('trader')->group(function (): void {
        Route::get('/dashboard/summary', [TraderDashboardController::class, 'summary']);
        Route::post('/products', [TraderProductController::class, 'store']);
        Route::put('/products/{product}', [TraderProductController::class, 'update']);
        Route::patch('/products/{product}/active', [TraderProductController::class, 'toggleActive']);
        Route::post('/discounts/assign', [DiscountController::class, 'assign']);
    });
});

