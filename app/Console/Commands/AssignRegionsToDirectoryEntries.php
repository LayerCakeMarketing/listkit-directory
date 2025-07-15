<?php

namespace App\Console\Commands;

use App\Jobs\AssignDirectoryEntriesToRegions;
use App\Models\Place;
use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignRegionsToDirectoryEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regions:assign-entries 
                            {--ids=* : Specific directory entry IDs to process}
                            {--region= : Process only entries in this region ID}
                            {--state= : Process only entries in this state (by name)}
                            {--force : Force reassignment even for recently updated entries}
                            {--queue : Process via queue (recommended for large datasets)}
                            {--batch-size=100 : Number of entries to process per job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign directory entries to appropriate regions based on their location';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('This command requires PostgreSQL with PostGIS extension.');
            return 1;
        }

        $this->info('Starting region assignment for directory entries...');

        // Build query based on options
        $query = Place::query()
            ->whereHas('location', function ($q) {
                $q->whereNotNull('latitude')
                  ->whereNotNull('longitude');
            });

        // Filter by specific IDs
        if ($ids = $this->option('ids')) {
            $query->whereIn('id', $ids);
            $this->info('Processing specific entry IDs: ' . implode(', ', $ids));
        }

        // Filter by region
        if ($regionId = $this->option('region')) {
            $region = Region::find($regionId);
            if (!$region) {
                $this->error("Region ID {$regionId} not found.");
                return 1;
            }
            $query->inRegion($regionId);
            $this->info("Processing entries in region: {$region->name}");
        }

        // Filter by state name
        if ($stateName = $this->option('state')) {
            $stateRegion = Region::where('level', 1)
                ->where('name', 'like', $stateName . '%')
                ->first();
            
            if (!$stateRegion) {
                $this->error("State '{$stateName}' not found.");
                return 1;
            }
            
            $query->where(function ($q) use ($stateRegion) {
                $q->where('state_region_id', $stateRegion->id)
                  ->orWhereHas('location', function ($loc) use ($stateRegion) {
                      $loc->where('state', $stateRegion->name);
                  });
            });
            
            $this->info("Processing entries in state: {$stateRegion->name}");
        }

        // Check if forcing reassignment
        $force = $this->option('force');
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('regions_updated_at')
                  ->orWhere('regions_updated_at', '<', now()->subDays(7));
            });
            $this->info('Skipping recently updated entries (use --force to override)');
        }

        $totalCount = $query->count();
        
        if ($totalCount === 0) {
            $this->info('No entries found to process.');
            return 0;
        }

        $this->info("Found {$totalCount} entries to process.");

        // Process via queue or directly
        if ($this->option('queue')) {
            $this->processViaQueue($query, $totalCount);
        } else {
            $this->processDirect($query, $totalCount);
        }

        $this->info('Region assignment completed!');
        
        // Update region counts
        $this->info('Updating region place counts...');
        $this->updateRegionCounts();
        
        return 0;
    }

    /**
     * Process entries via queue jobs
     */
    protected function processViaQueue($query, $totalCount)
    {
        $batchSize = (int) $this->option('batch-size');
        $jobCount = 0;

        $query->chunk($batchSize, function ($entries) use (&$jobCount) {
            $ids = $entries->pluck('id')->toArray();
            AssignDirectoryEntriesToRegions::dispatch($ids, $this->option('force'));
            $jobCount++;
        });

        $this->info("Dispatched {$jobCount} jobs to process {$totalCount} entries.");
        $this->info('Jobs will be processed in the background.');
    }

    /**
     * Process entries directly (synchronously)
     */
    protected function processDirect($query, $totalCount)
    {
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $processedCount = 0;
        $assignedCount = 0;

        $query->with(['location', 'stateRegion', 'cityRegion', 'neighborhoodRegion'])
            ->chunk(100, function ($entries) use ($bar, &$processedCount, &$assignedCount) {
                foreach ($entries as $entry) {
                    $oldState = $entry->state_region_id;
                    $oldCity = $entry->city_region_id;
                    $oldNeighborhood = $entry->neighborhood_region_id;

                    // Assign regions
                    $this->assignRegionsToEntry($entry);

                    if ($oldState !== $entry->state_region_id ||
                        $oldCity !== $entry->city_region_id ||
                        $oldNeighborhood !== $entry->neighborhood_region_id) {
                        $assignedCount++;
                    }

                    $processedCount++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        
        $this->info("Processed {$processedCount} entries.");
        $this->info("Assigned/updated regions for {$assignedCount} entries.");
    }

    /**
     * Assign regions to a single entry
     */
    protected function assignRegionsToEntry(Place $entry)
    {
        if (!$entry->location || !$entry->location->latitude || !$entry->location->longitude) {
            return;
        }

        $latitude = $entry->location->latitude;
        $longitude = $entry->location->longitude;

        // Find all regions containing this point
        $regions = Region::containingPoint($latitude, $longitude)
            ->orderBy('level')
            ->get();

        $updates = [
            'state_region_id' => null,
            'city_region_id' => null,
            'neighborhood_region_id' => null,
            'regions_updated_at' => now()
        ];

        foreach ($regions as $region) {
            switch ($region->level) {
                case 1: // State
                    $updates['state_region_id'] = $region->id;
                    break;
                case 2: // City
                    $updates['city_region_id'] = $region->id;
                    break;
                case 3: // Neighborhood
                    $updates['neighborhood_region_id'] = $region->id;
                    break;
            }
        }

        // If no spatial match, try address matching
        if (!$updates['state_region_id'] && !$updates['city_region_id']) {
            $this->assignByAddress($entry, $updates);
        }

        $entry->update($updates);
    }

    /**
     * Try to assign regions based on address
     */
    protected function assignByAddress(Place $entry, array &$updates)
    {
        if (!$entry->location) {
            return;
        }

        // Match state
        if ($entry->location->state) {
            $stateRegion = Region::where('level', 1)
                ->where('name', 'like', $entry->location->state . '%')
                ->first();

            if ($stateRegion) {
                $updates['state_region_id'] = $stateRegion->id;

                // Match city within state
                if ($entry->location->city) {
                    $cityRegion = Region::where('level', 2)
                        ->where('parent_id', $stateRegion->id)
                        ->where('name', 'like', $entry->location->city . '%')
                        ->first();

                    if ($cityRegion) {
                        $updates['city_region_id'] = $cityRegion->id;
                    }
                }
            }
        }
    }

    /**
     * Update cached place counts for all regions
     */
    protected function updateRegionCounts()
    {
        DB::statement('
            UPDATE regions r
            SET cached_place_count = (
                SELECT COUNT(*)
                FROM directory_entries de
                WHERE de.status = \'published\'
                AND (
                    (r.level = 1 AND de.state_region_id = r.id) OR
                    (r.level = 2 AND de.city_region_id = r.id) OR
                    (r.level = 3 AND de.neighborhood_region_id = r.id)
                )
            )
        ');

        $this->info('Region place counts updated.');
    }
}