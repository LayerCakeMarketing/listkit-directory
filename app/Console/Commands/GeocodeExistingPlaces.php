<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Place;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Log;

class GeocodeExistingPlaces extends Command
{
    protected $signature = 'places:geocode 
                            {--limit=100 : Number of places to process}
                            {--dry-run : Run without saving changes}
                            {--missing-only : Only geocode places without coordinates}
                            {--force : Geocode even if coordinates exist}';

    protected $description = 'Geocode existing places that are missing coordinates';

    protected $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        parent::__construct();
        $this->geocodingService = $geocodingService;
    }

    public function handle()
    {
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');
        $missingOnly = $this->option('missing-only');
        $force = $this->option('force');

        $this->info('Starting geocoding process...');
        
        // Build query
        $query = Place::query();
        
        if ($missingOnly || !$force) {
            $query->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude');
            });
        }
        
        // Must have some address info
        $query->where(function($q) {
            $q->whereNotNull('address')
              ->orWhereNotNull('city');
        });
        
        $places = $query->limit($limit)->get();
        
        $this->info("Found {$places->count()} places to process");
        
        $successCount = 0;
        $failCount = 0;
        
        $bar = $this->output->createProgressBar($places->count());
        $bar->start();
        
        foreach ($places as $place) {
            $bar->advance();
            
            // Build address
            $addressParts = array_filter([
                $place->address,
                $place->city,
                $place->state,
                'USA'
            ]);
            
            if (empty($addressParts)) {
                $failCount++;
                continue;
            }
            
            $fullAddress = implode(', ', $addressParts);
            
            try {
                $result = $this->geocodingService->geocodeAddress($fullAddress);
                
                if ($result && $result['confidence'] >= 0.5) {
                    if (!$dryRun) {
                        $place->latitude = $result['coordinates']['lat'];
                        $place->longitude = $result['coordinates']['lng'];
                        $place->location_updated_at = now();
                        $place->location_verified = false;
                        
                        // Skip observer to avoid re-geocoding
                        $place->saveQuietly();
                    }
                    
                    $successCount++;
                    
                    $this->line("\n✓ {$place->title}: {$result['coordinates']['lat']}, {$result['coordinates']['lng']} (confidence: {$result['confidence']})");
                } else {
                    $failCount++;
                    $this->line("\n✗ {$place->title}: Failed to geocode");
                }
                
                // Respect rate limits
                usleep(100000); // 0.1 seconds
                
            } catch (\Exception $e) {
                $failCount++;
                $this->error("\n✗ {$place->title}: Error - " . $e->getMessage());
            }
        }
        
        $bar->finish();
        
        $this->info("\n\nGeocoding complete!");
        $this->info("Success: $successCount");
        $this->info("Failed: $failCount");
        
        if ($dryRun) {
            $this->warn("This was a dry run. No changes were saved.");
        }
    }
}