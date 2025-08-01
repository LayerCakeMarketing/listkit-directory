<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Place;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class UpdatePlaceLocations extends Command
{
    protected $signature = 'update:place-locations';
    protected $description = 'Update places with location data from production';

    public function handle()
    {
        $this->info('Updating place locations from production data...');
        
        // Read the CSV file
        $file = '/tmp/production_locations.csv';
        if (!file_exists($file)) {
            $this->error("Location file not found: $file");
            return;
        }
        
        $csv = array_map('str_getcsv', file($file));
        $headers = array_shift($csv);
        
        $updated = 0;
        $created = 0;
        
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            // Find the place by slug
            $place = Place::where('slug', $data['slug'])->first();
            
            if (!$place) {
                $this->warn("Place not found: {$data['slug']}");
                continue;
            }
            
            // Check if location already exists
            $location = Location::where('directory_entry_id', $place->id)->first();
            
            $locationData = [
                'address_line1' => $data['address_line1'] ?: null,
                'address_line2' => $data['address_line2'] ?: null,
                'city' => $data['city'] ?: null,
                'state' => $data['state'] ?: null,
                'zip_code' => $data['zip_code'] ?: null,
                'country' => $data['country'] ?: 'USA',
                'latitude' => !empty($data['latitude']) && $data['latitude'] != '0.0000000' ? (float)$data['latitude'] : null,
                'longitude' => !empty($data['longitude']) && $data['longitude'] != '0.0000000' ? (float)$data['longitude'] : null,
            ];
            
            if ($location) {
                $location->update($locationData);
                $updated++;
                $this->info("✓ Updated location for: {$place->title}");
            } else {
                $locationData['directory_entry_id'] = $place->id;
                $locationData['place_id'] = $place->id;
                Location::create($locationData);
                $created++;
                $this->info("✓ Created location for: {$place->title}");
            }
        }
        
        $this->info("\nLocation update complete!");
        $this->info("Created: $created locations");
        $this->info("Updated: $updated locations");
        
        // Also update region assignments if missing
        $this->updateRegionAssignments();
    }
    
    private function updateRegionAssignments()
    {
        $this->info("\nUpdating region assignments...");
        
        // Get places with locations but no region
        $placesWithoutRegion = Place::whereNull('region_id')
            ->whereHas('location', function($q) {
                $q->whereNotNull('city')
                  ->whereNotNull('state');
            })
            ->with('location')
            ->get();
            
        $updated = 0;
        
        foreach ($placesWithoutRegion as $place) {
            // Try to find the region based on city and state
            $state = DB::table('regions')
                ->where('type', 'state')
                ->where('name', $place->location->state)
                ->first();
                
            if ($state) {
                $city = DB::table('regions')
                    ->where('type', 'city')
                    ->where('name', $place->location->city)
                    ->where('parent_id', $state->id)
                    ->first();
                    
                if ($city) {
                    $place->region_id = $city->id;
                    $place->save();
                    $updated++;
                    $this->info("✓ Assigned region to: {$place->title} ({$city->name}, {$state->name})");
                }
            }
        }
        
        $this->info("Updated $updated places with region assignments");
    }
}