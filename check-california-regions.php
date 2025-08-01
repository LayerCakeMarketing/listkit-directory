<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Region;
use App\Models\Place;

// Check both California regions
$californiaRegions = Region::where('slug', 'california')->get();

foreach ($californiaRegions as $region) {
    echo "Region ID: {$region->id}\n";
    echo "Name: {$region->name}\n";
    echo "Type: {$region->type}, Level: {$region->level}\n";
    echo "Created: {$region->created_at}\n";
    echo "Updated: {$region->updated_at}\n";
    
    // Count places
    $placesAsState = Place::where('state_region_id', $region->id)->count();
    $placesAsCity = Place::where('city_region_id', $region->id)->count();
    $placesAsNeighborhood = Place::where('neighborhood_region_id', $region->id)->count();
    $placesOld = Place::where('region_id', $region->id)->count();
    
    echo "Places as state: {$placesAsState}\n";
    echo "Places as city: {$placesAsCity}\n";
    echo "Places as neighborhood: {$placesAsNeighborhood}\n";
    echo "Places (old field): {$placesOld}\n";
    
    // Count children
    $childrenCount = $region->children()->count();
    echo "Child regions: {$childrenCount}\n";
    
    // Check if has rich data
    echo "Has intro text: " . ($region->intro_text ? 'Yes' : 'No') . "\n";
    echo "Has facts: " . (!empty($region->facts) ? 'Yes' : 'No') . "\n";
    echo "Has state symbols: " . (!empty($region->state_symbols) ? 'Yes' : 'No') . "\n";
    echo "Has cover image: " . ($region->cover_image ? 'Yes' : 'No') . "\n";
    echo "Cloudflare image ID: " . ($region->cloudflare_image_id ?: 'None') . "\n";
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}