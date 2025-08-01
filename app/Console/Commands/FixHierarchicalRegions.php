<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Place;
use App\Models\Region;

class FixHierarchicalRegions extends Command
{
    protected $signature = 'fix:hierarchical-regions';
    protected $description = 'Fix hierarchical region IDs for places';

    public function handle()
    {
        $this->info('Fixing hierarchical region IDs...');
        
        $updated = 0;
        $places = Place::whereNotNull('region_id')->get();
        
        foreach ($places as $place) {
            $region = Region::find($place->region_id);
            if (!$region) {
                $this->warn("Region not found for place: {$place->title}");
                continue;
            }
            
            $updates = [];
            
            // Set based on region type
            if ($region->type === 'city') {
                $updates['city_region_id'] = $region->id;
                $updates['state_region_id'] = $region->parent_id;
            } elseif ($region->type === 'neighborhood') {
                $updates['neighborhood_region_id'] = $region->id;
                $city = Region::find($region->parent_id);
                if ($city) {
                    $updates['city_region_id'] = $city->id;
                    $updates['state_region_id'] = $city->parent_id;
                }
            } elseif ($region->type === 'state') {
                $updates['state_region_id'] = $region->id;
            }
            
            if (!empty($updates)) {
                $place->update($updates);
                $updated++;
                $this->info("✓ Updated: {$place->title} - Region: {$region->name}");
            }
        }
        
        $this->info("\nFixed $updated places with correct hierarchical region IDs");
        
        // Show summary
        $this->info("\nSummary:");
        $this->info("Places with state_region_id: " . Place::whereNotNull('state_region_id')->count());
        $this->info("Places with city_region_id: " . Place::whereNotNull('city_region_id')->count());
        $this->info("Places with neighborhood_region_id: " . Place::whereNotNull('neighborhood_region_id')->count());
    }
}