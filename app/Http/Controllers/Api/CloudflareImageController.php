<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CloudflareImage;
use App\Models\Media;
use App\Services\CloudflareImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Validation\ValidationException;

class CloudflareImageController extends Controller
{
    public function __construct(
        private CloudflareImageService $cloudflareService
    ) {}

    /**
     * Generate a signed upload URL for direct client uploads
     */
    public function generateUploadUrl(Request $request): JsonResponse
    {
        try {
            // Validate optional context parameters
            $request->validate([
                'context' => 'nullable|string|in:avatar,cover,gallery,logo,item,region_cover,avatar_temp,banner_temp,banner,marketing',
                'entity_type' => 'nullable|string',
                'entity_id' => 'nullable|integer',
            ]);

            $metadata = [
                'user_id' => auth()->id() ?? 'anonymous',
                'uploaded_via' => 'drag_drop_uploader',
                'timestamp' => now()->toISOString(),
                'context' => $request->get('context'),
                'entity_type' => $request->get('entity_type'),
                'entity_id' => $request->get('entity_id'),
            ];

            $result = $this->cloudflareService->generateSignedUploadUrl($metadata);

            return response()->json([
                'success' => true,
                'uploadURL' => $result['uploadURL'],
                'imageId' => $result['id'],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate upload URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image information and variants
     */
    public function getImageInfo(Request $request, string $imageId): JsonResponse
    {
        try {
            $variants = $this->cloudflareService->getImageVariants($imageId);

            return response()->json([
                'success' => true,
                'id' => $imageId,
                'variants' => $variants,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get image info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm successful upload and save to database
     */
    public function confirmUpload(Request $request): JsonResponse
    {
        // Debug logging with memory usage
        Log::info('confirmUpload called', [
            'request_data' => $request->all(),
            'user_id' => auth()->id(),
            'authenticated' => auth()->check(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
        ]);
        
        try {
            $request->validate([
                'cloudflare_id' => 'required|string',
                'filename' => 'required|string',
                'context' => 'nullable|string|in:avatar,cover,gallery,logo,item,region_cover,avatar_temp,banner_temp,banner',
                'entity_type' => 'nullable|string',
                'entity_id' => 'nullable|integer',
                'metadata' => 'nullable|array',
            ]);

            // Check if image already exists in database
            $existingImage = CloudflareImage::where('cloudflare_id', $request->cloudflare_id)->first();
            if ($existingImage) {
                Log::info('Image already tracked in database', [
                    'cloudflare_id' => $request->cloudflare_id,
                    'existing_image_id' => $existingImage->id
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Image already tracked',
                    'image' => $existingImage,
                ]);
            }

            // Try to get image details from Cloudflare with retry logic
            $cloudflareData = null;
            $maxRetries = 3;
            $retryDelay = 1; // seconds
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    Log::info("Attempting to fetch Cloudflare image details", [
                        'attempt' => $attempt,
                        'cloudflare_id' => $request->cloudflare_id
                    ]);
                    
                    // Wait a bit on retries to allow Cloudflare to propagate the image
                    if ($attempt > 1) {
                        sleep($retryDelay);
                        $retryDelay *= 2; // Exponential backoff
                    }
                    
                    $cloudflareData = $this->cloudflareService->getImageDetails($request->cloudflare_id);
                    
                    if ($cloudflareData) {
                        Log::info('Successfully fetched Cloudflare image details', [
                            'attempt' => $attempt,
                            'has_result' => isset($cloudflareData['result'])
                        ]);
                        break;
                    }
                } catch (Exception $e) {
                    Log::warning("Could not fetch Cloudflare image details (attempt {$attempt}/{$maxRetries})", [
                        'error' => $e->getMessage(),
                        'cloudflare_id' => $request->cloudflare_id
                    ]);
                    
                    // Don't sleep after the last attempt
                    if ($attempt == $maxRetries) {
                        Log::info('Proceeding without Cloudflare metadata after all retries failed');
                    }
                }
            }

            // Create database record regardless of whether we got Cloudflare metadata
            $imageData = [
                'cloudflare_id' => $request->cloudflare_id,
                'filename' => $request->filename,
                'user_id' => auth()->id(),
                'context' => $request->context,
                'entity_type' => $request->entity_type,
                'entity_id' => $request->entity_id,
                'metadata' => $request->metadata ?? [],
                'uploaded_at' => now(),
            ];

            // Add Cloudflare metadata if available
            if ($cloudflareData && isset($cloudflareData['result'])) {
                $cfResult = $cloudflareData['result'];
                Log::info('Adding Cloudflare metadata to image record', [
                    'has_meta' => isset($cfResult['meta']),
                    'meta_keys' => isset($cfResult['meta']) ? array_keys($cfResult['meta']) : []
                ]);
                
                if (isset($cfResult['meta'])) {
                    $imageData['file_size'] = $cfResult['meta']['size'] ?? null;
                    $imageData['width'] = $cfResult['meta']['width'] ?? null;
                    $imageData['height'] = $cfResult['meta']['height'] ?? null;
                    $imageData['mime_type'] = $cfResult['meta']['type'] ?? null;
                }
                $imageData['metadata'] = array_merge($imageData['metadata'], ['cloudflare_result' => $cfResult]);
            } else {
                Log::info('No Cloudflare metadata available, proceeding with basic image data');
            }

            $image = CloudflareImage::create($imageData);

            // Also create a Media record for centralized tracking
            try {
                $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
                $mediaUrl = "{$deliveryUrl}/{$request->cloudflare_id}/public";
                
                $media = Media::create([
                    'cloudflare_id' => $request->cloudflare_id,
                    'url' => $mediaUrl,
                    'filename' => $request->filename,
                    'mime_type' => $imageData['mime_type'] ?? null,
                    'file_size' => $imageData['file_size'] ?? null,
                    'width' => $imageData['width'] ?? null,
                    'height' => $imageData['height'] ?? null,
                    'entity_type' => $request->entity_type,
                    'entity_id' => $request->entity_id,
                    'user_id' => auth()->id(),
                    'context' => $request->context,
                    'metadata' => $request->metadata ?? [],
                    'status' => 'approved',
                ]);

                Log::info('Media record created', [
                    'media_id' => $media->id,
                    'cloudflare_id' => $media->cloudflare_id,
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to create media record', [
                    'error' => $e->getMessage(),
                    'cloudflare_id' => $request->cloudflare_id,
                ]);
                // Don't fail the upload if media tracking fails
            }

            Log::info('confirmUpload success', [
                'cloudflare_id' => $request->cloudflare_id,
                'image_id' => $image->id,
                'media_id' => isset($media) ? $media->id : null,
                'user_id' => auth()->id(),
                'has_metadata' => !empty($imageData['width'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upload confirmed and tracked',
                'image' => $image,
                'media' => $media ?? null,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in confirmUpload', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (Exception $e) {
            Log::error('Failed to confirm upload: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an image
     */
    public function deleteImage(Request $request, string $imageId): JsonResponse
    {
        try {
            // Delete from Cloudflare
            $deleted = $this->cloudflareService->deleteImage($imageId);

            if ($deleted) {
                // Also delete from database if exists
                CloudflareImage::where('cloudflare_id', $imageId)->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete image',
                ], 500);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update image tracking with entity information
     */
    public function updateTracking(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cloudflare_id' => 'required|string',
                'entity_type' => 'nullable|string',
                'entity_id' => 'nullable|integer',
                'context' => 'nullable|string|in:avatar,cover,gallery,logo,item,region_cover,avatar_temp,banner_temp,banner',
            ]);

            $image = CloudflareImage::where('cloudflare_id', $request->cloudflare_id)->first();
            
            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found in database',
                ], 404);
            }

            // Update tracking information
            $image->update([
                'entity_type' => $request->entity_type,
                'entity_id' => $request->entity_id,
                'context' => $request->context ?? $image->context,
            ]);

            Log::info('Image tracking updated', [
                'cloudflare_id' => $request->cloudflare_id,
                'entity_type' => $request->entity_type,
                'entity_id' => $request->entity_id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image tracking updated successfully',
                'image' => $image,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update image tracking: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update image tracking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Cloudflare Images usage stats
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->cloudflareService->getStats();

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}