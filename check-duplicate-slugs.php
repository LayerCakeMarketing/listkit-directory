<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Region;
use Illuminate\Support\Facades\DB;

// Find duplicate slugs
$duplicates = DB::table('regions')
    ->select('slug', DB::raw('COUNT(*) as count'))
    ->groupBy('slug')
    ->havingRaw('COUNT(*) > ?', [1])
    ->get();

echo "Duplicate slugs found:\n";
foreach ($duplicates as $duplicate) {
    echo "\nSlug: {$duplicate->slug} (Count: {$duplicate->count})\n";
    
    $regions = Region::where('slug', $duplicate->slug)->get();
    foreach ($regions as $region) {
        echo "  - ID: {$region->id}, Name: {$region->name}, Type: {$region->type}, Level: {$region->level}\n";
    }
}

// Check specifically for california
echo "\n\nAll regions with 'california' slug:\n";
$californiaRegions = Region::where('slug', 'california')->get();
foreach ($californiaRegions as $region) {
    echo "ID: {$region->id}, Name: {$region->name}, Type: {$region->type}, Level: {$region->level}\n";
}