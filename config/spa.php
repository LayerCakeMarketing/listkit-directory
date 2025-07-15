<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SPA Mode
    |--------------------------------------------------------------------------
    |
    | This value determines whether the application should run in SPA mode
    | or traditional Inertia.js mode. When true, all routes will be handled
    | by Vue Router and data will be fetched via API calls.
    |
    */
    'enabled' => env('SPA_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | API Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all API routes when running in SPA mode.
    |
    */
    'api_prefix' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Frontend URL
    |--------------------------------------------------------------------------
    |
    | The URL where the frontend application is served from in development.
    |
    */
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
];