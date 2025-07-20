<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Region;
use App\Models\Place;
use Illuminate\Support\Facades\DB;

class ManageRegionDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regions:manage-duplicates 
                            {--fix : Automatically merge duplicates (interactive)}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and manage duplicate regions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Analyzing regions for duplicates...');
        
        // Find duplicates
        $duplicates = Region::select('name', 'type', 'parent_id', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('name', 'type', 'parent_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
            
        if ($duplicates->isEmpty()) {
            $this->info('No duplicate regions found!');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$duplicates->count()} sets of duplicates:");
        $this->newLine();
        
        $totalMerged = 0;
        
        foreach ($duplicates as $duplicate) {
            // Get all regions matching this duplicate
            $regions = Region::where('name', $duplicate->name)
                ->where('type', $duplicate->type)
                ->when($duplicate->parent_id, function($query) use ($duplicate) {
                    return $query->where('parent_id', $duplicate->parent_id);
                }, function($query) {
                    return $query->whereNull('parent_id');
                })
                ->withCount([
                    'stateEntries as state_places_count',
                    'cityEntries as city_places_count', 
                    'neighborhoodEntries as neighborhood_places_count',
                    'children'
                ])
                ->get();
                
            // Calculate total places for each region
            $regions = $regions->map(function($region) {
                $region->total_places = $region->state_places_count + 
                                       $region->city_places_count + 
                                       $region->neighborhood_places_count;
                return $region;
            });
            
            $this->warn("=== {$duplicate->type}: {$duplicate->name} ===");
            $this->info("Found {$duplicate->duplicate_count} instances:");
            
            $headers = ['ID', 'Name', 'Slug', 'Parent', 'Places', 'Children', 'Created'];
            $rows = [];
            
            foreach ($regions as $region) {
                $parent = $region->parent ? "{$region->parent->name} (#{$region->parent->id})" : 'None';
                $rows[] = [
                    $region->id,
                    $region->name,
                    $region->slug,
                    $parent,
                    $region->total_places,
                    $region->children_count,
                    $region->created_at->format('Y-m-d')
                ];
            }
            
            $this->table($headers, $rows);
            
            if ($this->option('fix') && !$this->option('dry-run')) {
                // Sort by total places descending, then by created_at ascending
                $sortedRegions = $regions->sortByDesc('total_places')->values();
                
                // If all have zero places, keep the oldest one
                if ($sortedRegions->first()->total_places == 0) {
                    $sortedRegions = $regions->sortBy('created_at')->values();
                }
                
                $keepRegion = $sortedRegions->first();
                $mergeRegions = $sortedRegions->slice(1);
                
                $this->info("Recommended: Keep region #{$keepRegion->id} (has {$keepRegion->total_places} places, {$keepRegion->children_count} children)");
                
                if ($mergeRegions->isNotEmpty()) {
                    $mergeIds = $mergeRegions->pluck('id')->implode(', ');
                    $this->info("Would merge regions: #{$mergeIds} into #{$keepRegion->id}");
                    
                    if ($this->confirm('Proceed with merge?')) {
                        $this->mergeRegions($keepRegion, $mergeRegions);
                        $totalMerged++;
                    }
                }
            }
            
            $this->newLine();
        }
        
        if ($this->option('fix') && !$this->option('dry-run')) {
            $this->info("Merged {$totalMerged} sets of duplicate regions.");
        } else if ($this->option('dry-run')) {
            $this->info("Dry run complete. No changes were made.");
        } else {
            $this->info("Run with --fix to merge duplicates interactively.");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Merge duplicate regions into one
     */
    protected function mergeRegions($keepRegion, $mergeRegions)
    {
        DB::beginTransaction();
        
        try {
            foreach ($mergeRegions as $mergeRegion) {
                $this->info("Merging region #{$mergeRegion->id} into #{$keepRegion->id}...");
                
                // Update places that reference this region
                Place::where('state_region_id', $mergeRegion->id)
                    ->update(['state_region_id' => $keepRegion->id]);
                    
                Place::where('city_region_id', $mergeRegion->id)
                    ->update(['city_region_id' => $keepRegion->id]);
                    
                Place::where('neighborhood_region_id', $mergeRegion->id)
                    ->update(['neighborhood_region_id' => $keepRegion->id]);
                
                // Update child regions
                Region::where('parent_id', $mergeRegion->id)
                    ->update(['parent_id' => $keepRegion->id]);
                
                // Update place_regions pivot table
                DB::table('place_regions')
                    ->where('region_id', $mergeRegion->id)
                    ->update(['region_id' => $keepRegion->id]);
                
                // Update featured places
                DB::table('region_featured_entries')
                    ->where('region_id', $mergeRegion->id)
                    ->update(['region_id' => $keepRegion->id]);
                
                // Delete the duplicate region
                $mergeRegion->delete();
                
                $this->info("✓ Merged region #{$mergeRegion->id}");
            }
            
            DB::commit();
            $this->info("✓ Successfully merged all duplicates into region #{$keepRegion->id}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during merge: " . $e->getMessage());
            return false;
        }
        
        return true;
    }
}