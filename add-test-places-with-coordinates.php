<?php

use App\Models\Place;
use App\Models\Category;
use App\Models\Region;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Sample San Francisco places with real coordinates
$testPlaces = [
    [
        'title' => 'Golden Gate Bridge',
        'description' => 'Iconic suspension bridge spanning the Golden Gate strait',
        'type' => 'point_of_interest',
        'latitude' => 37.8199,
        'longitude' => -122.4783,
        'address' => 'Golden Gate Bridge',
        'city' => 'San Francisco',
        'state' => 'CA',
    ],
    [
        'title' => 'Alcatraz Island',
        'description' => 'Historic federal prison turned tourist attraction',
        'type' => 'point_of_interest',
        'latitude' => 37.8267,
        'longitude' => -122.4233,
        'address' => 'Alcatraz Island',
        'city' => 'San Francisco',
        'state' => 'CA',
    ],
    [
        'title' => 'Fisherman\'s Wharf',
        'description' => 'Waterfront neighborhood known for seafood and tourist attractions',
        'type' => 'point_of_interest',
        'latitude' => 37.8080,
        'longitude' => -122.4177,
        'address' => 'Fisherman\'s Wharf',
        'city' => 'San Francisco',
        'state' => 'CA',
    ],
    [
        'title' => 'Union Square',
        'description' => 'Major commercial and cultural center',
        'type' => 'business_b2c',
        'latitude' => 37.7880,
        'longitude' => -122.4075,
        'address' => '333 Post St',
        'city' => 'San Francisco',
        'state' => 'CA',
    ],
    [
        'title' => 'Coit Tower',
        'description' => 'Art Deco tower with city views and murals',
        'type' => 'point_of_interest',
        'latitude' => 37.8024,
        'longitude' => -122.4058,
        'address' => '1 Telegraph Hill Blvd',
        'city' => 'San Francisco',
        'state' => 'CA',
    ],
    [
        'title' => 'Ferry Building Marketplace',
        'description' => 'Historic transit hub turned gourmet food market',
        'type' => 'business_b2c',
        'latitude' => 37.7956,
        'longitude' => -122.3933,
        'address' => '1 Ferry Building',
        'city' => 'San Francisco',
        'state' => 'CA',
    ],
    [
        'title' => 'Palace of Fine Arts',
        'description' => 'Greco-Roman style monument and event venue',
        'type' => 'point_of_interest',
        'latitude' => 37.8027,
        'longitude' => -122.4484,
        'address' => '3301 Lyon St',
        'city' => 'San Francisco',
        'state' => 'CA',
    ],
    [
        'title' => 'Chinatown Gate',
        'description' => 'Ornate entrance to historic Chinatown district',
        'type' => 'point_of_interest',
        'latitude' => 37.7907,
        'longitude' => -122.4056,
        'address' => 'Grant Ave & Bush St',
        'city' => 'San Francisco',
        'state' => 'CA',
    ]
];

echo "Adding test places with coordinates...\n\n";

// Get or create a default category
$category = Category::firstOrCreate(
    ['slug' => 'attractions'],
    ['name' => 'Attractions', 'color' => '#3B82F6']
);

// Get or create California and San Francisco regions
$california = Region::firstOrCreate(
    ['slug' => 'california', 'type' => 'state'],
    ['name' => 'California']
);

$sanFrancisco = Region::firstOrCreate(
    ['slug' => 'san-francisco', 'type' => 'city', 'parent_id' => $california->id],
    ['name' => 'San Francisco']
);

$created = 0;
$updated = 0;

foreach ($testPlaces as $placeData) {
    // Check if place already exists by title
    $existingPlace = Place::where('title', $placeData['title'])->first();
    
    if ($existingPlace) {
        // Update coordinates if missing
        if (!$existingPlace->latitude || !$existingPlace->longitude) {
            $existingPlace->update([
                'latitude' => $placeData['latitude'],
                'longitude' => $placeData['longitude'],
                'address' => $placeData['address'],
                'city' => $placeData['city'],
                'state' => $placeData['state'],
            ]);
            echo "✓ Updated coordinates for: {$placeData['title']}\n";
            $updated++;
        } else {
            echo "- Skipped (has coordinates): {$placeData['title']}\n";
        }
    } else {
        // Create new place
        Place::create([
            'title' => $placeData['title'],
            'slug' => Str::slug($placeData['title']),
            'description' => $placeData['description'],
            'type' => $placeData['type'],
            'category_id' => $category->id,
            'state_region_id' => $california->id,
            'city_region_id' => $sanFrancisco->id,
            'latitude' => $placeData['latitude'],
            'longitude' => $placeData['longitude'],
            'address' => $placeData['address'],
            'city' => $placeData['city'],
            'state' => $placeData['state'],
            'status' => 'published',
            'published_at' => now(),
            'created_by_user_id' => 1
        ]);
        echo "✓ Created: {$placeData['title']}\n";
        $created++;
    }
}

echo "\nSummary:\n";
echo "- Created: $created places\n";
echo "- Updated: $updated places\n";

// Show total places with coordinates
$totalWithCoords = Place::whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->count();

echo "- Total places with coordinates: $totalWithCoords\n";

// Test the map API
echo "\nTesting map API endpoint...\n";

$client = new \GuzzleHttp\Client(['base_uri' => 'http://localhost:8000']);

try {
    $response = $client->get('/api/places/map-data', [
        'query' => [
            'bounds' => [
                'north' => 37.85,
                'south' => 37.75,
                'east' => -122.35,
                'west' => -122.50
            ]
        ]
    ]);
    
    $data = json_decode($response->getBody(), true);
    echo "✓ API returned {$data['total']} places in San Francisco bounds\n";
    
    if ($data['total'] > 0) {
        echo "\nFirst few places:\n";
        foreach (array_slice($data['features'], 0, 3) as $feature) {
            $props = $feature['properties'];
            echo "  - {$props['name']} at ({$feature['geometry']['coordinates'][1]}, {$feature['geometry']['coordinates'][0]})\n";
        }
    }
} catch (\Exception $e) {
    echo "✗ API Error: " . $e->getMessage() . "\n";
}

echo "\nDone! You can now visit http://localhost:5173/places/map to see the places on the map.\n";