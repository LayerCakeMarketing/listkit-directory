<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Channel Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for channels, including
    | forbidden slugs that cannot be used for channel names.
    |
    */

    'forbidden_slugs' => [
        // System routes
        'admin',
        'api',
        'auth',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'email-verification',
        'dashboard',
        
        // User routes
        'profile',
        'settings',
        'account',
        'user',
        'users',
        'me',
        
        // Content routes
        'home',
        'local',
        'places',
        'place',
        'lists',
        'list',
        'mylists',
        'my-lists',
        'myplaces',
        'my-places',
        'saved',
        'channels',
        'channel',
        'chains',
        'chain',
        
        // Feature routes
        'search',
        'explore',
        'discover',
        'trending',
        'popular',
        'featured',
        'notifications',
        'messages',
        'chat',
        
        // Action routes
        'edit',
        'create',
        'new',
        'delete',
        'remove',
        'update',
        'manage',
        'claim',
        'verify',
        
        // Page routes
        'about',
        'contact',
        'help',
        'support',
        'privacy',
        'terms',
        'tos',
        'policy',
        'legal',
        'blog',
        'news',
        'press',
        
        // Special routes
        'p', // Short URL prefix
        'up', // User profile prefix
        'app',
        'assets',
        'public',
        'storage',
        'vendor',
        'api-docs',
        'docs',
        'documentation',
        
        // Business/monetization
        'subscribe',
        'subscription',
        'billing',
        'payment',
        'stripe',
        'webhook',
        'webhooks',
        
        // Admin sections
        'reports',
        'analytics',
        'stats',
        'statistics',
        'media',
        'marketing',
        'waitlist',
        
        // Common technical terms
        'css',
        'js',
        'img',
        'images',
        'fonts',
        'files',
        'upload',
        'download',
        'export',
        'import',
        
        // Region/location routes
        'regions',
        'region',
        'state',
        'states',
        'city',
        'cities',
        'neighborhood',
        'neighborhoods',
        
        // SEO/Marketing pages
        'sitemap',
        'robots',
        'rss',
        'feed',
        'amp',
        
        // Reserved for future use
        'groups',
        'events',
        'jobs',
        'marketplace',
        'shop',
        'store',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | URL Generation Settings
    |--------------------------------------------------------------------------
    */
    
    'use_at_prefix' => false, // Set to true to use @ prefix in URLs (legacy)
];