<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ site_setting('site_name', config('app.name', 'Laravel')) }}</title>

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <!-- SEO Meta Tags -->
        <meta name="description" content="{{ site_setting('meta_description', 'Your local directory for discovering places, businesses, and services') }}">
        <meta name="keywords" content="{{ site_setting('meta_keywords', 'local directory, businesses, places, services, reviews, listings') }}">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ site_setting('site_name', config('app.name', 'Laravel')) }}">
        <meta property="og:description" content="{{ site_setting('meta_description', 'Your local directory for discovering places, businesses, and services') }}">
        <meta property="og:image" content="{{ site_setting('og_image', '/images/og-image.jpg') }}">
        
        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:title" content="{{ site_setting('site_name', config('app.name', 'Laravel')) }}">
        <meta property="twitter:description" content="{{ site_setting('meta_description', 'Your local directory for discovering places, businesses, and services') }}">
        <meta property="twitter:image" content="{{ site_setting('og_image', '/images/og-image.jpg') }}">
        
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ site_setting('favicon_url', '/favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite('resources/js/app.js')
        @inertiaHead
        
        <!-- Inject site settings for frontend access -->
        <script>
            window.siteSettings = {
                site_name: '{{ site_setting('site_name', config('app.name', 'Listerino')) }}',
                meta_description: '{{ site_setting('meta_description', 'Your local directory for discovering places, businesses, and services') }}',
                logo_url: '{{ site_setting('logo_url', '/images/listerino_logo.svg') }}',
                primary_color: '{{ site_setting('primary_color', '#4F46E5') }}',
                enable_registration: {{ site_setting('enable_registration', true) ? 'true' : 'false' }}
            };
        </script>
    </head>
    <body class="font-sans antialiased">
        @inertia

     
    </body>
</html>
