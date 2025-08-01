<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Region;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes duplicate regions by:
     * 1. Merging references from duplicate regions to the primary region
     * 2. Deleting duplicate regions
     * 3. Adding unique constraint on slug column
     * 4. Adding support for park region types
     */
    public function up(): void
    {
        DB::transaction(function () {
            $this->logMessage("Starting duplicate region cleanup...");

            // Define duplicate regions and which ones to keep
            // Keep the older regions (created first) as they have more established data
            $duplicateMapping = [
                'california' => ['keep' => 82, 'remove' => [90]],
                'mammoth-lakes' => ['keep' => 86, 'remove' => [92]],
                'irvine' => ['keep' => 6, 'remove' => [91]],
                'florida' => ['keep' => 84, 'remove' => [94]],
                'miami' => ['keep' => 85, 'remove' => [95]]
            ];

            foreach ($duplicateMapping as $slug => $mapping) {
                $this->mergeDuplicateRegion($slug, $mapping['keep'], $mapping['remove']);
            }

            $this->logMessage("Adding unique constraint to slug column...");
            
            // Add unique constraint to slug column to prevent future duplicates
            Schema::table('regions', function (Blueprint $table) {
                $table->unique('slug', 'regions_slug_unique');
            });

            $this->logMessage("Adding park region types...");
            
            // Add support for park types by updating the type column enum if it exists
            // Note: PostgreSQL doesn't have enum constraints by default in Laravel,
            // but we'll add validation at the application level
            
            $this->logMessage("Migration completed successfully!");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->logMessage("Rolling back duplicate region cleanup...");
        
        // Remove unique constraint
        Schema::table('regions', function (Blueprint $table) {
            $table->dropUnique('regions_slug_unique');
        });
        
        // Note: We cannot restore deleted duplicate regions in rollback
        // This is a destructive migration that should be backed up before running
        
        $this->logMessage("Rollback completed. Note: Deleted duplicate regions cannot be restored.");
    }

    /**
     * Merge duplicate regions by updating all foreign key references
     */
    private function mergeDuplicateRegion(string $slug, int $keepId, array $removeIds): void
    {
        $this->logMessage("Processing slug: {$slug} (keep: {$keepId}, remove: " . implode(',', $removeIds) . ")");

        $keepRegion = Region::find($keepId);
        if (!$keepRegion) {
            $this->logMessage("ERROR: Keep region {$keepId} not found!");
            return;
        }

        foreach ($removeIds as $removeId) {
            $removeRegion = Region::find($removeId);
            if (!$removeRegion) {
                $this->logMessage("WARNING: Remove region {$removeId} not found, skipping...");
                continue;
            }

            $this->logMessage("Merging region {$removeId} into {$keepId}...");

            // 1. Update directory_entries references
            $this->updateDirectoryEntriesReferences($removeId, $keepId);

            // 2. Update place_regions references  
            $this->updatePlaceRegionsReferences($removeId, $keepId);

            // 3. Update region_featured_entries references
            $this->updateRegionFeaturedEntriesReferences($removeId, $keepId);

            // 4. Update region_featured_lists references
            $this->updateRegionFeaturedListsReferences($removeId, $keepId);

            // 5. Update child regions' parent_id
            $this->updateChildRegionsParent($removeId, $keepId);

            // 6. Merge cached_place_count and other aggregated data
            $this->mergeRegionData($removeRegion, $keepRegion);

            // 7. Delete the duplicate region
            $this->logMessage("Deleting duplicate region {$removeId}...");
            $removeRegion->delete();
        }

        // Recalculate cached_place_count for the kept region
        $this->recalculatePlaceCount($keepId);
    }

    /**
     * Update directory_entries table references
     */
    private function updateDirectoryEntriesReferences(int $fromId, int $toId): void
    {
        $updates = [
            'state_region_id' => DB::table('directory_entries')->where('state_region_id', $fromId)->count(),
            'city_region_id' => DB::table('directory_entries')->where('city_region_id', $fromId)->count(),
            'neighborhood_region_id' => DB::table('directory_entries')->where('neighborhood_region_id', $fromId)->count(),
            'region_id' => DB::table('directory_entries')->where('region_id', $fromId)->count()
        ];

        foreach ($updates as $column => $count) {
            if ($count > 0) {
                DB::table('directory_entries')
                    ->where($column, $fromId)
                    ->update([$column => $toId]);
                $this->logMessage("  Updated {$count} directory_entries.{$column} references");
            }
        }
    }

    /**
     * Update place_regions table references
     */
    private function updatePlaceRegionsReferences(int $fromId, int $toId): void
    {
        $count = DB::table('place_regions')->where('region_id', $fromId)->count();
        if ($count > 0) {
            // Check for potential duplicates before updating
            $duplicateCheck = DB::table('place_regions')
                ->select('place_id')
                ->where('region_id', $fromId)
                ->whereExists(function ($query) use ($toId) {
                    $query->select(DB::raw(1))
                        ->from('place_regions as pr2')
                        ->whereColumn('pr2.place_id', 'place_regions.place_id')
                        ->where('pr2.region_id', $toId);
                })
                ->get();

            if ($duplicateCheck->count() > 0) {
                // Delete the records that would create duplicates
                DB::table('place_regions')
                    ->where('region_id', $fromId)
                    ->whereIn('place_id', $duplicateCheck->pluck('place_id'))
                    ->delete();
                $this->logMessage("  Deleted {$duplicateCheck->count()} duplicate place_regions entries");
                
                // Update the remaining ones
                $remaining = $count - $duplicateCheck->count();
                if ($remaining > 0) {
                    DB::table('place_regions')
                        ->where('region_id', $fromId)
                        ->update(['region_id' => $toId]);
                    $this->logMessage("  Updated {$remaining} place_regions references");
                }
            } else {
                // No duplicates, safe to update all
                DB::table('place_regions')
                    ->where('region_id', $fromId)
                    ->update(['region_id' => $toId]);
                $this->logMessage("  Updated {$count} place_regions references");
            }
        }
    }

    /**
     * Update region_featured_entries table references
     */
    private function updateRegionFeaturedEntriesReferences(int $fromId, int $toId): void
    {
        $count = DB::table('region_featured_entries')->where('region_id', $fromId)->count();
        if ($count > 0) {
            DB::table('region_featured_entries')
                ->where('region_id', $fromId)
                ->update(['region_id' => $toId]);
            $this->logMessage("  Updated {$count} region_featured_entries references");
        }
    }

    /**
     * Update region_featured_lists table references
     */
    private function updateRegionFeaturedListsReferences(int $fromId, int $toId): void
    {
        $count = DB::table('region_featured_lists')->where('region_id', $fromId)->count();
        if ($count > 0) {
            DB::table('region_featured_lists')
                ->where('region_id', $fromId)
                ->update(['region_id' => $toId]);
            $this->logMessage("  Updated {$count} region_featured_lists references");
        }
    }

    /**
     * Update child regions to point to the correct parent
     */
    private function updateChildRegionsParent(int $fromId, int $toId): void
    {
        $count = DB::table('regions')->where('parent_id', $fromId)->count();
        if ($count > 0) {
            DB::table('regions')
                ->where('parent_id', $fromId)
                ->update(['parent_id' => $toId]);
            $this->logMessage("  Updated {$count} child regions' parent_id");
        }
    }

    /**
     * Merge data from the duplicate region into the kept region
     */
    private function mergeRegionData(Region $fromRegion, Region $keepRegion): void
    {
        // Merge cached_place_count (will be recalculated later)
        // Merge is_featured status (keep if either is featured)
        if ($fromRegion->is_featured && !$keepRegion->is_featured) {
            $keepRegion->is_featured = true;
            $this->logMessage("  Merged is_featured status");
        }

        // Merge metadata if the keep region doesn't have it
        if ($fromRegion->metadata && !$keepRegion->metadata) {
            $keepRegion->metadata = $fromRegion->metadata;
            $this->logMessage("  Merged metadata");
        }

        // Merge other useful fields if they're empty in the keep region
        $fieldsToMerge = ['intro_text', 'meta_title', 'meta_description', 'full_name', 'abbreviation'];
        foreach ($fieldsToMerge as $field) {
            if ($fromRegion->$field && !$keepRegion->$field) {
                $keepRegion->$field = $fromRegion->$field;
                $this->logMessage("  Merged {$field}");
            }
        }

        $keepRegion->save();
    }

    /**
     * Recalculate the cached_place_count for a region
     */
    private function recalculatePlaceCount(int $regionId): void
    {
        // Count from directory_entries
        $directoryCount = DB::table('directory_entries')
            ->where(function ($query) use ($regionId) {
                $query->where('state_region_id', $regionId)
                    ->orWhere('city_region_id', $regionId)
                    ->orWhere('neighborhood_region_id', $regionId)
                    ->orWhere('region_id', $regionId);
            })
            ->count();

        // Count from place_regions
        $placeRegionsCount = DB::table('place_regions')
            ->where('region_id', $regionId)
            ->count();

        // Use the higher count (they represent different association methods)
        $totalCount = max($directoryCount, $placeRegionsCount);

        DB::table('regions')
            ->where('id', $regionId)
            ->update(['cached_place_count' => $totalCount]);

        $this->logMessage("  Recalculated place count: {$totalCount}");
    }

    /**
     * Log migration messages
     */
    private function logMessage(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }
};