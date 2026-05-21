<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\CartWebController;
use App\Http\Controllers\Web\CatalogWebController;
use App\Http\Controllers\Web\CheckoutWebController;
use App\Http\Controllers\Web\InvoiceWebController;
use App\Http\Controllers\Web\OrderWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\ReviewWebController;
use App\Http\Controllers\PickupRfidController;
use App\Services\Catalog\CatalogService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (CatalogService $catalogService) {
    return view('home', [
        'homeCategoryCards' => $catalogService->homeCategoryCards(),
    ]);
})->name('home');

// Temporary timezone verification route. Remove after confirming correct app timezone and now() behavior.
Route::get('/debug-timezone', function () {
    return response()->json([
        'app_timezone' => config('app.timezone'),
        'now' => now()->toDateTimeString(),
        'now_kathmandu' => now('Asia/Kathmandu')->toDateTimeString(),
    ]);
});

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::view('/terms-and-conditions', 'legal.terms')->name('legal.terms');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');
Route::view('/cookie-notice', 'legal.cookies')->name('legal.cookies');

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::get('/register-type', function () {
    return view('auth.register-type');
})->name('register-type');
Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthWebController::class, 'register']);
Route::get('/verify-email', [AuthWebController::class, 'showVerifyEmail'])->name('verify-email');
Route::post('/verify-email', [AuthWebController::class, 'verifyEmail'])->name('verify-email.submit');
Route::post('/verify-email/resend', [AuthWebController::class, 'resendVerification'])->name('verify-email.resend');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthWebController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthWebController::class, 'sendForgotPassword'])->name('password.email');
Route::get('/reset-password', [AuthWebController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthWebController::class, 'resetPassword'])->name('password.update');

Route::get('/categories', [CatalogWebController::class, 'categories'])->name('categories');
Route::get('/products/{id}', [CatalogWebController::class, 'show'])->name('products.show');
Route::get('/shops', [CatalogWebController::class, 'shops'])->name('shops.index');
Route::redirect('/traders', '/shops');

Route::get('/cart', [CartWebController::class, 'index'])->name('cart');
Route::post('/cart/items', [CartWebController::class, 'addItem'])->name('cart.add');
Route::post('/cart/items/update', [CartWebController::class, 'updateItem'])->name('cart.update');
Route::post('/cart/items/remove', [CartWebController::class, 'removeItem'])->name('cart.remove');

Route::middleware('auth')->group(function (): void {
    Route::get('/checkout/collection-slot', [CheckoutWebController::class, 'showSlotPicker'])->name('checkout.collection-slot');
    Route::post('/checkout/paypal/create-order', [CheckoutWebController::class, 'createPayPalOrder'])->name('checkout.paypal.create');
    Route::post('/checkout/paypal/redirect', [CheckoutWebController::class, 'startPayPalRedirect'])->name('checkout.paypal.redirect');
    Route::get('/checkout/paypal/return', [CheckoutWebController::class, 'paypalReturn'])->name('checkout.paypal.return');
    Route::get('/checkout/paypal/cancel', [CheckoutWebController::class, 'paypalCancel'])->name('checkout.paypal.cancel');
    Route::post('/checkout/paypal/capture', [CheckoutWebController::class, 'capturePayPal'])->name('checkout.paypal.capture');
    Route::post('/checkout', [CheckoutWebController::class, 'checkout'])->name('checkout');
    Route::get('/profile', [ProfileWebController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileWebController::class, 'update'])->name('profile.update');
    Route::get('/orders', [OrderWebController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderId}', [OrderWebController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderId}/cancel', [OrderWebController::class, 'cancel'])->name('orders.cancel');
    Route::get('/invoices', [InvoiceWebController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{orderId}', [InvoiceWebController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{orderId}/export', [InvoiceWebController::class, 'export'])->name('invoices.export');
    Route::get('/invoices/{orderId}/print', [InvoiceWebController::class, 'print'])->name('invoices.print');
    Route::post('/products/{id}/reviews', [ReviewWebController::class, 'store'])->name('products.reviews.store');
    Route::post('/reviews/{reviewId}/comments', [ReviewWebController::class, 'storeComment'])->name('reviews.comments.store');
});

Route::get('/pickup-rfid', [PickupRfidController::class, 'show']);

Route::redirect('/trader-portal', App\Support\AppUrl::traderPortal('login.php'), 302);
Route::redirect('/trader-portal/', App\Support\AppUrl::traderPortal('login.php'), 302);
