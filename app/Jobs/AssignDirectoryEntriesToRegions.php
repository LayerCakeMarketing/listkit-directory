<?php

namespace App\Jobs;

use App\Models\Place;
use App\Models\Region;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignDirectoryEntriesToRegions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $directoryEntryIds;
    protected $forceReassign;
    protected $batchSize = 100;

    /**
     * Create a new job instance.
     *
     * @param array|null $directoryEntryIds Specific entry IDs to process, or null for all
     * @param bool $forceReassign Force reassignment even if regions_updated_at is recent
     */
    public function __construct(?array $directoryEntryIds = null, bool $forceReassign = false)
    {
        $this->directoryEntryIds = $directoryEntryIds;
        $this->forceReassign = $forceReassign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Only run on PostgreSQL with PostGIS
        if (config('database.default') !== 'pgsql') {
            Log::warning('AssignDirectoryEntriesToRegions job skipped - requires PostgreSQL with PostGIS');
            return;
        }

        // Build query for entries to process
        $query = Place::query()
            ->whereHas('location', function ($q) {
                $q->whereNotNull('latitude')
                  ->whereNotNull('longitude');
            })
            ->with('location');

        // Filter to specific IDs if provided
        if ($this->directoryEntryIds) {
            $query->whereIn('id', $this->directoryEntryIds);
        }

        // Skip recently updated entries unless forced
        if (!$this->forceReassign) {
            $query->where(function ($q) {
                $q->whereNull('regions_updated_at')
                  ->orWhere('regions_updated_at', '<', now()->subDays(7));
            });
        }

        // Process in chunks to avoid memory issues
        $query->chunk($this->batchSize, function ($entries) {
            foreach ($entries as $entry) {
                $this->assignRegionsToEntry($entry);
            }
        });

        // Update region place counts
        $this->updateRegionCounts();
    }

    /**
     * Assign regions to a single directory entry based on its location
     */
    protected function assignRegionsToEntry(Place $entry): void
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

        // Assign regions by level
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

        // Alternative: Try to match by address if no spatial match
        if (!$updates['state_region_id'] && !$updates['city_region_id']) {
            $this->assignRegionsByAddress($entry, $updates);
        }

        // Update the entry
        $entry->update($updates);

        Log::info('Assigned regions to directory entry', [
            'entry_id' => $entry->id,
            'state_region_id' => $updates['state_region_id'],
            'city_region_id' => $updates['city_region_id'],
            'neighborhood_region_id' => $updates['neighborhood_region_id']
        ]);
    }

    /**
     * Fallback method to assign regions based on address matching
     */
    protected function assignRegionsByAddress(Place $entry, array &$updates): void
    {
        if (!$entry->location) {
            return;
        }

        // Try to match state
        if ($entry->location->state) {
            $stateRegion = Region::where('level', 1)
                ->where(function ($q) use ($entry) {
                    $q->where('name', $entry->location->state)
                      ->orWhere('name', 'like', $entry->location->state . '%');
                })
                ->first();

            if ($stateRegion) {
                $updates['state_region_id'] = $stateRegion->id;

                // Try to match city within this state
                if ($entry->location->city) {
                    $cityRegion = Region::where('level', 2)
                        ->where('parent_id', $stateRegion->id)
                        ->where(function ($q) use ($entry) {
                            $q->where('name', $entry->location->city)
                              ->orWhere('name', 'like', $entry->location->city . '%');
                        })
                        ->first();

                    if ($cityRegion) {
                        $updates['city_region_id'] = $cityRegion->id;

                        // Try to match neighborhood within this city
                        if ($entry->location->neighborhood) {
                            $neighborhoodRegion = Region::where('level', 3)
                                ->where('parent_id', $cityRegion->id)
                                ->where(function ($q) use ($entry) {
                                    $q->where('name', $entry->location->neighborhood)
                                      ->orWhere('name', 'like', $entry->location->neighborhood . '%');
                                })
                                ->first();

                            if ($neighborhoodRegion) {
                                $updates['neighborhood_region_id'] = $neighborhoodRegion->id;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Update cached place counts for all affected regions
     */
    protected function updateRegionCounts(): void
    {
        // Update counts for all regions with a more efficient query
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

        Log::info('Updated region place counts');
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to assign regions to directory entries', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}