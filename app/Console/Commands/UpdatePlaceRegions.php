<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Place;
use App\Services\PlaceRegionService;

class UpdatePlaceRegions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'places:update-regions {--batch=100 : Number of places to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update region associations for all places with location data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $placeRegionService = app(PlaceRegionService::class);
        $batchSize = $this->option('batch');
        
        $this->info('Updating place regions...');
        
        $result = $placeRegionService->populateExistingPlaceRegions($batchSize);
        
        $this->info("Process completed!");
        $this->info("Total processed: {$result['total_processed']}");
        $this->info("Success: {$result['success']}");
        $this->info("Failed: {$result['failed']}");
        
        if ($result['failed'] > 0) {
            $this->warn("Some places failed to update. Check the logs for details.");
        }
        
        return Command::SUCCESS;
    }
}