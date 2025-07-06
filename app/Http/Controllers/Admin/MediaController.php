<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudflareImage;
use App\Models\UserList;
use App\Models\DirectoryEntry;
use App\Models\User;
use App\Services\CloudflareImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Exception;

class MediaController extends Controller
{
    public function __construct(
        private CloudflareImageService $cloudflareService
    ) {}

    /**
     * Display the media management dashboard
     */
    public function index(Request $request): Response
    {
        try {
            // Get filter parameters
            $filters = [
                'search' => $request->get('search'),
                'uploader_id' => $request->get('uploader_id'),
                'context' => $request->get('context'),
                'entity_type' => $request->get('entity_type'),
                'entity_id' => $request->get('entity_id'),
                'sort' => $request->get('sort', 'uploaded'),
                'order' => $request->get('order', 'desc'),
                'page' => $request->get('page', 1)
            ];

            // Get images with filtering
            $images = $this->getFilteredImages($filters);
            
            // Get enhanced storage stats
            $stats = $this->getEnhancedStats();
            
            // Get filter options for dropdowns
            $filterOptions = $this->getFilterOptions();
            
            return Inertia::render('Admin/Media/Index', [
                'images' => $images,
                'stats' => $stats,
                'filters' => $filters,
                'filterOptions' => $filterOptions
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load media dashboard: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return Inertia::render('Admin/Media/Index', [
                'images' => [
                    'images' => collect([]),
                    'pagination' => [
                        'page' => 1,
                        'per_page' => 50,
                        'count' => 0,
                        'total_count' => 0,
                        'total_pages' => 1
                    ]
                ],
                'stats' => [
                    'count' => 0,
                    'current' => ['size' => 0],
                    'allowed' => ['size' => 0]
                ],
                'error' => 'Failed to load media data: ' . $e->getMessage(),
                'filters' => [
                    'search' => $request->get('search'),
                    'sort' => $request->get('sort', 'uploaded'),
                    'order' => $request->get('order', 'desc')
                ]
            ]);
        }
    }

    /**
     * Get filtered images with enhanced database integration
     */
    private function getFilteredImages(array $filters): array
    {
        $page = $filters['page'] ?? 1;
        $perPage = 50;

        // Check if any database-specific filters are applied
        $hasDbFilters = !empty($filters['uploader_id']) || 
                       !empty($filters['context']) || 
                       !empty($filters['entity_type']) || 
                       !empty($filters['entity_id']);

        try {
            // If we have database-specific filters, use database filtering
            if ($hasDbFilters) {
                return $this->getFilteredDatabaseImages($filters, $page, $perPage);
            }

            // Otherwise, get all Cloudflare images with enhanced database info
            return $this->getCloudflareImagesWithDbInfo($filters, $page, $perPage);

        } catch (Exception $e) {
            Log::error('Failed to get filtered images: ' . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);
            
            // Fallback to original method
            return $this->getCloudflareImages($page, $perPage);
        }
    }

    /**
     * Get filtered images from database only
     */
    private function getFilteredDatabaseImages(array $filters, int $page, int $perPage): array
    {
        $query = CloudflareImage::with(['user', 'entity'])
            ->orderBy('created_at', $filters['order'] === 'asc' ? 'asc' : 'desc');

        // Apply filters
        if (!empty($filters['uploader_id'])) {
            $query->where('user_id', $filters['uploader_id']);
        }

        if (!empty($filters['context'])) {
            $query->where('context', $filters['context']);
        }

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (!empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('filename', 'ILIKE', '%' . $filters['search'] . '%')
                  ->orWhereHas('user', function($userQuery) use ($filters) {
                      $userQuery->where('name', 'ILIKE', '%' . $filters['search'] . '%')
                               ->orWhere('email', 'ILIKE', '%' . $filters['search'] . '%');
                  });
            });
        }

        // Get paginated results
        $total = $query->count();
        $dbImages = $query->offset(($page - 1) * $perPage)
                         ->limit($perPage)
                         ->get();

        $cloudflareImages = [];
        foreach ($dbImages as $dbImage) {
            $cloudflareImages[] = [
                'id' => $dbImage->cloudflare_id,
                'filename' => $dbImage->filename,
                'uploaded' => $dbImage->uploaded_at ? $dbImage->uploaded_at->toISOString() : $dbImage->created_at->toISOString(),
                'meta' => $dbImage->metadata ?? [],
                'variants' => [],
                'url' => $this->cloudflareService->getImageUrl($dbImage->cloudflare_id),
                'thumbnail' => $this->cloudflareService->getImageUrl($dbImage->cloudflare_id, ['width' => 200, 'height' => 200, 'fit' => 'cover']),
                'size_bytes' => $dbImage->file_size,
                'dimensions' => [
                    'width' => $dbImage->width,
                    'height' => $dbImage->height
                ],
                'tracked_in_db' => true,
                'user' => $dbImage->user ? [
                    'id' => $dbImage->user->id,
                    'name' => $dbImage->user->name,
                    'email' => $dbImage->user->email,
                ] : null,
                'context' => $dbImage->context,
                'entity_type' => $dbImage->entity_type,
                'entity_id' => $dbImage->entity_id,
                'entity' => $dbImage->entity ? [
                    'type' => $dbImage->entity_type,
                    'id' => $dbImage->entity_id,
                    'name' => $this->getEntityName($dbImage->entity)
                ] : null,
                'database_created_at' => $dbImage->created_at->toISOString(),
            ];
        }

        return [
            'images' => collect($cloudflareImages),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'count' => count($cloudflareImages),
                'total_count' => $total,
                'total_pages' => max(1, ceil($total / $perPage))
            ]
        ];
    }

    /**
     * Get Cloudflare images with enhanced database information
     */
    private function getCloudflareImagesWithDbInfo(array $filters, int $page, int $perPage): array
    {
        // Get images from Cloudflare API
        $cloudflareResult = $this->getCloudflareImages($page, $perPage);
        
        // Get database records for these images to include user information
        $cloudflareIds = $cloudflareResult['images']->pluck('id')->filter()->toArray();
        $dbImages = CloudflareImage::whereIn('cloudflare_id', $cloudflareIds)
            ->with('user', 'entity')
            ->get()
            ->keyBy('cloudflare_id');

        // Apply search filter if provided
        if (!empty($filters['search'])) {
            $searchTerm = strtolower($filters['search']);
            $cloudflareResult['images'] = $cloudflareResult['images']->filter(function($image) use ($searchTerm, $dbImages) {
                $filename = strtolower($image['filename'] ?? '');
                $imageId = strtolower($image['id'] ?? '');
                $dbImage = $dbImages->get($image['id']);
                $userName = $dbImage && $dbImage->user ? strtolower($dbImage->user->name) : '';
                
                return str_contains($filename, $searchTerm) || 
                       str_contains($imageId, $searchTerm) ||
                       str_contains($userName, $searchTerm);
            });
        }

        // Enhance images with database information
        $enhancedImages = $cloudflareResult['images']->map(function ($image) use ($dbImages) {
            $imageId = $image['id'] ?? 'unknown';
            $dbImage = $dbImages->get($imageId);
            
            return array_merge($image, [
                'tracked_in_db' => $dbImage !== null,
                'user' => $dbImage && $dbImage->user ? [
                    'id' => $dbImage->user->id,
                    'name' => $dbImage->user->name,
                    'email' => $dbImage->user->email,
                ] : null,
                'context' => $dbImage?->context,
                'entity_type' => $dbImage?->entity_type,
                'entity_id' => $dbImage?->entity_id,
                'entity' => $dbImage && $dbImage->entity ? [
                    'type' => $dbImage->entity_type,
                    'id' => $dbImage->entity_id,
                    'name' => $this->getEntityName($dbImage->entity)
                ] : null,
                'database_created_at' => $dbImage?->created_at?->toISOString(),
            ]);
        });

        return [
            'images' => $enhancedImages,
            'pagination' => $cloudflareResult['pagination']
        ];
    }

    /**
     * Get enhanced storage stats with live Cloudflare data
     */
    private function getEnhancedStats(): array
    {
        try {
            // Get database stats first (always available)
            $dbStats = [
                'tracked_images_count' => CloudflareImage::count(),
                'users_with_uploads' => CloudflareImage::distinct('user_id')->whereNotNull('user_id')->count(),
                'total_tracked_size' => CloudflareImage::sum('file_size') ?: 0,
            ];

            // Try to get live stats from Cloudflare
            $cloudflareStats = [];
            $cloudflareAvailable = false;
            
            try {
                $cloudflareStats = $this->cloudflareService->getStats();
                $cloudflareAvailable = !empty($cloudflareStats);
                
                // Log the raw response for debugging
                Log::info('Cloudflare stats response: ' . json_encode($cloudflareStats));
            } catch (Exception $cfException) {
                Log::warning('Cloudflare stats API failed: ' . $cfException->getMessage());
            }

            // Extract counts safely
            $imageCount = $dbStats['tracked_images_count']; // Default to database count
            $currentSize = $dbStats['total_tracked_size']; // Default to database size
            $allowedSize = 1073741824; // 1GB fallback

            if ($cloudflareAvailable && is_array($cloudflareStats)) {
                // Handle various possible response structures
                if (isset($cloudflareStats['count'])) {
                    if (is_numeric($cloudflareStats['count'])) {
                        $imageCount = (int) $cloudflareStats['count'];
                    } elseif (is_array($cloudflareStats['count']) && isset($cloudflareStats['count']['current'])) {
                        $imageCount = (int) $cloudflareStats['count']['current'];
                    }
                }
                
                // Cloudflare stats API doesn't provide storage size info
                // Use database calculated size as it's more accurate anyway
                $currentSize = $dbStats['total_tracked_size'];
                
                // Set a reasonable storage limit
                if (isset($cloudflareStats['count']['allowed']) && is_numeric($cloudflareStats['count']['allowed'])) {
                    $allowedImages = (int) $cloudflareStats['count']['allowed'];
                    // Cloudflare typically has generous storage limits, estimate conservatively
                    $allowedSize = min($allowedImages * 1048576, 107374182400); // 1MB per image, max 100GB
                } else {
                    $allowedSize = 107374182400; // 100GB default for Cloudflare
                }
            }

            return [
                'count' => $imageCount,
                'current' => [
                    'size' => $currentSize
                ],
                'allowed' => [
                    'size' => $allowedSize
                ],
                'database' => $dbStats,
                'cloudflare_api_available' => $cloudflareAvailable
            ];

        } catch (Exception $e) {
            Log::error('Failed to get enhanced stats: ' . $e->getMessage());
            
            // Fallback to database-only stats
            $dbStats = [
                'tracked_images_count' => CloudflareImage::count(),
                'users_with_uploads' => CloudflareImage::distinct('user_id')->whereNotNull('user_id')->count(),
                'total_tracked_size' => CloudflareImage::sum('file_size') ?: 0,
            ];

            return [
                'count' => (int) $dbStats['tracked_images_count'],
                'current' => ['size' => (int) $dbStats['total_tracked_size']],
                'allowed' => ['size' => 1073741824], // 1GB fallback
                'database' => $dbStats,
                'cloudflare_api_available' => false
            ];
        }
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions(): array
    {
        try {
            return [
                'uploaders' => CloudflareImage::with('user')
                    ->whereNotNull('user_id')
                    ->get()
                    ->unique('user_id')
                    ->map(function ($image) {
                        return [
                            'id' => $image->user_id,
                            'name' => $image->user->name ?? 'Unknown User',
                            'email' => $image->user->email ?? ''
                        ];
                    })
                    ->values(),
                'contexts' => CloudflareImage::distinct('context')
                    ->whereNotNull('context')
                    ->pluck('context')
                    ->sort()
                    ->values(),
                'entity_types' => CloudflareImage::distinct('entity_type')
                    ->whereNotNull('entity_type')
                    ->pluck('entity_type')
                    ->sort()
                    ->values(),
                'places' => CloudflareImage::where('entity_type', 'App\\Models\\DirectoryEntry')
                    ->with('entity')
                    ->get()
                    ->map(function ($image) {
                        return [
                            'id' => $image->entity_id,
                            'name' => $this->getEntityName($image->entity)
                        ];
                    })
                    ->unique('id')
                    ->values()
            ];
        } catch (Exception $e) {
            Log::error('Failed to get filter options: ' . $e->getMessage());
            return [
                'uploaders' => [],
                'contexts' => [],
                'entity_types' => [],
                'places' => []
            ];
        }
    }

    /**
     * Get images from Cloudflare Images API
     */
    private function getCloudflareImages(int $page = 1, int $perPage = 50): array
    {
        try {
            $accountId = config('services.cloudflare.account_id');
            $apiToken = config('services.cloudflare.images_token');
            $email = config('services.cloudflare.email');
            
            // First verify credentials are set
            if (empty($accountId) || empty($apiToken) || empty($email)) {
                throw new Exception('Cloudflare credentials not configured properly. Check .env file.');
            }

            $response = Http::withHeaders([
                'X-Auth-Email' => $email,
                'X-Auth-Key' => $apiToken,
            ])->timeout(30)->get("https://api.cloudflare.com/client/v4/accounts/{$accountId}/images/v1", [
                'page' => $page,
                'per_page' => $perPage
            ]);

            // Log only if there are issues
            if (!$response->successful()) {
                Log::error('Cloudflare Images API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            if (!$response->successful()) {
                throw new Exception('Failed to fetch images from Cloudflare: HTTP ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data || !isset($data['success'])) {
                throw new Exception('Invalid response format from Cloudflare API');
            }
            
            if (!$data['success']) {
                $errors = isset($data['errors']) && is_array($data['errors']) 
                    ? implode(', ', array_column($data['errors'], 'message'))
                    : 'Unknown error';
                throw new Exception('Cloudflare API returned error: ' . $errors);
            }

            // Parse the Cloudflare API response
            $images = [];
            $pagination = [
                'page' => $page,
                'per_page' => $perPage,
                'count' => 0,
                'total_count' => 0,
                'total_pages' => 1
            ];

            if (isset($data['result'])) {
                if (isset($data['result']['images']) && is_array($data['result']['images'])) {
                    // Current Cloudflare API structure: result.images array
                    $images = $data['result']['images'];
                } elseif (is_array($data['result']) && isset($data['result'][0]['id'])) {
                    // Alternative structure: direct array of images
                    $images = $data['result'];
                }
                
                // Set pagination info
                $pagination['count'] = count($images);
                $pagination['total_count'] = count($images);
                $pagination['total_pages'] = max(1, ceil(count($images) / $perPage));
            }

            // Get database records for these images to include user information
            $cloudflareIds = collect($images)->pluck('id')->filter()->toArray();
            $dbImages = CloudflareImage::whereIn('cloudflare_id', $cloudflareIds)
                ->with('user', 'entity')
                ->get()
                ->keyBy('cloudflare_id');

            return [
                'images' => collect($images)->map(function ($image) use ($dbImages) {
                    $imageId = $image['id'] ?? 'unknown';
                    $dbImage = $dbImages->get($imageId);
                    
                    return [
                        'id' => $imageId,
                        'filename' => $image['filename'] ?? 'unknown.jpg',
                        'uploaded' => $image['uploaded'] ?? now()->toISOString(),
                        'meta' => $image['meta'] ?? [],
                        'variants' => $image['variants'] ?? [],
                        // Generate public URL
                        'url' => $this->cloudflareService->getImageUrl($imageId),
                        'thumbnail' => $this->cloudflareService->getImageUrl($imageId, ['width' => 200, 'height' => 200, 'fit' => 'cover']),
                        'size_bytes' => $image['meta']['size'] ?? null,
                        'dimensions' => [
                            'width' => $image['meta']['width'] ?? null,
                            'height' => $image['meta']['height'] ?? null
                        ],
                        // Database information
                        'tracked_in_db' => $dbImage !== null,
                        'user' => $dbImage ? [
                            'id' => $dbImage->user?->id,
                            'name' => $dbImage->user?->name,
                            'email' => $dbImage->user?->email,
                        ] : null,
                        'context' => $dbImage?->context,
                        'entity_type' => $dbImage?->entity_type,
                        'entity_id' => $dbImage?->entity_id,
                        'entity' => $dbImage?->entity ? [
                            'type' => $dbImage->entity_type,
                            'id' => $dbImage->entity_id,
                            'name' => $this->getEntityName($dbImage->entity)
                        ] : null,
                        'database_created_at' => $dbImage?->created_at?->toISOString(),
                    ];
                }),
                'pagination' => $pagination
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch Cloudflare images: ' . $e->getMessage(), [
                'exception' => $e,
                'page' => $page,
                'per_page' => $perPage
            ]);
            throw $e;
        }
    }

    /**
     * Delete an image
     */
    public function destroy(Request $request, string $imageId): JsonResponse
    {
        try {
            // Delete from Cloudflare
            $deleted = $this->cloudflareService->deleteImage($imageId);
            
            if ($deleted) {
                // Also delete from database if exists
                CloudflareImage::where('cloudflare_id', $imageId)->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete image'
                ], 500);
            }
        } catch (Exception $e) {
            Log::error('Failed to delete image: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete images
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'image_ids' => 'required|array',
            'image_ids.*' => 'required|string'
        ]);

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($request->image_ids as $imageId) {
            try {
                $deleted = $this->cloudflareService->deleteImage($imageId);
                if ($deleted) {
                    // Also delete from database if exists
                    CloudflareImage::where('cloudflare_id', $imageId)->delete();
                    $successCount++;
                } else {
                    $failureCount++;
                    $errors[] = "Failed to delete image: {$imageId}";
                }
            } catch (Exception $e) {
                $failureCount++;
                $errors[] = "Error deleting {$imageId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Deleted {$successCount} images successfully" . ($failureCount > 0 ? ", {$failureCount} failed" : ""),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => $errors
        ]);
    }

    /**
     * Get detailed image information
     */
    public function show(Request $request, string $imageId): JsonResponse
    {
        try {
            $accountId = config('services.cloudflare.account_id');
            $apiToken = config('services.cloudflare.images_token');
            $email = config('services.cloudflare.email');
            
            $response = Http::withHeaders([
                'X-Auth-Email' => $email,
                'X-Auth-Key' => $apiToken,
            ])->get("https://api.cloudflare.com/client/v4/accounts/{$accountId}/images/v1/{$imageId}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            $data = $response->json();
            
            if (!$data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch image details'
                ], 500);
            }

            $image = $data['result'];
            
            return response()->json([
                'success' => true,
                'image' => [
                    'id' => $image['id'],
                    'filename' => $image['filename'],
                    'uploaded' => $image['uploaded'],
                    'meta' => $image['meta'] ?? [],
                    'variants' => $image['variants'] ?? [],
                    'url' => $this->cloudflareService->getImageUrl($image['id']),
                    'thumbnail' => $this->cloudflareService->getImageUrl($image['id'], ['width' => 400, 'height' => 400, 'fit' => 'cover']),
                    'size_bytes' => $image['meta']['size'] ?? null,
                    'dimensions' => [
                        'width' => $image['meta']['width'] ?? null,
                        'height' => $image['meta']['height'] ?? null
                    ]
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch image details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch image details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup unused images (images not referenced in database)
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            // Get all images from Cloudflare
            $allImages = $this->getAllCloudflareImages();
            
            // Get all image URLs/IDs referenced in the database
            $referencedImages = $this->getReferencedImages();
            
            // Find unused images
            $unusedImages = collect($allImages)->filter(function ($image) use ($referencedImages) {
                return !in_array($image['id'], $referencedImages);
            });

            if ($request->get('dry_run', true)) {
                // Just return what would be deleted
                return response()->json([
                    'success' => true,
                    'dry_run' => true,
                    'unused_images' => $unusedImages->values(),
                    'count' => $unusedImages->count(),
                    'total_size_bytes' => $unusedImages->sum('size_bytes'),
                    'message' => "Found {$unusedImages->count()} unused images"
                ]);
            } else {
                // Actually delete the unused images
                $deletedCount = 0;
                $errors = [];

                foreach ($unusedImages as $image) {
                    try {
                        $deleted = $this->cloudflareService->deleteImage($image['id']);
                        if ($deleted) {
                            // Also delete from database if exists
                            CloudflareImage::where('cloudflare_id', $image['id'])->delete();
                            $deletedCount++;
                        } else {
                            $errors[] = "Failed to delete image: {$image['id']}";
                        }
                    } catch (Exception $e) {
                        $errors[] = "Error deleting {$image['id']}: " . $e->getMessage();
                    }
                }

                return response()->json([
                    'success' => true,
                    'dry_run' => false,
                    'deleted_count' => $deletedCount,
                    'total_found' => $unusedImages->count(),
                    'errors' => $errors,
                    'message' => "Deleted {$deletedCount} unused images"
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to cleanup images: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all images from Cloudflare (paginated)
     */
    private function getAllCloudflareImages(): array
    {
        $allImages = [];
        $page = 1;
        $perPage = 100;

        do {
            $result = $this->getCloudflareImages($page, $perPage);
            $allImages = array_merge($allImages, $result['images']->toArray());
            $page++;
        } while ($result['pagination']['page'] < $result['pagination']['total_pages']);

        return $allImages;
    }

    /**
     * Get all image IDs referenced in the database
     */
    private function getReferencedImages(): array
    {
        $referencedImages = [];

        // Get all image IDs tracked in our cloudflare_images table that have entity associations
        $trackedImages = CloudflareImage::whereNotNull('entity_type')
            ->whereNotNull('entity_id')
            ->pluck('cloudflare_id')
            ->toArray();
        
        $referencedImages = array_merge($referencedImages, $trackedImages);

        // Extract image IDs from various database fields
        // Add tables and columns that reference Cloudflare images
        $imageSources = [
            ['table' => 'directory_entries', 'columns' => ['logo_url', 'cover_image_url', 'gallery_images']],
            ['table' => 'lists', 'columns' => ['featured_image']],
            ['table' => 'users', 'columns' => ['avatar']],
        ];

        foreach ($imageSources as $source) {
            foreach ($source['columns'] as $column) {
                $query = \DB::table($source['table'])->whereNotNull($column);
                
                // Handle different column types appropriately  
                if ($column === 'gallery_images') {
                    // For JSON columns, use PostgreSQL JSON operators
                    $query->whereRaw("({$column})::text != '[]'")
                          ->whereRaw("({$column})::text != 'null'");
                } else {
                    // For string columns, check not empty
                    $query->where($column, '!=', '');
                }
                
                $results = $query->pluck($column);

                foreach ($results as $value) {
                    if (is_string($value) && str_contains($value, 'imagedelivery.net')) {
                        // Extract image ID from Cloudflare URL
                        $imageId = $this->extractImageIdFromUrl($value);
                        if ($imageId) {
                            $referencedImages[] = $imageId;
                        }
                    } elseif (is_string($value) && str_starts_with($value, '[')) {
                        // Handle JSON arrays (like gallery_images)
                        $urls = json_decode($value, true);
                        if (is_array($urls)) {
                            foreach ($urls as $url) {
                                if (is_string($url) && str_contains($url, 'imagedelivery.net')) {
                                    $imageId = $this->extractImageIdFromUrl($url);
                                    if ($imageId) {
                                        $referencedImages[] = $imageId;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_unique($referencedImages);
    }

    /**
     * Extract Cloudflare image ID from URL
     */
    private function extractImageIdFromUrl(string $url): ?string
    {
        // Cloudflare URLs format: https://imagedelivery.net/accountHash/imageId/variant
        if (preg_match('/imagedelivery\.net\/[^\/]+\/([^\/]+)\//', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get a friendly name for an entity
     */
    private function getEntityName($entity): string
    {
        if (!$entity) {
            return 'Unknown';
        }

        // Handle different entity types
        switch (get_class($entity)) {
            case 'App\\Models\\DirectoryEntry':
                return $entity->title ?? 'Unnamed Entry';
            case 'App\\Models\\UserList':
                return $entity->name ?? 'Unnamed List';
            case 'App\\Models\\User':
                return $entity->name ?? 'User';
            default:
                return class_basename($entity) . ' #' . $entity->id;
        }
    }
}