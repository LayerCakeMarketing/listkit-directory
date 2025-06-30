<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DirectoryEntry;
use App\Models\Location;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class BulkImportController extends Controller
{
    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'image_folder' => 'required|string', // Path to images folder
        ]);

        try {
            $csvFile = $request->file('csv_file');
            $imageFolder = $request->input('image_folder');
            
            // Validate image folder exists
            if (!Storage::disk('public')->exists($imageFolder)) {
                return response()->json([
                    'success' => false,
                    'message' => "Image folder '{$imageFolder}' not found in storage/app/public/"
                ], 400);
            }

            // Parse CSV
            $csvData = $this->parseCsv($csvFile);
            
            if (empty($csvData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file is empty or invalid'
                ], 400);
            }

            // Process entries
            $results = $this->processEntries($csvData, $imageFolder);

            return response()->json([
                'success' => true,
                'message' => 'Bulk import completed',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'title',
            'description',
            'type',
            'category_name',
            'email',
            'phone',
            'website_url',
            'facebook_url',
            'instagram_handle',
            'twitter_handle',
            'youtube_channel',
            'price_range',
            'takes_reservations',
            'accepts_credit_cards',
            'wifi_available',
            'pet_friendly',
            'parking_options',
            'wheelchair_accessible',
            'outdoor_seating',
            'kid_friendly',
            'logo_filename',
            'cover_image_filename',
            'gallery_filenames',
            'address_line1',
            'address_line2',
            'city',
            'state',
            'zip_code',
            'country',
            'cross_streets',
            'neighborhood',
            'latitude',
            'longitude',
            'tags',
            'temporarily_closed',
            'open_24_7'
        ];

        $sampleData = [
            'Example Restaurant',
            'A wonderful local restaurant serving fresh, organic meals.',
            'physical_location',
            'Restaurants',
            'info@example.com',
            '555-123-4567',
            'https://example.com',
            'https://facebook.com/example',
            '@example',
            '@example',
            'https://youtube.com/c/example',
            '$$',
            'true',
            'true',
            'true',
            'true',
            'street',
            'true',
            'true',
            'true',
            'restaurant-logo.jpg',
            'restaurant-cover.jpg',
            'interior.jpg,food1.jpg,food2.jpg',
            '123 Main Street',
            'Suite 200',
            'Anytown',
            'CA',
            '90210',
            'US',
            'Corner of Main and 1st',
            'Downtown',
            '34.0522',
            '-118.2437',
            'restaurant,food,dining,organic',
            'false',
            'false'
        ];

        $csvContent = implode(',', $headers) . "\n";
        $csvContent .= '"' . implode('","', $sampleData) . '"';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="directory_entries_template.csv"'
        ]);
    }

    private function parseCsv($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        if ($handle === false) {
            throw new \Exception('Unable to read CSV file');
        }

        $headers = fgetcsv($handle);
        
        if ($headers === false) {
            fclose($handle);
            throw new \Exception('Invalid CSV format - no headers found');
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }

        fclose($handle);
        return $data;
    }

    private function processEntries($csvData, $imageFolder)
    {
        $results = [
            'total' => count($csvData),
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($csvData as $index => $row) {
            try {
                DB::beginTransaction();
                
                $this->processEntry($row, $imageFolder, $index + 2); // +2 for 1-based + header row
                
                DB::commit();
                $results['successful']++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $index + 2,
                    'title' => $row['title'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    private function processEntry($row, $imageFolder, $rowNumber)
    {
        // Find or validate category - make optional
        $category = null;
        if (!empty($row['category_name'])) {
            $category = Category::where('name', 'like', '%' . $row['category_name'] . '%')->first();
            // Don't throw error if category not found, just use null
        }

        // Process images
        $logoUrl = $this->processImage($row['logo_filename'] ?? '', $imageFolder, 'logo');
        $coverImageUrl = $this->processImage($row['cover_image_filename'] ?? '', $imageFolder, 'cover');
        $galleryImages = $this->processGalleryImages($row['gallery_filenames'] ?? '', $imageFolder);

        // Validate required field
        if (empty($row['title'])) {
            throw new \Exception("Title is required");
        }

        // Create directory entry
        $entryData = [
            'title' => $row['title'],
            'description' => $this->emptyToNull($row['description'] ?? ''),
            'type' => !empty($row['type']) ? $row['type'] : 'physical_location',
            'category_id' => $category ? $category->id : null,
            'email' => $this->emptyToNull($row['email'] ?? ''),
            'phone' => $this->emptyToNull($row['phone'] ?? ''),
            'website_url' => $this->emptyToNull($row['website_url'] ?? ''),
            'facebook_url' => $this->emptyToNull($row['facebook_url'] ?? ''),
            'instagram_handle' => $this->emptyToNull($row['instagram_handle'] ?? ''),
            'twitter_handle' => $this->emptyToNull($row['twitter_handle'] ?? ''),
            'youtube_channel' => $this->emptyToNull($row['youtube_channel'] ?? ''),
            'price_range' => $this->validatePriceRange($row['price_range'] ?? ''),
            'takes_reservations' => $this->parseBool($row['takes_reservations'] ?? ''),
            'accepts_credit_cards' => $this->parseBool($row['accepts_credit_cards'] ?? ''),
            'wifi_available' => $this->parseBool($row['wifi_available'] ?? ''),
            'pet_friendly' => $this->parseBool($row['pet_friendly'] ?? ''),
            'parking_options' => $this->validateParkingOption($row['parking_options'] ?? ''),
            'wheelchair_accessible' => $this->parseBool($row['wheelchair_accessible'] ?? ''),
            'outdoor_seating' => $this->parseBool($row['outdoor_seating'] ?? ''),
            'kid_friendly' => $this->parseBool($row['kid_friendly'] ?? ''),
            'logo_url' => $logoUrl,
            'cover_image_url' => $coverImageUrl,
            'gallery_images' => $galleryImages,
            'tags' => !empty($row['tags']) ? explode(',', trim($row['tags'])) : [],
            'temporarily_closed' => $this->parseBool($row['temporarily_closed'] ?? ''),
            'open_24_7' => $this->parseBool($row['open_24_7'] ?? ''),
            'cross_streets' => $this->emptyToNull($row['cross_streets'] ?? ''),
            'neighborhood' => $this->emptyToNull($row['neighborhood'] ?? ''),
            'created_by_user_id' => auth()->id(),
            'status' => 'published',
            'published_at' => now(),
        ];

        $entry = DirectoryEntry::create($entryData);

        // Create location if address provided
        if (!empty($row['address_line1']) && in_array($entry->type, ['physical_location', 'event'])) {
            Location::create([
                'directory_entry_id' => $entry->id,
                'address_line1' => $row['address_line1'],
                'address_line2' => $this->emptyToNull($row['address_line2'] ?? ''),
                'city' => $this->emptyToNull($row['city'] ?? '') ?: 'Unknown',
                'state' => $this->emptyToNull($row['state'] ?? '') ?: 'Unknown',
                'zip_code' => $this->emptyToNull($row['zip_code'] ?? ''),
                'country' => $this->emptyToNull($row['country'] ?? '') ?: 'US',
                'cross_streets' => $this->emptyToNull($row['cross_streets'] ?? ''),
                'neighborhood' => $this->emptyToNull($row['neighborhood'] ?? ''),
                // Use provided coordinates or default to 0
                'latitude' => !empty($row['latitude']) ? $row['latitude'] : '0.000000',
                'longitude' => !empty($row['longitude']) ? $row['longitude'] : '0.000000',
            ]);
        }

        return $entry;
    }

    private function processImage($filename, $imageFolder, $type)
    {
        if (empty($filename)) {
            return null;
        }

        $sourcePath = $imageFolder . '/' . $filename;
        
        if (!Storage::disk('public')->exists($sourcePath)) {
            throw new \Exception("Image file '{$filename}' not found in {$imageFolder}");
        }

        // Generate new filename for processed image
        $newFilename = Str::random(40) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $destinationPath = "directory-entries/{$type}s/{$newFilename}";

        // Process and resize image
        $manager = new ImageManager(new Driver());
        $imageContent = Storage::disk('public')->get($sourcePath);
        $image = $manager->read($imageContent);

        // Apply size constraints based on type
        $constraints = $this->getImageConstraints($type);
        if ($constraints['max_width'] || $constraints['max_height']) {
            $image->scaleDown($constraints['max_width'], $constraints['max_height']);
        }

        // Save processed image
        Storage::disk('public')->put($destinationPath, $image->toJpeg());

        return Storage::disk('public')->url($destinationPath);
    }

    private function processGalleryImages($filenames, $imageFolder)
    {
        if (empty($filenames)) {
            return [];
        }

        $filenameArray = array_map('trim', explode(',', $filenames));
        $galleryUrls = [];

        foreach ($filenameArray as $filename) {
            if (!empty($filename)) {
                try {
                    $url = $this->processImage($filename, $imageFolder, 'gallery');
                    if ($url) {
                        $galleryUrls[] = $url;
                    }
                } catch (\Exception $e) {
                    // Log warning but don't fail the entire entry
                    \Log::warning("Gallery image failed: {$filename} - " . $e->getMessage());
                }
            }
        }

        return $galleryUrls;
    }

    private function getImageConstraints($type)
    {
        $constraints = [
            'logo' => ['max_width' => 400, 'max_height' => 400],
            'cover' => ['max_width' => 1920, 'max_height' => 1080],
            'gallery' => ['max_width' => 1200, 'max_height' => 800],
        ];

        return $constraints[$type] ?? $constraints['gallery'];
    }

    private function parseBool($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes', 'y']) ? true : false;
    }

    private function validateParkingOption($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $value = strtolower(trim($value));
        $allowedOptions = ['street', 'lot', 'valet', 'none'];
        
        return in_array($value, $allowedOptions) ? $value : null;
    }

    private function validatePriceRange($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $value = trim($value);
        $allowedRanges = ['$', '$$', '$$$', '$$$$'];
        
        return in_array($value, $allowedRanges) ? $value : null;
    }

    private function emptyToNull($value)
    {
        return empty(trim($value)) ? null : trim($value);
    }
}