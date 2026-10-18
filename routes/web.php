<?php

use Illuminate\Support\Facades\Route;

// Route::statamic('example', 'example-view', [
//    'title' => 'Example'
// ]);

Route::statamic('checkout', 'checkout.index', ['title' => 'Checkout', 'layout' => 'checkout.layout'])
    ->name('checkout');

Route::statamic('checkout/confirmation', 'checkout.confirmation', ['title' => 'Order Confirmation', 'layout' => 'checkout.layout'])
    ->name('checkout.confirmation')
    ->middleware('signed');

/*
 * The demo's feed lab: made-up feeds for Ingest to read (local only).
 */
if (app()->environment('local')) {
    Route::prefix('feed-lab')->group(function () {
        Route::get('supplier/books', [App\Http\Controllers\FeedLabController::class, 'books']);
        Route::get('supplier/stock.csv', [App\Http\Controllers\FeedLabController::class, 'stock']);
        Route::get('api/events', [App\Http\Controllers\FeedLabController::class, 'events']);
        Route::get('{path}', [App\Http\Controllers\FeedLabController::class, 'file'])->where('path', '.*');
    });
}
