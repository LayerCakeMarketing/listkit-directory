<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlaceRegionService;

class PopulatePlaceRegions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'places:populate-regions 
                            {--batch-size=100 : Number of places to process at once}
                            {--clear : Clear existing associations before populating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate place_regions association table for existing places';

    /**
     * Execute the console command.
     */
    public function handle(PlaceRegionService $service)
    {
        $batchSize = (int) $this->option('batch-size');
        
        if ($this->option('clear')) {
            if ($this->confirm('This will clear ALL existing place-region associations. Are you sure?')) {
                $this->info('Clearing existing associations...');
                \DB::table('place_regions')->truncate();
                $this->info('Cleared all place-region associations.');
            } else {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $this->info('Starting to populate place-region associations...');
        $this->info("Processing in batches of {$batchSize}...");
        
        $startTime = microtime(true);
        
        $results = $service->populateExistingPlaceRegions($batchSize);
        
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->info("\nCompleted!");
        $this->info("Total processed: {$results['total_processed']}");
        $this->info("Success: {$results['success']}");
        $this->info("Failed: {$results['failed']}");
        $this->info("Duration: {$duration} seconds");
        
        if ($results['failed'] > 0) {
            $this->warn("Check the logs for details about failed associations.");
        }
        
        return 0;
    }
}
