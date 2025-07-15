<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is used when SPA_MODE is false (Inertia mode).
| For SPA mode, see web-spa.php
|
*/

// Include authentication routes (for API endpoints)
require __DIR__.'/auth.php';

// Root route - return basic info for API
Route::get('/', function () {
    return response()->json([
        'message' => 'ListKit API',
        'version' => '1.0',
        'frontend_url' => 'http://localhost:5174'
    ]);
});