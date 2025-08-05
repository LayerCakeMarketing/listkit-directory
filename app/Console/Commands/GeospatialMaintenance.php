<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Place;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\DB;

class GeospatialMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'geospatial:maintain
                            {--geocode : Run geocoding for places that need it}
                            {--refresh-cache : Refresh the materialized view cache}
                            {--analyze : Analyze table statistics}
                            {--cleanup : Clean up invalid geospatial data}
                            {--stats : Show geospatial statistics}
                            {--limit=50 : Limit for batch operations}';

    /**
     * The console command description.
     */
    protected $description = 'Maintain geospatial data: geocoding, cache refresh, cleanup, and statistics';

    private GeocodingService $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        parent::__construct();
        $this->geocodingService = $geocodingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗺️  Starting geospatial maintenance...');

        if ($this->option('stats')) {
            $this->showStats();
        }

        if ($this->option('cleanup')) {
            $this->cleanupInvalidData();
        }

        if ($this->option('geocode')) {
            $this->runGeocoding();
        }

        if ($this->option('refresh-cache')) {
            $this->refreshCache();
        }

        if ($this->option('analyze')) {
            $this->analyzeTableStats();
        }

        // If no specific options, run a basic maintenance routine
        if (!$this->option('geocode') && !$this->option('refresh-cache') && 
            !$this->option('analyze') && !$this->option('cleanup') && !$this->option('stats')) {
            $this->runBasicMaintenance();
        }

        $this->info('✅ Geospatial maintenance completed!');
    }

    private function showStats()
    {
        $this->info('📊 Geospatial Statistics:');
        
        $stats = $this->geocodingService->getGeocodingStats();
        
        $this->table(['Metric', 'Count'], [
            ['Total Places', number_format($stats['total_places'])],
            ['Geocoded Places', number_format($stats['geocoded_places'])],
            ['Places Needing Geocoding', number_format($stats['needs_geocoding'])],
            ['Verified Locations', number_format($stats['verified_locations'])],
            ['Recently Geocoded (7 days)', number_format($stats['recent_geocoding'])],
        ]);

        // Show geocoding coverage percentage
        $coverage = $stats['total_places'] > 0 ? 
            ($stats['geocoded_places'] / $stats['total_places']) * 100 : 0;
        
        $this->info("📍 Geocoding Coverage: " . number_format($coverage, 1) . "%");

        // Show top regions by place count
        $topRegions = DB::table('directory_entries')
            ->join('regions', 'directory_entries.state_region_id', '=', 'regions.id')
            ->where('directory_entries.status', 'published')
            ->whereNotNull('directory_entries.latitude')
            ->select('regions.name', DB::raw('count(*) as place_count'))
            ->groupBy('regions.id', 'regions.name')
            ->orderByDesc('place_count')
            ->limit(10)
            ->get();

        if ($topRegions->isNotEmpty()) {
            $this->info("\n🏛️  Top Regions by Geocoded Places:");
            $this->table(['Region', 'Places'], 
                $topRegions->map(fn($r) => [$r->name, number_format($r->place_count)])->toArray()
            );
        }
    }

    private function cleanupInvalidData()
    {
        $this->info('🧹 Cleaning up invalid geospatial data...');

        // Find places with invalid coordinates
        $invalidCoords = Place::where(function($q) {
            $q->whereNotNull('latitude')
              ->whereNotNull('longitude')
              ->where(function($subQ) {
                  $subQ->where('latitude', '<', -90)
                       ->orWhere('latitude', '>', 90)
                       ->orWhere('longitude', '<', -180)
                       ->orWhere('longitude', '>', 180);
              });
        })->count();

        if ($invalidCoords > 0) {
            $this->warn("Found {$invalidCoords} places with invalid coordinates");
            
            if ($this->confirm('Clear invalid coordinates?')) {
                $cleared = Place::where(function($q) {
                    $q->where('latitude', '<', -90)
                      ->orWhere('latitude', '>', 90)
                      ->orWhere('longitude', '<', -180)
                      ->orWhere('longitude', '>', 180);
                })->update([
                    'latitude' => null,
                    'longitude' => null,
                    'location_verified' => false
                ]);
                
                $this->info("✅ Cleared {$cleared} invalid coordinate pairs");
            }
        } else {
            $this->info("✅ No invalid coordinates found");
        }

        // Clean up orphaned geometry records
        $geometryFixed = DB::statement('
            UPDATE directory_entries 
            SET geom = NULL 
            WHERE (latitude IS NULL OR longitude IS NULL) 
                AND geom IS NOT NULL
        ');

        $this->info("✅ Cleaned up orphaned geometry records");

        // Update missing geometry for valid coordinates
        $updated = DB::statement('
            UPDATE directory_entries 
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND latitude BETWEEN -90 AND 90 
              AND longitude BETWEEN -180 AND 180
              AND geom IS NULL
        ');

        $this->info("✅ Updated missing geometry for valid coordinates");
    }

    private function runGeocoding()
    {
        $limit = (int) $this->option('limit');
        $this->info("🎯 Running geocoding for up to {$limit} places...");

        $needsGeocoding = Place::needsGeocoding()->count();
        $this->info("Found {$needsGeocoding} places that need geocoding");

        if ($needsGeocoding === 0) {
            $this->info("✅ No places need geocoding");
            return;
        }

        $bar = $this->output->createProgressBar(min($limit, $needsGeocoding));
        $bar->start();

        $successCount = 0;
        $places = Place::needsGeocoding()
            ->whereNotNull('city')
            ->limit($limit)
            ->get();

        foreach ($places as $place) {
            if ($this->geocodingService->geocodePlace($place)) {
                $successCount++;
            }
            
            $bar->advance();
            
            // Rate limiting
            usleep(100000); // 100ms delay
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("✅ Successfully geocoded {$successCount} out of {$places->count()} places");
        
        $remaining = Place::needsGeocoding()->count();
        if ($remaining > 0) {
            $this->warn("⚠️  {$remaining} places still need geocoding");
        }
    }

    private function refreshCache()
    {
        $this->info('🔄 Refreshing materialized view cache...');

        try {
            // Check if materialized view exists
            $viewExists = DB::selectOne("
                SELECT COUNT(*) as count 
                FROM pg_matviews 
                WHERE matviewname = 'directory_entries_geospatial_cache'
            ");

            if ($viewExists->count == 0) {
                $this->warn('Materialized view does not exist. Run migrations first.');
                return;
            }

            $startTime = microtime(true);
            
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY directory_entries_geospatial_cache');
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("✅ Cache refreshed in {$duration}ms");

            // Show cache statistics
            $cacheCount = DB::table('directory_entries_geospatial_cache')->count();
            $this->info("📊 Cache contains " . number_format($cacheCount) . " places");

        } catch (\Exception $e) {
            $this->error("❌ Failed to refresh cache: " . $e->getMessage());
        }
    }

    private function analyzeTableStats()
    {
        $this->info('📈 Analyzing table statistics...');

        $tables = ['directory_entries', 'locations'];
        
        foreach ($tables as $table) {
            $this->info("Analyzing {$table}...");
            DB::statement("ANALYZE {$table}");
        }

        $this->info('✅ Table analysis completed');

        // Show index usage stats
        $indexStats = DB::select("
            SELECT 
                indexname,
                idx_scan as scans,
                idx_tup_read as tuples_read,
                idx_tup_fetch as tuples_fetched
            FROM pg_stat_user_indexes 
            WHERE tablename IN ('directory_entries', 'locations')
                AND idx_scan > 0
            ORDER BY idx_scan DESC
            LIMIT 10
        ");

        if (!empty($indexStats)) {
            $this->info("\n📊 Top Index Usage:");
            $this->table(['Index', 'Scans', 'Tuples Read', 'Tuples Fetched'], 
                collect($indexStats)->map(function($stat) {
                    return [
                        $stat->indexname,
                        number_format($stat->scans),
                        number_format($stat->tuples_read),
                        number_format($stat->tuples_fetched)
                    ];
                })->toArray()
            );
        }
    }

    private function runBasicMaintenance()
    {
        $this->info('🔧 Running basic maintenance routine...');

        // Quick stats
        $stats = $this->geocodingService->getGeocodingStats();
        $this->info("📊 {$stats['geocoded_places']}/{$stats['total_places']} places geocoded");

        // Geocode a small batch if needed
        if ($stats['needs_geocoding'] > 0) {
            $this->info("🎯 Geocoding up to 10 places...");
            $geocoded = $this->geocodingService->batchGeocodePlaces(10);
            $this->info("✅ Geocoded {$geocoded} places");
        }

        // Quick cleanup
        $this->cleanupInvalidData();

        $this->info('✅ Basic maintenance completed');
    }
}