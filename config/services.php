<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'cloudflare' => [
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'images_token' => env('CLOUDFLARE_IMAGES_TOKEN'),
        'email' => env('CLOUDFLARE_EMAIL'),
        'delivery_url' => env('CLOUDFLARE_IMAGES_DELIVERY_URL', 'https://imagedelivery.net'),
    ],

    'imagekit' => [
        'public_key' => env('IMAGEKIT_PUBLIC_KEY'),
        'private_key' => env('IMAGEKIT_PRIVATE_KEY'),
        'url_endpoint' => env('IMAGEKIT_URL_ENDPOINT', 'https://ik.imagekit.io/listerinolistkit'),
        'id' => env('IMAGEKIT_ID', 'listerinolistkit'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
        'prices' => [
            'tier1' => env('STRIPE_PRICE_TIER1'),
            'tier2' => env('STRIPE_PRICE_TIER2'),
            'verification_fee' => env('STRIPE_VERIFICATION_FEE_PRICE_ID'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'claiming' => [
        'test_mode' => env('CLAIMING_TEST_MODE', false),
        'test_otp' => env('CLAIMING_TEST_OTP', '123456'),
        'auto_approve' => env('CLAIMING_AUTO_APPROVE', false),
        'auto_approve_after_payment' => env('CLAIMING_AUTO_APPROVE_AFTER_PAYMENT', false),
        'auto_approve_free' => env('CLAIMING_AUTO_APPROVE_FREE', false),
    ],

    'geocoding' => [
        'provider' => env('GEOCODING_PROVIDER', 'openstreetmap'), // openstreetmap, google_maps, mapbox
        'cache_duration_days' => env('GEOCODING_CACHE_DAYS', 30),
        'rate_limit_delay_ms' => env('GEOCODING_RATE_LIMIT_MS', 100),
        
        'google_maps' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'region' => env('GOOGLE_MAPS_REGION', 'us'),
        ],
        
        'mapbox' => [
            'access_token' => env('MAPBOX_ACCESS_TOKEN'),
            'country_code' => env('MAPBOX_COUNTRY_CODE', 'us'),
        ],
        
        'openstreetmap' => [
            'user_agent' => env('APP_NAME', 'Laravel') . ' Geocoding Service',
            'country_codes' => env('OSM_COUNTRY_CODES', 'us'),
            'rate_limit' => env('OSM_RATE_LIMIT', 1), // requests per second
        ]
    ],
    
    'mapbox' => [
        'secret_token' => env('MAPBOX_SECRET_TOKEN'),
    ],

];
