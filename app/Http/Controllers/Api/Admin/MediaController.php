<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudflareImage;
use App\Services\CloudflareImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MediaController extends Controller
{
    public function __construct(
        private CloudflareImageService $cloudflareService
    ) {}

    /**
     * Get paginated list of media with filters
     */
    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            
            // For filtering, we need to check if we have any filters
            $hasFilters = $request->filled('search') || 
                         $request->filled('context') || 
                         $request->filled('status') ||
                         $request->filled('entity_type');
            
            // Get Cloudflare images for current page
            // If we have filters, we need more images to filter from
            if ($hasFilters) {
                // Get more images when filtering (up to 500)
                $allImages = [];
                $maxPages = 5;
                for ($cfPage = 1; $cfPage <= $maxPages; $cfPage++) {
                    $batch = $this->cloudflareService->listImages($cfPage, 100);
                    if (empty($batch['images'])) {
                        break;
                    }
                    $allImages = array_merge($allImages, $batch['images']);
                    if (count($batch['images']) < 100) {
                        break;
                    }
                }
                $cloudflareImages = ['images' => $allImages];
            } else {
                // Without filters, just get the requested page
                $cloudflareImages = $this->cloudflareService->listImages($page, $perPage);
            }
            
            // Get tracked images from database
            $trackedImages = CloudflareImage::with(['user'])
                ->whereIn('cloudflare_id', array_column($cloudflareImages['images'] ?? [], 'id'))
                ->get()
                ->keyBy('cloudflare_id');
            
            // Merge Cloudflare data with database tracking
            $images = collect($cloudflareImages['images'] ?? [])->map(function ($cfImage) use ($trackedImages) {
                $tracked = $trackedImages->get($cfImage['id']);
                
                return [
                    'id' => $cfImage['id'],
                    'cloudflare_id' => $cfImage['id'],
                    'filename' => $cfImage['filename'] ?? 'Unknown',
                    'file_size' => $cfImage['meta']['size'] ?? null,
                    'width' => $cfImage['meta']['width'] ?? null,
                    'height' => $cfImage['meta']['height'] ?? null,
                    'uploaded_at' => $cfImage['uploaded'] ?? null,
                    'variants' => $cfImage['variants'] ?? [],
                    'requireSignedURLs' => $cfImage['requireSignedURLs'] ?? false,
                    
                    // Database tracked fields
                    'tracked_in_db' => $tracked !== null,
                    'context' => $tracked?->context,
                    'entity_type' => $tracked?->entity_type,
                    'entity_id' => $tracked?->entity_id,
                    'user' => $tracked?->user ? [
                        'id' => $tracked->user->id,
                        'name' => $tracked->user->name,
                        'avatar_url' => $tracked->user->avatar_cloudflare_id 
                            ? "https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/{$tracked->user->avatar_cloudflare_id}/public" 
                            : null,
                    ] : null,
                    'metadata' => array_merge($cfImage, ['db_metadata' => $tracked?->metadata]),
                ];
            });
            
            // Apply filters on the merged data
            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $images = $images->filter(function ($image) use ($search) {
                    return str_contains(strtolower($image['filename']), $search) ||
                           str_contains(strtolower($image['cloudflare_id']), $search);
                });
            }
            
            if ($request->filled('context')) {
                $images = $images->filter(fn($image) => $image['context'] === $request->context);
            }
            
            if ($request->filled('status')) {
                if ($request->status === 'orphaned') {
                    $images = $images->filter(fn($image) => !$image['tracked_in_db'] || !$image['entity_id']);
                } else if ($request->status === 'active') {
                    $images = $images->filter(fn($image) => $image['tracked_in_db'] && $image['entity_id']);
                }
            }
            
            // Sort by upload date
            $images = $images->sortByDesc('uploaded_at')->values();
            
            // Get total count from Cloudflare stats for accurate pagination
            $cfStats = $this->cloudflareService->getStats();
            $cloudflareTotal = $cfStats['count'] ?? $images->count();
            
            // Manual pagination of filtered results
            $total = $hasFilters ? $images->count() : $cloudflareTotal;
            
            // Only slice if we have filters (because we fetched all filtered results)
            // Without filters, Cloudflare already returned the correct page
            if ($hasFilters) {
                $images = $images->slice(($page - 1) * $perPage, $perPage)->values();
            }
            
            return response()->json([
                'data' => $images,
                'links' => $this->generatePaginationLinks($page, ceil($total / $perPage), $request->url()),
                'meta' => [
                    'current_page' => $page,
                    'from' => ($page - 1) * $perPage + 1,
                    'last_page' => ceil($total / $perPage),
                    'path' => $request->url(),
                    'per_page' => $perPage,
                    'to' => min($page * $perPage, $total),
                    'total' => $total,
                    'cloudflare_total' => $cloudflareTotal,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching media: ' . $e->getMessage());
            return response()->json([
                'data' => [],
                'links' => [],
                'meta' => [
                    'current_page' => 1,
                    'from' => null,
                    'last_page' => 1,
                    'path' => $request->url(),
                    'per_page' => 20,
                    'to' => null,
                    'total' => 0,
                ]
            ]);
        }
    }
    
    /**
     * Generate pagination links
     */
    private function generatePaginationLinks($currentPage, $lastPage, $url)
    {
        $links = [];
        
        // Previous
        $links[] = [
            'url' => $currentPage > 1 ? "{$url}?page=" . ($currentPage - 1) : null,
            'label' => '&laquo; Previous',
            'active' => false,
        ];
        
        // Page numbers
        for ($i = 1; $i <= $lastPage; $i++) {
            $links[] = [
                'url' => "{$url}?page={$i}",
                'label' => (string) $i,
                'active' => $i === $currentPage,
            ];
        }
        
        // Next
        $links[] = [
            'url' => $currentPage < $lastPage ? "{$url}?page=" . ($currentPage + 1) : null,
            'label' => 'Next &raquo;',
            'active' => false,
        ];
        
        return $links;
    }

    /**
     * Get media statistics
     */
    public function stats()
    {
        try {
            // Get fresh stats from Cloudflare
            $cfStats = $this->cloudflareService->getStats();
            
            // Get database stats
            $dbStats = [
                'tracked_images' => CloudflareImage::count(),
                'tracked_with_entities' => CloudflareImage::whereNotNull('entity_id')->count(),
                'users_with_uploads' => CloudflareImage::distinct('user_id')->whereNotNull('user_id')->count('user_id'),
                'contexts' => CloudflareImage::whereNotNull('context')->distinct('context')->count('context'),
            ];

            // Get specific counts by context and entity type
            $userImages = CloudflareImage::where(function($query) {
                $query->whereIn('context', ['avatar', 'cover'])
                      ->orWhere('entity_type', 'App\Models\User');
            })->count();

            $regionImages = CloudflareImage::where(function($query) {
                $query->where('context', 'region_cover')
                      ->orWhere('entity_type', 'App\Models\Region');
            })->count();

            $placeImages = CloudflareImage::where(function($query) {
                $query->whereIn('context', ['logo', 'gallery'])
                      ->orWhere('entity_type', 'App\Models\Place');
            })->count();

            $listImages = CloudflareImage::where(function($query) {
                $query->where('context', 'item')
                      ->orWhere('entity_type', 'App\Models\UserList');
            })->count();

            // Calculate storage estimate based on image count
            // Cloudflare charges $5 per 100,000 images/month
            $imageCount = $cfStats['count'] ?? 0;
            $costEstimate = ($imageCount / 100000) * 5;

            return response()->json([
                'total_images' => $cfStats['count'] ?? 0, // Total from Cloudflare
                'tracked_images' => $dbStats['tracked_images'], // Tracked in our DB
                'users_with_uploads' => $dbStats['users_with_uploads'],
                'contexts' => $dbStats['contexts'],
                'user_images' => $userImages,
                'region_images' => $regionImages,
                'place_images' => $placeImages,
                'list_images' => $listImages,
                'storage_used' => $cfStats['current']['size'] ?? 0, // May not be available
                'storage_quota' => $cfStats['allowed']['size'] ?? 500000000,
                'current' => $cfStats['current'] ?? ['count' => 0, 'size' => 0],
                'allowed' => $cfStats['allowed'] ?? ['count' => 100000, 'size' => 500000000],
                'cost_estimate' => round($costEstimate, 2),
                'billing_note' => 'Cloudflare charges $5 per 100,000 images/month',
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting media stats: ' . $e->getMessage());
            return response()->json([
                'total_images' => 0,
                'tracked_images' => 0,
                'users_with_uploads' => 0,
                'contexts' => 0,
                'user_images' => 0,
                'region_images' => 0,
                'place_images' => 0,
                'list_images' => 0,
                'storage_used' => 0,
                'storage_quota' => 500000000,
                'current' => ['count' => 0, 'size' => 0],
                'allowed' => ['count' => 100000, 'size' => 500000000],
                'cost_estimate' => 0,
                'billing_note' => 'Cloudflare charges $5 per 100,000 images/month',
            ]);
        }
    }

    /**
     * Delete a single image
     */
    public function destroy($cloudflareId)
    {
        Log::info('Single delete request received', ['id' => $cloudflareId]);
        
        try {
            // Check if the parameter is a database ID or cloudflare ID
            $image = null;
            $actualCloudflareId = $cloudflareId;
            
            // First try to find by database ID (numeric)
            if (is_numeric($cloudflareId)) {
                $image = CloudflareImage::find($cloudflareId);
                if ($image) {
                    $actualCloudflareId = $image->cloudflare_id;
                }
            }
            
            // If not found, try by cloudflare_id
            if (!$image) {
                $image = CloudflareImage::where('cloudflare_id', $cloudflareId)->first();
            }
            
            // Delete from Cloudflare (even if not in database)
            $this->cloudflareService->deleteImage($actualCloudflareId);

            // Delete from database if it exists
            if ($image) {
                $image->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete images
     */
    public function bulkDelete(Request $request)
    {
        Log::info('Bulk delete request received', ['ids' => $request->ids]);
        
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string'
        ]);

        $deletedCount = 0;
        $errors = [];

        foreach ($request->ids as $cloudflareId) {
            Log::info('Processing deletion for image', ['cloudflare_id' => $cloudflareId]);
            try {
                // Delete from Cloudflare first
                $this->cloudflareService->deleteImage($cloudflareId);
                
                // Delete from database if it exists
                $dbImage = CloudflareImage::where('cloudflare_id', $cloudflareId)->first();
                if ($dbImage) {
                    $dbImage->delete();
                }
                
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $cloudflareId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    }

    /**
     * Cleanup orphaned images
     */
    public function cleanup(Request $request)
    {
        $dryRun = $request->boolean('dry_run', true);

        // Find orphaned images (no entity association)
        $orphaned = CloudflareImage::whereNull('entity_id')
            ->orWhere(function ($q) {
                $q->whereNull('entity_type');
            })
            ->where('uploaded_at', '<', now()->subDays(7)) // Only cleanup images older than 7 days
            ->get();

        if ($dryRun) {
            return response()->json([
                'count' => $orphaned->count(),
                'total_size_bytes' => $orphaned->sum('file_size'),
                'images' => $orphaned->map(fn($img) => [
                    'id' => $img->id,
                    'filename' => $img->filename,
                    'size' => $img->file_size,
                    'uploaded_at' => $img->uploaded_at
                ])
            ]);
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($orphaned as $image) {
            try {
                // Delete from Cloudflare
                $this->cloudflareService->deleteImage($image->cloudflare_id);
                
                // Delete from database
                $image->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $image->cloudflare_id,
                    'filename' => $image->filename,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    }
}