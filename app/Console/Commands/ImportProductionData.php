<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserList;
use App\Models\Place;
use App\Models\Category;
use App\Models\Region;
use App\Models\Location;
use Illuminate\Support\Str;

class ImportProductionData extends Command
{
    protected $signature = 'import:production-data';
    protected $description = 'Import production data from CSV files';

    public function handle()
    {
        $this->info('Starting production data import...');
        
        // Import lists
        $this->importLists();
        
        // Import places
        $this->importPlaces();
        
        $this->info('Import complete!');
    }
    
    private function importLists()
    {
        $file = '/tmp/import_production_lists.csv';
        if (!file_exists($file)) {
            $this->error("Lists file not found: $file");
            return;
        }
        
        $this->info('Importing lists...');
        
        $csv = array_map('str_getcsv', file($file));
        $headers = array_shift($csv);
        
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            // Skip if list already exists
            if (UserList::where('slug', $data['slug'])->exists()) {
                $this->warn("List already exists: {$data['name']}");
                continue;
            }
            
            UserList::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?: null,
                'visibility' => $data['visibility'],
                'status' => $data['status'],
                'owner_type' => $data['owner_type'],
                'owner_id' => $data['owner_id'],
                'user_id' => $data['user_id'],
            ]);
            
            $this->info("✓ Imported list: {$data['name']}");
        }
    }
    
    private function importPlaces()
    {
        $file = '/tmp/import_production_places.csv';
        if (!file_exists($file)) {
            $this->error("Places file not found: $file");
            return;
        }
        
        $this->info('Importing places...');
        
        $csv = array_map('str_getcsv', file($file));
        $headers = array_shift($csv);
        
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            // Skip if place already exists
            if (Place::where('slug', $data['slug'])->exists()) {
                $this->warn("Place already exists: {$data['title']}");
                continue;
            }
            
            // Find or create category
            $category = null;
            if (!empty($data['category_name'])) {
                $category = Category::firstOrCreate(
                    ['name' => $data['category_name']],
                    ['slug' => Str::slug($data['category_name'])]
                );
            }
            
            // Find region by name
            $region = null;
            if (!empty($data['city_name']) && !empty($data['state_name'])) {
                $state = Region::where('type', 'state')
                    ->where('name', $data['state_name'])
                    ->first();
                    
                if ($state) {
                    $region = Region::where('type', 'city')
                        ->where('name', $data['city_name'])
                        ->where('parent_id', $state->id)
                        ->first();
                }
            }
            
            $place = Place::create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => strip_tags($data['description']),
                'type' => $data['type'],
                'category_id' => $category?->id,
                'region_id' => $region?->id,
                'phone' => $data['phone'] ?: null,
                'email' => $data['email'] ?: null,
                'website' => $data['website_url'] ?: null,
                'status' => $data['status'],
                'is_featured' => $data['is_featured'] === 'yes',
                'is_verified' => $data['is_verified'] === 'yes',
                'user_id' => 2, // Default to Eric Larson
            ]);
            
            // Create location if we have address data
            if (!empty($data['address_line1']) || !empty($data['latitude'])) {
                Location::create([
                    'place_id' => $place->id,
                    'address' => $data['address_line1'] ?: null,
                    'city' => $data['city'] ?: null,
                    'state' => $data['state'] ?: null,
                    'zip' => $data['zip_code'] ?: null,
                    'country' => $data['country'] ?: 'USA',
                    'lat' => !empty($data['latitude']) ? (float)$data['latitude'] : null,
                    'lng' => !empty($data['longitude']) ? (float)$data['longitude'] : null,
                ]);
            }
            
            $this->info("✓ Imported place: {$data['title']}");
        }
    }
}