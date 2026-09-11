<?php

use App\Http\Controllers\Api\FlowTrackArtworkController;
use App\Http\Controllers\Api\FlowTrackBulkQuoteAttachmentController;
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
