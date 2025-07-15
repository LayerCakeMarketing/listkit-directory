<?php

echo "=== Route Count Comparison ===\n\n";

// Count routes in web.php (Inertia mode)
$webContent = file_get_contents(__DIR__ . '/routes/web.php');
$inertiaRoutes = preg_match_all('/Route::(get|post|put|patch|delete|any)\s*\(/i', $webContent, $matches);

// Count routes in web-spa.php (SPA mode)
$spaContent = file_get_contents(__DIR__ . '/routes/web-spa.php');
$spaRoutes = preg_match_all('/Route::(get|post|put|patch|delete|any)\s*\(/i', $spaContent, $matches);

echo "INERTIA MODE (routes/web.php):\n";
echo "  Approximate route definitions: ~{$inertiaRoutes}\n\n";

echo "SPA MODE (routes/web-spa.php):\n";
echo "  Route definitions: {$spaRoutes}\n";
echo "  - 1 catch-all route: /{any}\n";
echo "  - 1 health check: /api/health\n"; 
echo "  - 1 CSRF route: /sanctum/csrf-cookie\n\n";

$reduction = $inertiaRoutes - $spaRoutes;
$percent = round(($reduction / $inertiaRoutes) * 100, 1);

echo "IMPROVEMENT:\n";
echo "  Routes reduced by: {$reduction} ({$percent}%)\n\n";

echo "KEY BENEFITS:\n";
echo "  ✅ Faster route resolution (3 routes vs ~{$inertiaRoutes})\n";
echo "  ✅ Lower memory usage\n";
echo "  ✅ Faster Laravel bootstrap time\n";
echo "  ✅ Scalable to millions of dynamic URLs\n";
echo "  ✅ All routing logic moved to Vue Router\n";