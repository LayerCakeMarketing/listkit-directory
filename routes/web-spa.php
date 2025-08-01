<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (SPA Mode)
|--------------------------------------------------------------------------
|
| This file is used when SPA_MODE is true.
| These routes serve the SPA shell and handle client-side routing.
|
*/

// Include authentication routes (for API endpoints)
require __DIR__.'/auth.php';

// Stripe webhook endpoint (must be before catch-all)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Catch-all route for SPA - let Vue Router handle client-side routing
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '.*');