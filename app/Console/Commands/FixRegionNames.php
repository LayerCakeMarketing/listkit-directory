<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Place;
use App\Models\Region;
use App\Helpers\StateHelper;
use Illuminate\Support\Str;

class FixRegionNames extends Command
{
    protected $signature = 'regions:fix-names';
    protected $description = 'Fix region names to use full state names instead of abbreviations';

    public function handle()
    {
        $this->info('Starting region name fix...');
        
        // Step 1: Get all abbreviated state regions
        $abbreviatedRegions = Region::where('type', 'state')
            ->whereRaw('LENGTH(name) = 2')
            ->get();
        
        $this->info("Found {$abbreviatedRegions->count()} abbreviated state regions");
        
        foreach ($abbreviatedRegions as $abbrevRegion) {
            $fullName = StateHelper::normalize($abbrevRegion->name);
            
            // Check if full name region already exists
            $fullNameRegion = Region::where('type', 'state')
                ->where('name', $fullName)
                ->first();
            
            if (!$fullNameRegion) {
                // Create full name region
                $fullNameRegion = Region::create([
                    'name' => $fullName,
                    'slug' => Str::slug($fullName),
                    'type' => 'state',
                    'level' => 1,
                    'parent_id' => null,
                    'abbreviation' => $abbrevRegion->name
                ]);
                $this->info("Created region: {$fullName}");
            }
            
            // Update all places using the abbreviated region
            $updatedPlaces = Place::where('state_region_id', $abbrevRegion->id)
                ->update(['state_region_id' => $fullNameRegion->id]);
            
            $this->info("Updated {$updatedPlaces} places from {$abbrevRegion->name} to {$fullName}");
            
            // Update child regions (cities) to point to the full name state
            $updatedCities = Region::where('parent_id', $abbrevRegion->id)
                ->update(['parent_id' => $fullNameRegion->id]);
            
            $this->info("Updated {$updatedCities} child regions");
        }
        
        // Step 2: Update place counts for all regions
        $this->info('Updating place counts...');
        
        $regions = Region::all();
        foreach ($regions as $region) {
            $count = 0;
            
            if ($region->type == 'state') {
                $count = Place::where('state_region_id', $region->id)
                    ->where('status', 'published')
                    ->count();
            } elseif ($region->type == 'city') {
                $count = Place::where('city_region_id', $region->id)
                    ->where('status', 'published')
                    ->count();
            } elseif ($region->type == 'neighborhood') {
                $count = Place::where('neighborhood_region_id', $region->id)
                    ->where('status', 'published')
                    ->count();
            }
            
            $region->update(['cached_place_count' => $count]);
        }
        
        $this->info('Region fix completed!');
        
        // Step 3: Clear cache to ensure changes are visible
        $this->call('cache:clear');
        
        return 0;
    }
}