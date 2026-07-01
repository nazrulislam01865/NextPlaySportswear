<?php

use App\Http\Controllers\Storefront\Account\AccountController;
use App\Http\Controllers\Storefront\Account\AddressController;
use App\Http\Controllers\Storefront\Account\PaymentMethodController;
use App\Http\Controllers\Storefront\Account\ProfileController;
use App\Http\Controllers\Storefront\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Storefront\Auth\RegisteredUserController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\ContentPageController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\Checkout\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', \App\Http\Controllers\Storefront\SitemapController::class)->name('sitemap');

Route::get('/about-us', [ContentPageController::class, 'about'])->name('about');
Route::get('/contact-us', [ContactController::class, 'index'])->name('contact');
Route::post('/contact-us', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');
Route::get('/help-center', [ContentPageController::class, 'faq'])->name('faq');
Route::get('/how-to-order', [ContentPageController::class, 'howToOrder'])->name('how-to-order');
Route::get('/size-guide', [ContentPageController::class, 'sizeGuide'])->name('size-guide');
Route::get('/artwork-guidelines', [ContentPageController::class, 'artworkGuidelines'])->name('artwork-guidelines');
Route::get('/customization-guide', [ContentPageController::class, 'customizationGuide'])->name('customization-guide');
Route::get('/bulk-team-ordering', [ContentPageController::class, 'bulkOrdering'])->name('bulk-ordering');
Route::get('/shipping-delivery', [ContentPageController::class, 'shipping'])->name('shipping');
Route::get('/returns-refunds-exchanges', [ContentPageController::class, 'returns'])->name('returns');
Route::get('/payment-information', [ContentPageController::class, 'payment'])->name('payment-information');
Route::get('/privacy-policy', [ContentPageController::class, 'privacy'])->name('privacy');
Route::get('/terms-conditions', [ContentPageController::class, 'terms'])->name('terms');
Route::get('/cookie-policy', [ContentPageController::class, 'cookies'])->name('cookies');
Route::get('/accessibility', [ContentPageController::class, 'accessibility'])->name('accessibility');

Route::permanentRedirect('/about', '/about-us');
Route::permanentRedirect('/contact', '/contact-us');
Route::permanentRedirect('/faq', '/help-center');
Route::permanentRedirect('/help', '/help-center');
Route::permanentRedirect('/shipping', '/shipping-delivery');
Route::permanentRedirect('/returns', '/returns-refunds-exchanges');
Route::permanentRedirect('/privacy', '/privacy-policy');
Route::permanentRedirect('/terms', '/terms-conditions');
Route::permanentRedirect('/cookies', '/cookie-policy');
Route::permanentRedirect('/accessibility-statement', '/accessibility');



Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Admin\Auth\AdminSessionController::class, 'create'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Admin\Auth\AdminSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::middleware(['auth:admin', 'admin'])->group(function () {
        Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');
        Route::post('/logout', [\App\Http\Controllers\Admin\Auth\AdminSessionController::class, 'destroy'])->name('logout');

        Route::patch('/homepage-slides/{homepageSlide}/toggle', [\App\Http\Controllers\Admin\HomepageSlideController::class, 'toggle'])->name('homepage-slides.toggle');
        Route::resource('homepage-slides', \App\Http\Controllers\Admin\HomepageSlideController::class)->except('show');

        Route::post('/products/{product}/duplicate', [\App\Http\Controllers\Admin\ProductController::class, 'duplicate'])->name('products.duplicate');
        Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);
        Route::post('/categories/bulk', [\App\Http\Controllers\Admin\CategoryOperationsController::class, 'bulk'])->name('categories.bulk');
        Route::post('/categories/{category}/duplicate', [\App\Http\Controllers\Admin\CategoryController::class, 'duplicate'])->name('categories.duplicate');
        Route::get('/categories/export', [\App\Http\Controllers\Admin\CategoryOperationsController::class, 'export'])->name('categories.export');
        Route::post('/categories/import', [\App\Http\Controllers\Admin\CategoryOperationsController::class, 'import'])
            ->middleware('throttle:3,1')
            ->name('categories.import');
        Route::get('/categories-ordering', [\App\Http\Controllers\Admin\CategoryOperationsController::class, 'ordering'])->name('categories.ordering');
        Route::put('/categories-ordering', [\App\Http\Controllers\Admin\CategoryOperationsController::class, 'updateOrdering'])->name('categories.ordering.update');
        Route::post('/categories/products/sync-legacy', [\App\Http\Controllers\Admin\CategoryProductController::class, 'syncLegacyAssignments'])->name('categories.products.sync-legacy');
        Route::get('/categories/{category}/products', [\App\Http\Controllers\Admin\CategoryProductController::class, 'index'])->name('categories.products.index');
        Route::put('/categories/{category}/products', [\App\Http\Controllers\Admin\CategoryProductController::class, 'update'])->name('categories.products.update');
        Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->except('show');
        Route::resource('attributes', \App\Http\Controllers\Admin\AttributeController::class)->except('show');
        Route::get(
            '/jersey-customization-options/type/{type}',
            [\App\Http\Controllers\Admin\JerseyCustomizationOptionController::class, 'typeIndex']
        )->name('jersey-customization-options.type');
        Route::resource(
            'jersey-customization-options',
            \App\Http\Controllers\Admin\JerseyCustomizationOptionController::class
        )->parameters([
            'jersey-customization-options' => 'jerseyCustomizationOption',
        ])->except('show');
        Route::resource(
            'size-option-groups',
            \App\Http\Controllers\Admin\SizeOptionGroupController::class
        )->parameters([
            'size-option-groups' => 'sizeOptionGroup',
        ])->except('show');
        Route::resource('menus', \App\Http\Controllers\Admin\MenuController::class)->except('show');
        Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class)->except('show');
        Route::resource('rural-area-surcharges', \App\Http\Controllers\Admin\RuralAreaSurchargeController::class)
            ->parameters(['rural-area-surcharges' => 'ruralAreaSurcharge'])
            ->except('show');
        Route::resource('shipping-methods', \App\Http\Controllers\Admin\ShippingMethodController::class)
            ->parameters(['shipping-methods' => 'shippingMethod'])
            ->except('show');
        Route::resource('payment-methods', \App\Http\Controllers\Admin\PaymentMethodController::class)
            ->parameters(['payment-methods' => 'paymentMethod'])
            ->except('show');


        Route::middleware('order.manager')->group(function (): void {
            Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
            Route::patch('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'update'])->middleware('throttle:20,1')->name('orders.update');
            Route::post('/orders/{order}/shipments', [\App\Http\Controllers\Admin\OrderShipmentController::class, 'store'])->middleware('throttle:20,1')->name('orders.shipments.store');
            Route::patch('/orders/{order}/shipments/{shipment}', [\App\Http\Controllers\Admin\OrderShipmentController::class, 'update'])->middleware('throttle:20,1')->name('orders.shipments.update');
            Route::patch('/orders/{order}/requests/{changeRequest}', [\App\Http\Controllers\Admin\OrderChangeRequestController::class, 'update'])->middleware('throttle:20,1')->name('orders.requests.update');
            Route::post('/orders/{order}/downloads', [\App\Http\Controllers\Admin\OrderDownloadController::class, 'store'])->middleware('throttle:10,1')->name('orders.downloads.store');
            Route::delete('/orders/{order}/downloads/{download}', [\App\Http\Controllers\Admin\OrderDownloadController::class, 'destroy'])->name('orders.downloads.destroy');
            Route::get('/returns', [\App\Http\Controllers\Admin\OrderReturnController::class, 'index'])->name('returns.index');
            Route::get('/returns/{returnRequest}', [\App\Http\Controllers\Admin\OrderReturnController::class, 'show'])->name('returns.show');
            Route::get(
                '/returns/{returnRequest}/attachments/{attachment}',
                [\App\Http\Controllers\Admin\OrderReturnController::class, 'downloadAttachment']
            )->name('returns.attachments.download');
            Route::patch('/returns/{returnRequest}', [\App\Http\Controllers\Admin\OrderReturnController::class, 'update'])->middleware('throttle:20,1')->name('returns.update');
        });

        Route::get('/module/{module}', [\App\Http\Controllers\Admin\ModuleController::class, 'show'])
            ->whereIn('module', ['orders', 'customers', 'inventory', 'discounts', 'reviews', 'content', 'reports', 'shipping', 'taxes', 'payments', 'settings'])
            ->name('modules.show');
    });
});

Route::middleware(['guest:web', 'not.admin'])->group(function () {
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
    ->middleware(['auth:web', 'customer'])
    ->name('logout');

Route::middleware(['not.admin', 'auth:web', 'customer'])->prefix('account')->name('account.')->group(function () {
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


    Route::get('/orders', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'dashboard'])->name('orders.dashboard');
    Route::get('/orders/history', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/pay', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'pay'])->name('orders.pay');
    Route::post('/orders/{order}/pay', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'storePayment'])->middleware('throttle:5,1')->name('orders.pay.store');
    Route::get('/orders/{order}/retry-payment', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'retry'])->name('orders.payment.retry');
    Route::get('/orders/{order}/reorder', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'reorder'])->name('orders.reorder');
    Route::post('/orders/{order}/reorder', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'storeReorder'])->middleware('throttle:10,1')->name('orders.reorder.store');
    Route::get('/orders/{order}/cancel-request', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/cancel-request', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'storeCancel'])->middleware('throttle:5,1')->name('orders.cancel.store');
    Route::get('/orders/{order}/change-request', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'change'])->name('orders.change');
    Route::post('/orders/{order}/change-request', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'storeChange'])->middleware('throttle:5,1')->name('orders.change.store');
    Route::get('/orders/{order}/shipments', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'shipments'])->name('orders.shipments');
    Route::get('/orders/{order}/shipments/{shipment}', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'shipment'])->name('orders.shipments.show');
    Route::get('/orders/{order}/invoice', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'invoice'])->name('orders.invoice');
    Route::get('/orders/{order}/invoice/download', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'downloadInvoice'])->middleware('signed')->name('orders.invoice.download');

    Route::get('/orders/{order}/return-request', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'create'])->name('orders.returns.create');
    Route::post('/orders/{order}/return-request', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'store'])->middleware('throttle:5,1')->name('orders.returns.store');
    Route::get('/orders/{order}/exchange-request', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'createExchange'])->name('orders.exchanges.create');
    Route::post('/orders/{order}/exchange-request', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'storeExchange'])->middleware('throttle:5,1')->name('orders.exchanges.store');
    Route::get('/returns', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'index'])->name('returns.index');
    Route::get('/returns/{returnRequest}', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'show'])->name('returns.show');
    Route::get(
        '/return-attachments/{attachment}',
        [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'downloadAttachment']
    )->middleware('signed')->name('return-attachments.download');
    Route::get('/refunds/{refund}', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'refund'])->name('refunds.show');
    Route::get('/credit-notes/{creditNote}', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'creditNote'])->name('credit-notes.show');
    Route::get('/credit-notes/{creditNote}/download', [\App\Http\Controllers\Storefront\Account\ReturnCenterController::class, 'downloadCreditNote'])->middleware('signed')->name('credit-notes.download');
    Route::get('/order-downloads', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'downloads'])->name('downloads.index');
    Route::get('/order-downloads/{download}', [\App\Http\Controllers\Storefront\Account\OrderCenterController::class, 'download'])->middleware('signed')->name('downloads.download');

    Route::get('/{section}', [AccountController::class, 'section'])->name('section');
});

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/category/{slug}', [CategoryController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('categories.show');

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

Route::prefix('checkout')->name('checkout.')->middleware(['not.admin', 'auth:web', 'customer', 'throttle:60,1'])->group(function () {
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
