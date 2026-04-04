<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebPushSubscriptionController;

// Route::middleware(['auth'])->group(function () {
//     Route::post('/webpush/subscribe', [WebPushSubscriptionController::class, 'store'])->name('webpush.subscribe');
//     Route::post('/webpush/unsubscribe', [WebPushSubscriptionController::class, 'destroy'])->name('webpush.unsubscribe');
// });

Route::middleware(['web', 'auth'])
    ->group(function () {
        Route::post('/webpush/subscribe', [WebPushSubscriptionController::class, 'store'])->name('webpush.subscribe');
        Route::post('/webpush/unsubscribe', [WebPushSubscriptionController::class, 'destroy'])->name('webpush.unsubscribe');
    });