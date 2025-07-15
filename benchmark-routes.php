<?php

// Benchmark route performance
$startTime = microtime(true);
$startMemory = memory_get_usage(true);

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get route collection
$routes = $app->make('router')->getRoutes();

$endTime = microtime(true);
$endMemory = memory_get_usage(true);

$executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
$memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

echo "=== Route Performance Benchmark ===\n";
echo "SPA Mode: " . (env('SPA_MODE', false) ? 'ENABLED' : 'DISABLED') . "\n";
echo "Total Routes: " . count($routes) . "\n";
echo "Bootstrap Time: " . number_format($executionTime, 2) . " ms\n";
echo "Memory Used: " . number_format($memoryUsed, 2) . " MB\n";
echo "\n";

// List all routes
echo "Routes:\n";
foreach ($routes as $route) {
    $methods = implode('|', $route->methods());
    $uri = $route->uri();
    echo "  {$methods} {$uri}\n";
}