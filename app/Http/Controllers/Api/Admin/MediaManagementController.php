<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\CloudflareImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class MediaManagementController extends Controller
{
    public function __construct(
        private CloudflareImageService $cloudflareService
    ) {}

    /**
     * Get all media with Cloudflare sync
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get tracked media from database
            $query = Media::with(['user:id,firstname,lastname,email']);

            // Apply filters
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('context') && $request->context !== '') {
                $query->byContext($request->context);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $query->where('filename', 'like', '%' . $request->search . '%');
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Get paginated results
            $trackedMedia = $query->paginate($request->get('per_page', 20));

            // Get untracked images from Cloudflare (if requested)
            $untrackedImages = [];
            if ($request->get('show_untracked', false)) {
                $untrackedImages = $this->getUntrackedImages();
            }

            return response()->json([
                'tracked' => $trackedMedia,
                'untracked' => $untrackedImages,
                'statistics' => $this->getQuickStats()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch media', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch media',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all images from Cloudflare and compare with tracked
     */
    public function syncWithCloudflare(): JsonResponse
    {
        try {
            $cloudflareImages = $this->getAllCloudflareImages();
            $trackedIds = Media::pluck('cloudflare_id')->toArray();
            
            $untracked = [];
            $tracked = [];
            
            foreach ($cloudflareImages as $cfImage) {
                if (!in_array($cfImage['id'], $trackedIds)) {
                    $untracked[] = [
                        'cloudflare_id' => $cfImage['id'],
                        'filename' => $cfImage['filename'] ?? 'Unknown',
                        'uploaded' => $cfImage['uploaded'] ?? null,
                        'size' => $cfImage['meta']['size'] ?? null,
                        'width' => $cfImage['meta']['width'] ?? null,
                        'height' => $cfImage['meta']['height'] ?? null,
                        'url' => $this->getImageUrl($cfImage['id']),
                        'thumbnail_url' => $this->getImageUrl($cfImage['id'], 'thumbnail')
                    ];
                } else {
                    $tracked[] = $cfImage['id'];
                }
            }
            
            return response()->json([
                'success' => true,
                'untracked_count' => count($untracked),
                'tracked_count' => count($tracked),
                'untracked_images' => $untracked
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to sync with Cloudflare', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync with Cloudflare',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import untracked image from Cloudflare
     */
    public function importFromCloudflare(Request $request): JsonResponse
    {
        $request->validate([
            'cloudflare_id' => 'required|string',
            'filename' => 'nullable|string',
            'context' => 'nullable|string'
        ]);

        try {
            // Check if already exists
            $existing = Media::where('cloudflare_id', $request->cloudflare_id)->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image already tracked'
                ], 400);
            }

            // Get image details from Cloudflare
            $imageDetails = null;
            try {
                $imageDetails = $this->cloudflareService->getImageDetails($request->cloudflare_id);
            } catch (Exception $e) {
                Log::warning('Could not fetch Cloudflare details for import', [
                    'cloudflare_id' => $request->cloudflare_id,
                    'error' => $e->getMessage()
                ]);
            }

            // Build media record
            $filename = $request->filename ?? 'imported_' . time();
            
            // Determine MIME type from filename extension
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimeType = match($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default => 'image/jpeg' // Assume image if unknown
            };
            
            $mediaData = [
                'cloudflare_id' => $request->cloudflare_id,
                'url' => $this->getImageUrl($request->cloudflare_id),
                'filename' => $filename,
                'mime_type' => $mimeType,
                'user_id' => auth()->id(),
                'context' => $request->context,
                'status' => 'approved',
                'metadata' => ['imported' => true, 'import_date' => now()]
            ];

            // Add Cloudflare metadata if available
            if ($imageDetails && isset($imageDetails['result'])) {
                $result = $imageDetails['result'];
                if (isset($result['meta'])) {
                    $mediaData['file_size'] = $result['meta']['size'] ?? null;
                    $mediaData['width'] = $result['meta']['width'] ?? null;
                    $mediaData['height'] = $result['meta']['height'] ?? null;
                    $mediaData['mime_type'] = $result['meta']['type'] ?? null;
                }
                $mediaData['metadata']['cloudflare_data'] = $result;
            }

            $media = Media::create($mediaData);

            Log::info('Imported untracked image from Cloudflare', [
                'media_id' => $media->id,
                'cloudflare_id' => $media->cloudflare_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image imported successfully',
                'media' => $media
            ]);

        } catch (Exception $e) {
            Log::error('Failed to import from Cloudflare', [
                'error' => $e->getMessage(),
                'cloudflare_id' => $request->cloudflare_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete media item
     */
    public function destroy($id): JsonResponse
    {
        try {
            $media = Media::findOrFail($id);
            
            // Try to delete from Cloudflare
            try {
                $this->cloudflareService->deleteImage($media->cloudflare_id);
            } catch (Exception $e) {
                Log::warning('Failed to delete from Cloudflare', [
                    'cloudflare_id' => $media->cloudflare_id,
                    'error' => $e->getMessage()
                ]);
            }

            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete media', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import untracked images
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'cloudflare_ids' => 'required|array',
            'cloudflare_ids.*' => 'string'
        ]);

        $imported = 0;
        $failed = 0;

        foreach ($request->cloudflare_ids as $cfId) {
            try {
                // Skip if already exists
                if (Media::where('cloudflare_id', $cfId)->exists()) {
                    continue;
                }

                // Determine MIME type from filename extension
                $filename = 'bulk_import_' . time() . '_' . $imported;
                $mimeType = 'image/jpeg'; // Default
                
                Media::create([
                    'cloudflare_id' => $cfId,
                    'url' => $this->getImageUrl($cfId),
                    'filename' => $filename,
                    'mime_type' => $mimeType,
                    'user_id' => auth()->id(),
                    'status' => 'approved',
                    'metadata' => ['bulk_imported' => true]
                ]);

                $imported++;
            } catch (Exception $e) {
                $failed++;
                Log::error('Failed to bulk import image', [
                    'cloudflare_id' => $cfId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'failed' => $failed
        ]);
    }

    /**
     * Get all images from Cloudflare API
     */
    private function getAllCloudflareImages(): array
    {
        // Cache for 5 minutes to avoid hitting API too often
        return Cache::remember('cloudflare_images_list', 300, function () {
            $images = [];
            $page = 1;
            $perPage = 100;
            
            try {
                $accountId = config('services.cloudflare.account_id');
                $token = config('services.cloudflare.images_token');
                
                do {
                    // Use the authentication method that matches the token type
                    $email = config('services.cloudflare.email');
                    $headers = strlen($token) > 37 || str_contains($token, '_') 
                        ? ['Authorization' => "Bearer {$token}"]
                        : [
                            'X-Auth-Email' => $email,
                            'X-Auth-Key' => $token
                        ];
                    
                    $response = Http::withHeaders($headers)
                        ->get("https://api.cloudflare.com/client/v4/accounts/{$accountId}/images/v1", [
                            'page' => $page,
                            'per_page' => $perPage
                        ]);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['result']['images'])) {
                            $images = array_merge($images, $data['result']['images']);
                        }
                        
                        // Check if there are more pages
                        $totalPages = $data['result_info']['total_pages'] ?? 1;
                        if ($page >= $totalPages) {
                            break;
                        }
                        $page++;
                    } else {
                        Log::error('Failed to fetch Cloudflare images', [
                            'status' => $response->status(),
                            'body' => $response->body()
                        ]);
                        break;
                    }
                } while ($page <= 10); // Safety limit
                
            } catch (Exception $e) {
                Log::error('Error fetching Cloudflare images', [
                    'error' => $e->getMessage()
                ]);
            }
            
            return $images;
        });
    }

    /**
     * Get untracked images
     */
    private function getUntrackedImages(): array
    {
        $cloudflareImages = $this->getAllCloudflareImages();
        $trackedIds = Media::pluck('cloudflare_id')->toArray();
        
        $untracked = [];
        foreach ($cloudflareImages as $cfImage) {
            if (!in_array($cfImage['id'], $trackedIds)) {
                $untracked[] = [
                    'cloudflare_id' => $cfImage['id'],
                    'filename' => $cfImage['filename'] ?? 'Unknown',
                    'uploaded' => $cfImage['uploaded'] ?? null,
                    'url' => $this->getImageUrl($cfImage['id']),
                    'thumbnail_url' => $this->getImageUrl($cfImage['id'], 'thumbnail')
                ];
            }
        }
        
        return $untracked;
    }

    /**
     * Get image URL
     */
    private function getImageUrl(string $cloudflareId, string $variant = 'public'): string
    {
        $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
        return "{$deliveryUrl}/{$cloudflareId}/{$variant}";
    }

    /**
     * Get quick statistics
     */
    private function getQuickStats(): array
    {
        return [
            'total_tracked' => Media::count(),
            'total_size' => Media::sum('file_size'),
            'by_context' => Media::select('context', DB::raw('count(*) as count'))
                ->groupBy('context')
                ->pluck('count', 'context')
        ];
    }
}