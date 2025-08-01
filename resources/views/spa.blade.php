<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    
    @if(app()->environment('local') && file_exists(public_path('hot')))
        <!-- Development mode with Vite -->
        @vite(['frontend/src/main.js'])
    @else
        <!-- Production mode with built assets -->
        <link rel="stylesheet" href="/frontend/dist/assets/main-4slunYn2.css">
        <script type="module" src="/frontend/dist/assets/main-DWcjHYRH.js"></script>
    @endif
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>