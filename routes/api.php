<?php

use App\Http\Controllers\Api\FlowTrackArtworkController;
use App\Http\Controllers\Api\FlowTrackBulkQuoteAttachmentController;
use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController as ApiAuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\CurrentCustomerController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController as ApiEmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController as ApiPasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController as ApiRegistrationController;
use App\Http\Controllers\Api\V1\Cart\CartController as ApiCartController;
use App\Http\Controllers\Api\V1\Cart\CartItemController as ApiCartItemController;
use App\Http\Controllers\Api\V1\Cart\CouponController as ApiCouponController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductConfigurationController;
use App\Http\Controllers\Api\V1\Catalog\ProductController as ApiProductController;
use App\Http\Controllers\Api\V1\Catalog\SearchSuggestionController;
use App\Http\Controllers\Api\V1\Checkout\BillingAddressController as ApiBillingAddressController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController as ApiCheckoutController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutInformationController as ApiCheckoutInformationController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutReviewController as ApiCheckoutReviewController;
use App\Http\Controllers\Api\V1\Checkout\PaymentMethodController as ApiPaymentMethodController;
use App\Http\Controllers\Api\V1\Checkout\ShippingAddressController as ApiShippingAddressController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Storefront\HomeController as ApiHomeController;
use App\Http\Controllers\Api\V1\Storefront\StorefrontBootstrapController;
use App\Http\Controllers\Api\V1\Wishlist\WishlistController as ApiWishlistController;
use App\Http\Middleware\VerifyFlowTrackIntegration;
use Illuminate\Support\Facades\Route;

Route::prefix('integrations/flowtrack')
    ->middleware([VerifyFlowTrackIntegration::class, 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/order-items/{orderItem}/artworks/{index}', [FlowTrackArtworkController::class, 'show'])
            ->whereNumber('orderItem')
            ->whereNumber('index')
            ->name('api.flowtrack.order-items.artwork');

        Route::get('/bulk-quotes/{bulkQuote}/attachment', [FlowTrackBulkQuoteAttachmentController::class, 'show'])
            ->whereNumber('bulkQuote')
            ->name('api.flowtrack.bulk-quotes.attachment');
    });

Route::prefix('v1')
    ->middleware('throttle:storefront-api-public')
    ->group(function (): void {
        Route::get('/health', HealthController::class)->name('api.v1.health');

        Route::middleware('web')->group(function (): void {
            Route::get('/storefront/bootstrap', StorefrontBootstrapController::class)
                ->name('api.v1.storefront.bootstrap');

            Route::prefix('auth')->name('api.v1.auth.')->group(function (): void {
                Route::post('/register', ApiRegistrationController::class)
                    ->middleware(['guest:web', 'throttle:5,1'])
                    ->name('register');
                Route::post('/login', [ApiAuthenticatedSessionController::class, 'store'])
                    ->middleware(['guest:web', 'throttle:storefront-login'])
                    ->name('login');
                Route::post('/forgot-password', [ApiPasswordResetController::class, 'forgot'])
                    ->middleware(['guest:web', 'throttle:password-reset-link'])
                    ->name('forgot-password');
                Route::post('/reset-password', [ApiPasswordResetController::class, 'reset'])
                    ->middleware(['guest:web', 'throttle:password-reset'])
                    ->name('reset-password');

                Route::middleware(['auth:web', 'customer'])->group(function (): void {
                    Route::post('/logout', [ApiAuthenticatedSessionController::class, 'destroy'])->name('logout');
                    Route::get('/me', CurrentCustomerController::class)->name('me');
                    Route::get('/verification/status', [ApiEmailVerificationController::class, 'status'])->name('verification.status');
                    Route::post('/verification/resend', [ApiEmailVerificationController::class, 'resend'])
                        ->middleware('throttle:email-verification-send')
                        ->name('verification.resend');
                });
            });

            Route::get('/cart', ApiCartController::class)->name('api.v1.cart.show');
            Route::post('/cart/items', [ApiCartItemController::class, 'store'])->name('api.v1.cart.items.store');
            Route::patch('/cart/items/{item}', [ApiCartItemController::class, 'update'])
                ->where('item', '[A-Fa-f0-9]{40}')
                ->name('api.v1.cart.items.update');
            Route::patch('/cart/items/{item}/options', [ApiCartItemController::class, 'updateOptions'])
                ->where('item', '[A-Fa-f0-9]{40}')
                ->name('api.v1.cart.items.options.update');
            Route::delete('/cart/items/{item}', [ApiCartItemController::class, 'destroy'])
                ->where('item', '[A-Fa-f0-9]{40}')
                ->name('api.v1.cart.items.destroy');
            Route::post('/cart/coupon', [ApiCouponController::class, 'store'])->name('api.v1.cart.coupon.store');
            Route::delete('/cart/coupon', [ApiCouponController::class, 'destroy'])->name('api.v1.cart.coupon.destroy');

            Route::get('/wishlist', [ApiWishlistController::class, 'index'])->name('api.v1.wishlist.index');
            Route::post('/wishlist/items', [ApiWishlistController::class, 'store'])->name('api.v1.wishlist.items.store');
            Route::delete('/wishlist/items/{product}', [ApiWishlistController::class, 'destroy'])
                ->whereNumber('product')
                ->name('api.v1.wishlist.items.destroy');

            Route::prefix('checkout')
                ->name('api.v1.checkout.')
                ->middleware(['auth:web', 'customer', 'verified'])
                ->group(function (): void {
                    Route::get('/', ApiCheckoutController::class)->name('show');
                    Route::put('/information', ApiCheckoutInformationController::class)->middleware('throttle:checkout-step')->name('information.update');
                    Route::put('/shipping-address', ApiShippingAddressController::class)->middleware('throttle:checkout-step')->name('shipping-address.update');
                    Route::put('/billing-address', ApiBillingAddressController::class)->middleware('throttle:checkout-step')->name('billing-address.update');
                    Route::put('/payment-method', ApiPaymentMethodController::class)->middleware('throttle:checkout-step')->name('payment-method.update');
                    Route::get('/review', [ApiCheckoutReviewController::class, 'show'])->name('review.show');
                    Route::put('/review', [ApiCheckoutReviewController::class, 'update'])->middleware('throttle:checkout-step')->name('review.update');
                });
        });

        Route::get('/home', ApiHomeController::class)->name('api.v1.home');
        Route::get('/categories', [ApiCategoryController::class, 'index'])->name('api.v1.categories.index');
        Route::get('/categories/{slug}', [ApiCategoryController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('api.v1.categories.show');
        Route::get('/products', [ApiProductController::class, 'index'])->name('api.v1.products.index');
        Route::get('/products/{slug}', [ApiProductController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('api.v1.products.show');
        Route::get('/products/{slug}/configuration', [ProductConfigurationController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('api.v1.products.configuration.show');
        Route::post('/products/{slug}/price-preview', [ProductConfigurationController::class, 'preview'])->where('slug', '[a-z0-9-]+')->name('api.v1.products.price-preview');
        Route::get('/search/suggestions', SearchSuggestionController::class)
            ->middleware('throttle:catalog-search-suggestions')
            ->name('api.v1.search.suggestions');
    });
