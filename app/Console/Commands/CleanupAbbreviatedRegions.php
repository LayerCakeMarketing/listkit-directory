<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Region;
use App\Models\Place;

class CleanupAbbreviatedRegions extends Command
{
    protected $signature = 'regions:cleanup-abbreviated';
    protected $description = 'Remove abbreviated state regions that are no longer in use';

    public function handle()
    {
        $this->info('Checking for unused abbreviated regions...');
        
        // Get all abbreviated state regions
        $abbreviatedRegions = Region::where('type', 'state')
            ->whereRaw('LENGTH(name) = 2')
            ->get();
        
        $deleted = 0;
        
        foreach ($abbreviatedRegions as $region) {
            // Check if any places are still using this region
            $placeCount = Place::where('state_region_id', $region->id)
                ->orWhere('city_region_id', $region->id)
                ->orWhere('neighborhood_region_id', $region->id)
                ->count();
            
            // Check if any child regions exist
            $childCount = Region::where('parent_id', $region->id)->count();
            
            if ($placeCount == 0 && $childCount == 0) {
                $this->info("Deleting unused region: {$region->name} (ID: {$region->id})");
                $region->delete();
                $deleted++;
            } else {
                $this->warn("Cannot delete {$region->name} - still has {$placeCount} places and {$childCount} child regions");
            }
        }
        
        $this->info("Cleanup complete. Deleted {$deleted} unused abbreviated regions.");
        
        return 0;
    }
}