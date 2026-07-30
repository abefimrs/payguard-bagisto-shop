<?php

use Illuminate\Support\Facades\Route;
use Webkul\PayGuard\Http\Controllers\PayGuardController;

Route::group(['middleware' => ['web']], function () {

    // Customer is sent here right after "Place Order"
    Route::get('payguard/redirect/{provider}', [PayGuardController::class, 'redirect'])
        ->where('provider', 'bkash|nagad')
        ->name('payguard.redirect');

    // Browser return from PayGuard (public — no CSRF, PayGuard calls this directly)
    Route::get('payguard/callback/{provider}', [PayGuardController::class, 'callback'])
        ->where('provider', 'bkash|nagad')
        ->name('payguard.callback');

    // Server-to-server signed webhook (must stay outside CSRF protection)
    Route::post('api/payguard/webhook', [PayGuardController::class, 'webhook'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('payguard.webhook');
});
