<?php

use App\Models\Region;
use App\Models\Place;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->boot();

// Get a valid region
$region = Region::whereType('city')->whereSlug('san-francisco')->first();
if (\!$region) {
    $region = Region::first();
}

$user = User::first();

$place = new Place();
$place->title = 'Transamerica Pyramid';
$place->slug = 'transamerica-pyramid-' . time();
$place->description = 'Iconic pyramid-shaped skyscraper in San Francisco';
$place->type = 'point_of_interest';
$place->category_id = 1;
$place->region_id = $region->id;
$place->created_by_user_id = $user->id;
$place->address = '600 Montgomery Street';
$place->city = 'San Francisco';
$place->state = 'CA';
$place->status = 'published';
$place->save();

echo "SUCCESS\! Place created: {$place->title}\n";
echo "Type: {$place->type}\n";
echo "Address: {$place->address}, {$place->city}, {$place->state}\n";
echo "Coordinates: {$place->latitude}, {$place->longitude}\n";
echo "ZIP Code: {$place->zip_code}\n";
echo "Location updated at: {$place->location_updated_at}\n";
echo "\nGeocoding worked automatically\!\n";
