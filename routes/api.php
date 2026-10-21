<?php

use App\Http\Controllers\Api\FlowTrackArtworkController;
use App\Http\Controllers\Api\FlowTrackBulkQuoteAttachmentController;
use App\Http\Middleware\VerifyFlowTrackIntegration;
use Illuminate\Support\Facades\Route;

// Separate status callbacks from private-file transfer throttling. A production
// burst must not delay customer progress updates behind artwork downloads.
Route::post('/integrations/flowtrack/orders/{orderNumber}/progress', \App\Http\Controllers\Api\FlowTrackOrderProgressController::class)
    ->middleware([VerifyFlowTrackIntegration::class, 'throttle:300,1'])
    ->where('orderNumber', '[A-Za-z0-9_-]+')
    ->name('api.flowtrack.orders.progress');

Route::prefix('integrations/flowtrack')
    ->middleware([VerifyFlowTrackIntegration::class, 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/order-items/{orderItem}/artworks/{index}', [FlowTrackArtworkController::class, 'show'])
            ->whereNumber('orderItem')
            ->whereNumber('index')
            ->name('api.flowtrack.order-items.artwork');

        Route::get('/bulk-quotes/{bulkQuote}/files/{file}', [FlowTrackBulkQuoteAttachmentController::class, 'clientFile'])
            ->whereNumber('bulkQuote')->whereNumber('file');

        Route::get('/bulk-quotes/{bulkQuote}/attachment', [FlowTrackBulkQuoteAttachmentController::class, 'show'])
            ->whereNumber('bulkQuote')
            ->name('api.flowtrack.bulk-quotes.attachment');
    });

// Server-to-server catalog and reverse order creation. Same private bearer token
// as the existing progress callback; no browser/CORS access.
Route::prefix('integrations/flowtrack')->middleware([VerifyFlowTrackIntegration::class, 'throttle:120,1'])->group(function (): void {
    Route::get('/catalog/products', [\App\Http\Controllers\Api\FlowTrackCatalogController::class, 'products']);
    Route::get('/catalog/products/{product}', [\App\Http\Controllers\Api\FlowTrackCatalogController::class, 'product'])->whereNumber('product');
    Route::patch('/catalog/products/{product}', [\App\Http\Controllers\Api\FlowTrackCatalogController::class, 'update'])->whereNumber('product');
    Route::get('/catalog/customers', [\App\Http\Controllers\Api\FlowTrackCatalogController::class, 'customers']);
    Route::get('/catalog/customers/{customer}', [\App\Http\Controllers\Api\FlowTrackCatalogController::class, 'customer'])->whereNumber('customer');
    Route::patch('/catalog/customers/{customer}', [\App\Http\Controllers\Api\FlowTrackCatalogController::class, 'updateCustomer'])->whereNumber('customer');
    Route::get('/bulk-quotes/{bulkQuote}/published-files/{file}', \App\Http\Controllers\Api\FlowTrackPublishedQuotationFileController::class)
        ->whereNumber('bulkQuote')->whereNumber('file');
    Route::post('/bulk-quotes/{bulkQuote}/offer-files', \App\Http\Controllers\Api\FlowTrackBulkQuoteOfferController::class)
        ->whereNumber('bulkQuote');
    Route::post('/bulk-quotes/{bulkQuote}/messages', \App\Http\Controllers\Api\FlowTrackBulkQuoteMessageController::class)
        ->whereNumber('bulkQuote');
    Route::post('/bulk-quotes/{bulkQuote}/task-progress', \App\Http\Controllers\Api\FlowTrackBulkQuoteTaskProgressController::class)
        ->whereNumber('bulkQuote');
    Route::post('/bulk-orders', [\App\Http\Controllers\Api\FlowTrackBulkOrderController::class, 'store']);
    Route::post('/orders/from-flowtrack', [\App\Http\Controllers\Api\FlowTrackCreatedOrderController::class, 'store']);
});
