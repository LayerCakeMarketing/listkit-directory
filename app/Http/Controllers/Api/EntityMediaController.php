<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CloudflareImage;
use App\Services\CloudflareImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EntityMediaController extends Controller
{
    public function __construct(
        private CloudflareImageService $cloudflareService
    ) {}

    /**
     * Get media for a specific entity
     */
    public function getEntityMedia(Request $request, string $entityType, $entityId)
    {
        try {
            // Handle direct model class names from frontend
            if (str_contains($entityType, 'App\\Models\\')) {
                $modelClass = $entityType;
            } else {
                // Validate entity type
                $validTypes = ['user', 'list', 'place', 'region', 'channel'];
                if (!in_array(strtolower($entityType), $validTypes)) {
                    return response()->json(['error' => 'Invalid entity type'], 400);
                }

                // Map frontend entity types to model class names
                $entityTypeMap = [
                    'user' => 'App\Models\User',
                    'list' => 'App\Models\UserList',
                    'place' => 'App\Models\Place',
                    'region' => 'App\Models\Region',
                    'channel' => 'App\Models\Channel',
                ];
                
                $modelClass = $entityTypeMap[strtolower($entityType)];
            }

            // Check if entity exists
            if (!class_exists($modelClass) || !$modelClass::find($entityId)) {
                return response()->json(['error' => 'Entity not found'], 404);
            }

            // Cache key for this entity's media
            $cacheKey = "entity_media_{$entityType}_{$entityId}";
            
            // Try to get from cache first
            $media = Cache::remember($cacheKey, 300, function () use ($modelClass, $entityId) {
                // First, get tracked images from database
                $trackedImages = CloudflareImage::where('entity_type', $modelClass)
                    ->where('entity_id', $entityId)
                    ->with('user')
                    ->get();

                // Get all images from Cloudflare to find any with matching metadata
                $allCloudflareImages = $this->fetchAllCloudflareImages();
                
                // Filter images by metadata
                $cloudflareFiltered = collect($allCloudflareImages)->filter(function ($image) use ($entityId) {
                    $metadata = $image['metadata'] ?? [];
                    return isset($metadata['entity_id']) && $metadata['entity_id'] == $entityId;
                });

                // Merge and deduplicate
                $mergedImages = $this->mergeImageData($trackedImages, $cloudflareFiltered);
                
                return $mergedImages;
            });

            return response()->json([
                'success' => true,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'images' => $media,
                'total' => count($media)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching entity media: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch media',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch all images from Cloudflare (with pagination)
     */
    private function fetchAllCloudflareImages(): array
    {
        $allImages = [];
        $page = 1;
        $perPage = 100;

        // Fetch up to 500 images to avoid performance issues
        while ($page <= 5) {
            $batch = $this->cloudflareService->listImages($page, $perPage);
            
            if (empty($batch['images'])) {
                break;
            }

            $allImages = array_merge($allImages, $batch['images']);
            
            if (count($batch['images']) < $perPage) {
                break;
            }
            
            $page++;
        }

        return $allImages;
    }

    /**
     * Merge tracked images with Cloudflare data
     */
    private function mergeImageData($trackedImages, $cloudflareImages): array
    {
        $merged = [];
        $processedIds = [];

        // Add tracked images first
        foreach ($trackedImages as $tracked) {
            $processedIds[] = $tracked->cloudflare_id;
            $merged[] = [
                'id' => $tracked->cloudflare_id,
                'filename' => $tracked->filename,
                'context' => $tracked->context,
                'uploaded_at' => $tracked->uploaded_at,
                'file_size' => $tracked->file_size,
                'width' => $tracked->width,
                'height' => $tracked->height,
                'url' => $tracked->url,
                'thumbnail' => $tracked->thumbnail,
                'variants' => ["https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/{$tracked->cloudflare_id}/public"],
                'user' => $tracked->user ? [
                    'id' => $tracked->user->id,
                    'name' => $tracked->user->name,
                ] : null,
                'metadata' => $tracked->metadata,
                'tracked_in_db' => true
            ];
        }

        // Add Cloudflare-only images
        foreach ($cloudflareImages as $cfImage) {
            if (!in_array($cfImage['id'], $processedIds)) {
                $merged[] = [
                    'id' => $cfImage['id'],
                    'filename' => $cfImage['filename'] ?? 'Unknown',
                    'context' => $cfImage['metadata']['context'] ?? null,
                    'uploaded_at' => $cfImage['uploaded'] ?? null,
                    'file_size' => $cfImage['meta']['size'] ?? null,
                    'width' => $cfImage['meta']['width'] ?? null,
                    'height' => $cfImage['meta']['height'] ?? null,
                    'url' => $cfImage['variants'][0] ?? null,
                    'thumbnail' => $cfImage['variants'][0] ?? null,
                    'variants' => $cfImage['variants'] ?? [],
                    'user' => null,
                    'metadata' => $cfImage['metadata'] ?? [],
                    'tracked_in_db' => false
                ];
            }
        }

        return $merged;
    }

    /**
     * Clear cache for an entity
     */
    public function clearEntityMediaCache(Request $request, string $entityType, $entityId)
    {
        $cacheKey = "entity_media_{$entityType}_{$entityId}";
        Cache::forget($cacheKey);

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared'
        ]);
    }
}