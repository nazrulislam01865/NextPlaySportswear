<?php

use App\Http\Controllers\Storefront\Account\AccountController;
use App\Http\Controllers\Storefront\Account\AddressController;
use App\Http\Controllers\Storefront\Account\PaymentMethodController;
use App\Http\Controllers\Storefront\Account\ProfileController;
use App\Http\Controllers\Storefront\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Storefront\Auth\RegisteredUserController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\Checkout\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/forgot-password', function () {
        return view('storefront.placeholder', [
            'title' => 'Password Reset',
            'message' => 'Password reset email flow will be connected with the mail service in the authentication phase.',
        ]);
    })->name('password.request');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('profile.update');
    Route::patch('/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [AddressController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('addresses.store');
    Route::patch('/addresses/{address}/default', [AddressController::class, 'makeDefault'])->name('addresses.default');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('payment-methods.store');
    Route::patch('/payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'makeDefault'])->name('payment-methods.default');
    Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

    Route::get('/{section}', [AccountController::class, 'section'])->name('section');
});

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show.legacy');

Route::get('/quote-request', function () {
    return view('storefront.placeholder', [
        'title' => 'Quote Request',
        'message' => 'Quote request page will be created in the next step.',
    ]);
})->name('quote.request');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.destroy');


Route::get('/order-confirmation', [OrderController::class, 'confirmation'])->name('order.confirmation');
Route::get('/payment/success', [OrderController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/failed', [OrderController::class, 'paymentFailed'])->name('payment.failed');
Route::get('/order-details', [OrderController::class, 'details'])->name('orders.details.legacy');
Route::get('/orders/{orderNumber}', [OrderController::class, 'details'])->where('orderNumber', '[A-Za-z0-9\-]+')->name('orders.details');
Route::get('/track-order', [OrderController::class, 'tracking'])->name('orders.track');
Route::post('/track-order', [OrderController::class, 'lookup'])->middleware('throttle:8,1')->name('orders.track.lookup');
Route::get('/invoice-download', [OrderController::class, 'invoice'])->name('orders.invoice.legacy');
Route::get('/invoice/{orderNumber}', [OrderController::class, 'invoice'])->where('orderNumber', '[A-Za-z0-9\-]+')->name('orders.invoice');

Route::prefix('checkout')->name('checkout.')->middleware('throttle:60,1')->group(function () {
    Route::get('/', [CheckoutController::class, 'information'])->name('index');
    Route::get('/information', [CheckoutController::class, 'information'])->name('information');
    Route::post('/information', [CheckoutController::class, 'storeInformation'])->middleware('throttle:12,1')->name('information.store');

    Route::get('/shipping-address', [CheckoutController::class, 'shippingAddress'])->name('shipping-address');
    Route::post('/shipping-address', [CheckoutController::class, 'storeShippingAddress'])->middleware('throttle:12,1')->name('shipping-address.store');

    Route::get('/billing-address', [CheckoutController::class, 'billingAddress'])->name('billing-address');
    Route::post('/billing-address', [CheckoutController::class, 'storeBillingAddress'])->middleware('throttle:12,1')->name('billing-address.store');

    Route::get('/shipping-method', [CheckoutController::class, 'shippingMethod'])->name('shipping-method');
    Route::post('/shipping-method', [CheckoutController::class, 'storeShippingMethod'])->middleware('throttle:12,1')->name('shipping-method.store');

    Route::get('/payment-method', [CheckoutController::class, 'paymentMethod'])->name('payment-method');
    Route::post('/payment-method', [CheckoutController::class, 'storePaymentMethod'])->middleware('throttle:8,1')->name('payment-method.store');

    Route::get('/review', [CheckoutController::class, 'review'])->name('review');
    Route::post('/review', [CheckoutController::class, 'storeReview'])->middleware('throttle:10,1')->name('review.store');

    Route::get('/place-order', [CheckoutController::class, 'placeOrder'])->name('place-order');
    Route::post('/place-order', [CheckoutController::class, 'submitOrder'])->middleware('throttle:4,1')->name('place-order.submit');
    Route::get('/success', [CheckoutController::class, 'success'])->name('success');
});
