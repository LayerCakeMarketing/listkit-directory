<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegionBulkImportController extends Controller
{
    /**
     * Process bulk CSV import of regions
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $contents = file_get_contents($file->getPathname());
        
        // Handle different line endings
        $contents = str_replace("\r\n", "\n", $contents);
        $contents = str_replace("\r", "\n", $contents);
        
        $lines = explode("\n", $contents);
        $headers = str_getcsv(array_shift($lines));
        
        // Validate headers
        $requiredHeaders = ['name', 'type', 'level'];
        $missingHeaders = array_diff($requiredHeaders, $headers);
        
        if (!empty($missingHeaders)) {
            return response()->json([
                'message' => 'Missing required headers: ' . implode(', ', $missingHeaders),
                'required_headers' => $requiredHeaders,
                'provided_headers' => $headers,
            ], 422);
        }
        
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'created' => [],
        ];
        
        // Create a mapping of parent names to IDs
        $parentMapping = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($lines as $lineNumber => $line) {
                if (empty(trim($line))) {
                    continue;
                }
                
                $data = array_combine($headers, str_getcsv($line));
                
                // Skip if essential data is missing
                if (empty($data['name'])) {
                    continue;
                }
                
                // Process the row
                $rowResult = $this->processRow($data, $parentMapping, $lineNumber + 2);
                
                if ($rowResult['success']) {
                    $results['success']++;
                    $results['created'][] = $rowResult['region'];
                    
                    // Add to parent mapping for future rows
                    $parentMapping[strtolower($rowResult['region']['name'])] = $rowResult['region']['id'];
                } else {
                    $results['failed']++;
                    $results['errors'][] = $rowResult['error'];
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => "Import completed. {$results['success']} regions created, {$results['failed']} failed.",
                'results' => $results,
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Process a single row from the CSV
     */
    private function processRow($data, &$parentMapping, $lineNumber)
    {
        try {
            // Prepare data
            $regionData = [
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'level' => (int) $data['level'],
                'type' => $data['type'],
                'parent_id' => null,
            ];
            
            // Validate type matches level
            $expectedType = match($regionData['level']) {
                1 => 'state',
                2 => 'city',
                3 => 'neighborhood',
                default => 'custom'
            };
            
            if ($regionData['type'] !== $expectedType && !in_array($regionData['type'], ['county', 'district', 'custom'])) {
                $regionData['type'] = $expectedType;
            }
            
            // Handle parent relationship
            if (!empty($data['parent_name'])) {
                $parentKey = strtolower($data['parent_name']);
                
                if (isset($parentMapping[$parentKey])) {
                    $regionData['parent_id'] = $parentMapping[$parentKey];
                } else {
                    // Try to find parent in database
                    $parent = Region::where('name', $data['parent_name'])->first();
                    if ($parent) {
                        $regionData['parent_id'] = $parent->id;
                        $parentMapping[$parentKey] = $parent->id;
                    } else {
                        throw new \Exception("Parent region '{$data['parent_name']}' not found");
                    }
                }
            } elseif (!empty($data['parent_id'])) {
                $regionData['parent_id'] = (int) $data['parent_id'];
            }
            
            // Validate parent-child level relationship
            if ($regionData['parent_id']) {
                $parent = Region::find($regionData['parent_id']);
                if ($parent && $parent->level >= $regionData['level']) {
                    throw new \Exception("Parent region must be of a higher level than child");
                }
            } elseif ($regionData['level'] > 1) {
                throw new \Exception("Level {$regionData['level']} regions require a parent");
            }
            
            // Add optional fields
            $optionalFields = [
                'intro_text', 'meta_title', 'meta_description', 
                'is_featured', 'display_priority', 'cover_image'
            ];
            
            foreach ($optionalFields as $field) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    if ($field === 'is_featured') {
                        $regionData[$field] = in_array(strtolower($data[$field]), ['true', '1', 'yes']);
                    } elseif ($field === 'display_priority') {
                        $regionData[$field] = (int) $data[$field];
                    } else {
                        $regionData[$field] = $data[$field];
                    }
                }
            }
            
            // Check for duplicate slug
            $existingRegion = Region::where('slug', $regionData['slug'])
                ->where('level', $regionData['level'])
                ->first();
                
            if ($existingRegion) {
                // Generate unique slug
                $count = 2;
                $baseSlug = $regionData['slug'];
                do {
                    $regionData['slug'] = $baseSlug . '-' . $count;
                    $count++;
                } while (Region::where('slug', $regionData['slug'])->where('level', $regionData['level'])->exists());
            }
            
            // Create the region
            $region = Region::create($regionData);
            
            return [
                'success' => true,
                'region' => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'type' => $region->type,
                    'level' => $region->level,
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'line' => $lineNumber,
                    'name' => $data['name'] ?? 'Unknown',
                    'message' => $e->getMessage(),
                ]
            ];
        }
    }
    
    /**
     * Download sample CSV template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="regions_import_template.csv"',
        ];
        
        $columns = [
            'name',
            'type',
            'level',
            'parent_name',
            'parent_id',
            'slug',
            'intro_text',
            'meta_title',
            'meta_description',
            'is_featured',
            'display_priority',
            'cover_image'
        ];
        
        $sampleData = [
            ['California', 'state', '1', '', '', 'california', 'The Golden State', 'California - Directory', 'Explore California regions and places', 'true', '100', ''],
            ['Los Angeles', 'city', '2', 'California', '', 'los-angeles', 'City of Angels', 'Los Angeles, CA - Directory', 'Explore Los Angeles neighborhoods', 'true', '90', ''],
            ['Santa Monica', 'neighborhood', '3', 'Los Angeles', '', 'santa-monica', 'Beach city in LA', 'Santa Monica, Los Angeles - Directory', 'Explore Santa Monica area', 'false', '80', ''],
            ['San Francisco', 'city', '2', 'California', '', 'san-francisco', 'The City by the Bay', 'San Francisco, CA - Directory', 'Explore San Francisco neighborhoods', 'true', '95', ''],
            ['Mission District', 'neighborhood', '3', 'San Francisco', '', 'mission-district', 'Historic neighborhood', 'Mission District, San Francisco', 'Explore the Mission District', 'false', '70', ''],
        ];
        
        $callback = function() use ($columns, $sampleData) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, $columns);
            
            // Write sample data
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}