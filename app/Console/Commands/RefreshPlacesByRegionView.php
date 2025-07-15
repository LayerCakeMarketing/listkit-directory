<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshPlacesByRegionView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'places:refresh-materialized-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the materialized view for places by region';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('database.default') !== 'pgsql') {
            $this->info('Materialized views are only available for PostgreSQL.');
            return 0;
        }
        
        $this->info('Refreshing materialized view...');
        
        $startTime = microtime(true);
        
        try {
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY mv_places_by_region');
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Materialized view refreshed successfully in {$duration} seconds.");
            
            // Get some stats
            $count = DB::selectOne('SELECT COUNT(*) as count FROM mv_places_by_region')->count;
            $this->info("Total records in view: {$count}");
            
        } catch (\Exception $e) {
            $this->error('Failed to refresh materialized view: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
