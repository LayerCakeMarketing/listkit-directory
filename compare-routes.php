<?php

// Compare route performance between SPA and Inertia modes

function analyzeRoutes($mode) {
    // Set environment
    putenv("SPA_MODE=" . ($mode === 'spa' ? 'true' : 'false'));
    
    // Clear opcache to ensure fresh load
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Bootstrap Laravel fresh each time
    require_once __DIR__.'/vendor/autoload.php';
    $app = require __DIR__.'/bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    
    // Get route collection
    $routes = $app->make('router')->getRoutes();
    
    // Separate web and API routes
    $webRoutes = [];
    $apiRoutes = [];
    $catchAllRoutes = [];
    
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (str_starts_with($uri, 'api/')) {
            $apiRoutes[] = $route;
        } elseif (preg_match('/\{.*\}/', $uri) && strpos($uri, '{any}') !== false) {
            $catchAllRoutes[] = $route;
        } else {
            $webRoutes[] = $route;
        }
    }
    
    return [
        'total' => count($routes),
        'web' => count($webRoutes),
        'api' => count($apiRoutes),
        'catchAll' => count($catchAllRoutes),
        'webRoutes' => array_map(fn($r) => $r->uri(), $webRoutes),
        'catchAllRoutes' => array_map(fn($r) => $r->uri(), $catchAllRoutes)
    ];
}

echo "=== Route Comparison ===\n\n";

// Test Inertia mode
$inertiaStats = analyzeRoutes('inertia');
echo "INERTIA MODE:\n";
echo "  Total routes: {$inertiaStats['total']}\n";
echo "  Web routes: {$inertiaStats['web']}\n";
echo "  API routes: {$inertiaStats['api']}\n";
echo "  Catch-all routes: {$inertiaStats['catchAll']}\n";
echo "\n";

// Test SPA mode
$spaStats = analyzeRoutes('spa');
echo "SPA MODE:\n";
echo "  Total routes: {$spaStats['total']}\n";
echo "  Web routes: {$spaStats['web']}\n";
echo "  API routes: {$spaStats['api']}\n";
echo "  Catch-all routes: {$spaStats['catchAll']}\n";
echo "\n";

// Show reduction
$webReduction = $inertiaStats['web'] - $spaStats['web'];
$percentReduction = ($webReduction / $inertiaStats['web']) * 100;

echo "IMPROVEMENTS:\n";
echo "  Web routes reduced by: {$webReduction} ({$percentReduction}%)\n";
echo "  Catch-all routes in SPA: " . implode(', ', $spaStats['catchAllRoutes']) . "\n";
echo "\n";

// Show web routes in SPA mode
echo "WEB ROUTES IN SPA MODE:\n";
foreach ($spaStats['webRoutes'] as $route) {
    echo "  - {$route}\n";
}