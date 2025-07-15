<?php

namespace App\Console\Commands;

use App\Models\Place;
use App\Models\Region;
use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpdatePlaceCanonicalUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'places:update-canonical-urls {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing places to use the canonical URL structure /places/state/city/category/entry-id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }
        
        $places = Place::with(['location', 'category', 'stateRegion', 'cityRegion'])
            ->whereHas('location')
            ->where(function($q) {
                $q->whereNull('state_region_id')
                  ->orWhereNull('city_region_id');
            })
            ->get();
        
        $this->info("Found {$places->count()} places to process.");
        
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($places as $place) {
            try {
                // Skip if already has region relationships
                if ($place->state_region_id && $place->city_region_id) {
                    $this->line("Place {$place->id} '{$place->title}' already has regions assigned.");
                    $skipped++;
                    continue;
                }
                
                // Get location data
                $location = $place->location;
                if (!$location) {
                    $this->warn("Place {$place->id} '{$place->title}' has no location data.");
                    $errors++;
                    continue;
                }
                
                // Find or create state region
                $stateRegion = null;
                if ($location->state) {
                    $stateSlug = Str::slug($location->state);
                    $stateRegion = Region::where('slug', $stateSlug)
                        ->where('level', 1)
                        ->first();
                    
                    if (!$stateRegion && !$dryRun) {
                        $stateRegion = Region::create([
                            'name' => $location->state,
                            'slug' => $stateSlug,
                            'type' => 'state',
                            'level' => 1,
                            'is_active' => true
                        ]);
                        $this->info("Created state region: {$stateRegion->name}");
                    } elseif (!$stateRegion) {
                        $this->info("Would create state region: {$location->state}");
                    }
                }
                
                // Find or create city region
                $cityRegion = null;
                if ($location->city && $stateRegion) {
                    $citySlug = Str::slug($location->city);
                    $cityRegion = Region::where('slug', $citySlug)
                        ->where('level', 2)
                        ->where('parent_id', $stateRegion->id)
                        ->first();
                    
                    if (!$cityRegion && !$dryRun) {
                        $cityRegion = Region::create([
                            'name' => $location->city,
                            'slug' => $citySlug,
                            'type' => 'city',
                            'level' => 2,
                            'parent_id' => $stateRegion->id,
                            'is_active' => true
                        ]);
                        $this->info("Created city region: {$cityRegion->name}");
                    } elseif (!$cityRegion) {
                        $this->info("Would create city region: {$location->city}");
                    }
                }
                
                // Update the place with region IDs
                if (!$dryRun && $stateRegion && $cityRegion) {
                    $place->update([
                        'state_region_id' => $stateRegion->id,
                        'city_region_id' => $cityRegion->id,
                        'regions_updated_at' => now()
                    ]);
                    
                    $this->info("Updated place {$place->id} '{$place->title}' with regions.");
                    $updated++;
                } elseif ($dryRun) {
                    $this->info("Would update place {$place->id} '{$place->title}' with state: {$location->state}, city: {$location->city}");
                    $updated++;
                }
                
                // Display the canonical URL
                if ($stateRegion && $cityRegion && $place->category) {
                    $canonicalUrl = "/places/{$stateRegion->slug}/{$cityRegion->slug}/{$place->category->slug}/{$place->slug}-{$place->id}";
                    $this->line("  Canonical URL: {$canonicalUrl}");
                }
                
            } catch (\Exception $e) {
                $this->error("Error processing place {$place->id}: {$e->getMessage()}");
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->info("  Updated: {$updated}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Errors: {$errors}");
        
        if ($dryRun) {
            $this->newLine();
            $this->info("This was a dry run. Run without --dry-run to make changes.");
        }
    }
}