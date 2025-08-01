<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DirectoryEntry;
use App\Models\Region;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BulkDataController extends Controller
{
    /**
     * Export places to CSV
     */
    public function exportPlaces(Request $request)
    {
        $places = DirectoryEntry::with(['category', 'region', 'location'])
            ->get()
            ->map(function ($place) {
                return [
                    'id' => $place->id,
                    'title' => $place->title,
                    'slug' => $place->slug,
                    'description' => $place->description,
                    'type' => $place->type,
                    'category_id' => $place->category_id,
                    'category_name' => $place->category?->name,
                    'region_id' => $place->region_id,
                    'region_name' => $place->region?->name,
                    'state_region_id' => $place->state_region_id,
                    'state_name' => $place->state_name,
                    'city_region_id' => $place->city_region_id,
                    'city_name' => $place->city_name,
                    'neighborhood_region_id' => $place->neighborhood_region_id,
                    'neighborhood_name' => $place->neighborhood_name,
                    'address_line1' => $place->location?->address_line1,
                    'address_line2' => $place->location?->address_line2,
                    'city' => $place->location?->city,
                    'state' => $place->location?->state,
                    'zip_code' => $place->location?->zip_code,
                    'country' => $place->location?->country ?? 'USA',
                    'latitude' => $place->location?->latitude,
                    'longitude' => $place->location?->longitude,
                    'phone' => $place->phone,
                    'email' => $place->email,
                    'website_url' => $place->website_url,
                    'status' => $place->status,
                    'is_featured' => $place->is_featured ? 'yes' : 'no',
                    'is_verified' => $place->is_verified ? 'yes' : 'no',
                ];
            });

        $csv = $this->generateCSV($places->toArray());
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="places_export_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * Export regions to CSV
     */
    public function exportRegions(Request $request)
    {
        $regions = Region::with('parent')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'type' => $region->type,
                    'parent_id' => $region->parent_id,
                    'parent_name' => $region->parent?->name,
                    'level' => $region->level,
                    'full_path' => $region->full_path,
                    'latitude' => $region->centroid ? json_decode($region->centroid)?->coordinates[1] : null,
                    'longitude' => $region->centroid ? json_decode($region->centroid)?->coordinates[0] : null,
                    'place_count' => $region->cached_place_count,
                    'is_featured' => $region->is_featured ? 'yes' : 'no',
                ];
            });

        $csv = $this->generateCSV($regions->toArray());
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="regions_export_' . date('Y-m-d_His') . '.csv"',
        ]);
    }

    /**
     * Import places from CSV
     */
    public function importPlaces(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'update_existing' => 'boolean',
        ]);

        $file = $request->file('file');
        $updateExisting = $request->boolean('update_existing', true);
        
        $results = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        $csv = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($csv);
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        $headers = array_map(fn($h) => str_replace(' ', '_', $h), $headers);

        DB::beginTransaction();
        
        try {
            foreach ($csv as $rowIndex => $row) {
                $results['total']++;
                $lineNumber = $rowIndex + 2; // Account for header row and 0-based index
                
                if (count($row) !== count($headers)) {
                    $results['errors'][] = "Line $lineNumber: Column count mismatch";
                    continue;
                }

                $data = array_combine($headers, $row);
                
                // Find or create place
                $place = null;
                if (!empty($data['id'])) {
                    $place = DirectoryEntry::find($data['id']);
                }
                
                if (!$place && !empty($data['slug'])) {
                    $place = DirectoryEntry::where('slug', $data['slug'])->first();
                }
                
                if (!$place) {
                    // Create new place
                    $place = new DirectoryEntry();
                    $isNew = true;
                } else {
                    $isNew = false;
                    if (!$updateExisting) {
                        continue;
                    }
                }

                // Update place data
                try {
                    $this->updatePlaceFromCSV($place, $data);
                    $place->save();
                    
                    // Handle location data
                    if ($this->hasLocationData($data)) {
                        $this->updatePlaceLocation($place, $data);
                    }
                    
                    if ($isNew) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Line $lineNumber: " . $e->getMessage();
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Import regions from CSV
     */
    public function importRegions(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'update_existing' => 'boolean',
        ]);

        $file = $request->file('file');
        $updateExisting = $request->boolean('update_existing', true);
        
        $results = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        $csv = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($csv);
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        $headers = array_map(fn($h) => str_replace(' ', '_', $h), $headers);

        DB::beginTransaction();
        
        try {
            foreach ($csv as $rowIndex => $row) {
                $results['total']++;
                $lineNumber = $rowIndex + 2;
                
                if (count($row) !== count($headers)) {
                    $results['errors'][] = "Line $lineNumber: Column count mismatch";
                    continue;
                }

                $data = array_combine($headers, $row);
                
                // Find or create region
                $region = null;
                if (!empty($data['id'])) {
                    $region = Region::find($data['id']);
                }
                
                if (!$region && !empty($data['slug'])) {
                    $region = Region::where('slug', $data['slug'])->first();
                }
                
                if (!$region) {
                    $region = new Region();
                    $isNew = true;
                } else {
                    $isNew = false;
                    if (!$updateExisting) {
                        continue;
                    }
                }

                // Update region data
                try {
                    $this->updateRegionFromCSV($region, $data);
                    $region->save();
                    
                    if ($isNew) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Line $lineNumber: " . $e->getMessage();
                }
            }
            
            // Update full paths for all regions
            $this->updateRegionPaths();
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Get import template for places
     */
    public function getPlacesTemplate()
    {
        $headers = [
            'id',
            'title',
            'slug',
            'description',
            'type',
            'category_id',
            'category_name',
            'region_id',
            'region_name',
            'state_region_id',
            'state_name',
            'city_region_id',
            'city_name',
            'neighborhood_region_id',
            'neighborhood_name',
            'address_line1',
            'address_line2',
            'city',
            'state',
            'zip_code',
            'country',
            'latitude',
            'longitude',
            'phone',
            'email',
            'website_url',
            'status',
            'is_featured',
            'is_verified',
        ];

        $csv = implode(',', $headers) . "\n";
        
        // Add a sample row
        $sample = [
            '',  // id (leave empty for new)
            'Sample Business',
            'sample-business',
            'A great local business',
            'business_b2c',
            '2',  // category_id
            'Food & Restaurants',
            '32', // region_id
            'San Francisco',
            '82', // state_region_id
            'California',
            '32', // city_region_id
            'San Francisco',
            '',   // neighborhood_region_id
            '',   // neighborhood_name
            '123 Main St',
            'Suite 100',
            'San Francisco',
            'CA',
            '94105',
            'USA',
            '37.7749',
            '-122.4194',
            '(415) 555-0123',
            'info@example.com',
            'https://example.com',
            'published',
            'no',
            'no',
        ];
        
        $csv .= implode(',', $sample);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="places_import_template.csv"',
        ]);
    }

    /**
     * Get import template for regions
     */
    public function getRegionsTemplate()
    {
        $headers = [
            'id',
            'name',
            'slug',
            'type',
            'parent_id',
            'parent_name',
            'level',
            'latitude',
            'longitude',
            'is_featured',
        ];

        $csv = implode(',', $headers) . "\n";
        
        // Add sample rows
        $samples = [
            ['', 'California', 'california', 'state', '', '', '1', '36.7783', '-119.4179', 'no'],
            ['', 'San Francisco', 'san-francisco', 'city', '', 'California', '2', '37.7749', '-122.4194', 'yes'],
            ['', 'Mission District', 'mission-district', 'neighborhood', '', 'San Francisco', '3', '37.7599', '-122.4148', 'no'],
        ];
        
        foreach ($samples as $sample) {
            $csv .= implode(',', $sample) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="regions_import_template.csv"',
        ]);
    }

    /**
     * Generate CSV from array
     */
    private function generateCSV(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Update place from CSV data
     */
    private function updatePlaceFromCSV(DirectoryEntry $place, array $data): void
    {
        if (!empty($data['title'])) {
            $place->title = $data['title'];
        }
        
        if (!empty($data['slug'])) {
            $place->slug = $data['slug'];
        } elseif (!$place->slug && !empty($data['title'])) {
            $place->slug = Str::slug($data['title']);
        }
        
        if (isset($data['description'])) {
            $place->description = $data['description'];
        }
        
        if (!empty($data['type'])) {
            $place->type = $data['type'];
        }
        
        // Handle category
        if (!empty($data['category_id'])) {
            $place->category_id = $data['category_id'];
        } elseif (!empty($data['category_name'])) {
            $category = Category::where('name', $data['category_name'])->first();
            if ($category) {
                $place->category_id = $category->id;
            }
        }
        
        // Handle regions
        if (!empty($data['region_id'])) {
            $place->region_id = $data['region_id'];
        }
        
        if (!empty($data['state_region_id'])) {
            $place->state_region_id = $data['state_region_id'];
        } elseif (!empty($data['state_name'])) {
            $state = Region::where('type', 'state')
                ->where('name', $data['state_name'])
                ->first();
            if ($state) {
                $place->state_region_id = $state->id;
            }
        }
        
        if (!empty($data['city_region_id'])) {
            $place->city_region_id = $data['city_region_id'];
        } elseif (!empty($data['city_name']) && $place->state_region_id) {
            $city = Region::where('type', 'city')
                ->where('name', $data['city_name'])
                ->where('parent_id', $place->state_region_id)
                ->first();
            if ($city) {
                $place->city_region_id = $city->id;
            }
        }
        
        if (!empty($data['neighborhood_region_id'])) {
            $place->neighborhood_region_id = $data['neighborhood_region_id'];
        }
        
        // Update region names
        if ($place->state_region_id) {
            $state = Region::find($place->state_region_id);
            $place->state_name = $state?->name;
        }
        
        if ($place->city_region_id) {
            $city = Region::find($place->city_region_id);
            $place->city_name = $city?->name;
        }
        
        if ($place->neighborhood_region_id) {
            $neighborhood = Region::find($place->neighborhood_region_id);
            $place->neighborhood_name = $neighborhood?->name;
        }
        
        // Other fields
        if (isset($data['phone'])) {
            $place->phone = $data['phone'];
        }
        
        if (isset($data['email'])) {
            $place->email = $data['email'];
        }
        
        if (isset($data['website_url'])) {
            $place->website_url = $data['website_url'];
        }
        
        if (!empty($data['status'])) {
            $place->status = $data['status'];
        }
        
        if (isset($data['is_featured'])) {
            $place->is_featured = in_array(strtolower($data['is_featured']), ['yes', 'true', '1']);
        }
        
        if (isset($data['is_verified'])) {
            $place->is_verified = in_array(strtolower($data['is_verified']), ['yes', 'true', '1']);
        }
        
        // Set created_by if new
        if (!$place->exists && !$place->created_by_user_id) {
            $place->created_by_user_id = auth()->id() ?? 1;
        }
    }

    /**
     * Check if CSV data has location information
     */
    private function hasLocationData(array $data): bool
    {
        return !empty($data['address_line1']) || 
               !empty($data['latitude']) || 
               !empty($data['longitude']) ||
               !empty($data['city']) ||
               !empty($data['state']) ||
               !empty($data['zip_code']);
    }

    /**
     * Update place location from CSV data
     */
    private function updatePlaceLocation(DirectoryEntry $place, array $data): void
    {
        $location = Location::firstOrNew(['directory_entry_id' => $place->id]);
        
        if (isset($data['address_line1'])) {
            $location->address_line1 = $data['address_line1'];
        }
        
        if (isset($data['address_line2'])) {
            $location->address_line2 = $data['address_line2'];
        }
        
        if (isset($data['city'])) {
            $location->city = $data['city'];
        }
        
        if (isset($data['state'])) {
            $location->state = $data['state'];
        }
        
        if (isset($data['zip_code'])) {
            $location->zip_code = $data['zip_code'];
        }
        
        if (isset($data['country'])) {
            $location->country = $data['country'];
        }
        
        if (!empty($data['latitude']) && is_numeric($data['latitude'])) {
            $location->latitude = $data['latitude'];
        }
        
        if (!empty($data['longitude']) && is_numeric($data['longitude'])) {
            $location->longitude = $data['longitude'];
        }
        
        $location->save();
    }

    /**
     * Update region from CSV data
     */
    private function updateRegionFromCSV(Region $region, array $data): void
    {
        if (!empty($data['name'])) {
            $region->name = $data['name'];
        }
        
        if (!empty($data['slug'])) {
            $region->slug = $data['slug'];
        } elseif (!$region->slug && !empty($data['name'])) {
            $region->slug = Str::slug($data['name']);
        }
        
        if (!empty($data['type'])) {
            $region->type = $data['type'];
        }
        
        if (isset($data['parent_id'])) {
            $region->parent_id = !empty($data['parent_id']) ? $data['parent_id'] : null;
        } elseif (!empty($data['parent_name']) && !empty($data['type'])) {
            // Find parent by name and appropriate type
            $parentType = $this->getParentType($data['type']);
            if ($parentType) {
                $parent = Region::where('name', $data['parent_name'])
                    ->where('type', $parentType)
                    ->first();
                if ($parent) {
                    $region->parent_id = $parent->id;
                }
            }
        }
        
        if (isset($data['level'])) {
            $region->level = (int) $data['level'];
        } else {
            // Auto-calculate level based on type
            $levels = [
                'country' => 0,
                'state' => 1,
                'city' => 2,
                'neighborhood' => 3,
            ];
            $region->level = $levels[$region->type] ?? 0;
        }
        
        if (isset($data['is_featured'])) {
            $region->is_featured = in_array(strtolower($data['is_featured']), ['yes', 'true', '1']);
        }
        
        // Handle coordinates
        if (!empty($data['latitude']) && !empty($data['longitude']) && 
            is_numeric($data['latitude']) && is_numeric($data['longitude'])) {
            $region->centroid = DB::raw("ST_GeogFromText('POINT({$data['longitude']} {$data['latitude']})')");
        }
    }

    /**
     * Get expected parent type for a region type
     */
    private function getParentType(string $type): ?string
    {
        $hierarchy = [
            'state' => 'country',
            'city' => 'state',
            'neighborhood' => 'city',
        ];
        
        return $hierarchy[$type] ?? null;
    }

    /**
     * Update full paths for all regions
     */
    private function updateRegionPaths(): void
    {
        // Update in hierarchical order
        $types = ['country', 'state', 'city', 'neighborhood'];
        
        foreach ($types as $type) {
            $regions = Region::where('type', $type)->get();
            
            foreach ($regions as $region) {
                $path = [];
                $current = $region;
                
                while ($current->parent) {
                    array_unshift($path, $current->parent->slug);
                    $current = $current->parent;
                }
                
                if (!empty($path)) {
                    $path[] = $region->slug;
                    $region->full_path = implode('/', $path);
                    $region->save();
                }
            }
        }
    }
}