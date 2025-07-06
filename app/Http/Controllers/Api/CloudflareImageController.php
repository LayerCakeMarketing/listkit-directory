<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CloudflareImage;
use App\Services\CloudflareImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

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
                'context' => 'nullable|string|in:avatar,cover,gallery,logo,item',
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
        // Debug logging
        Log::info('confirmUpload called', [
            'request_data' => $request->all(),
            'user_id' => auth()->id(),
            'authenticated' => auth()->check()
        ]);
        
        try {
            $request->validate([
                'cloudflare_id' => 'required|string',
                'filename' => 'required|string',
                'context' => 'nullable|string|in:avatar,cover,gallery,logo,item',
                'entity_type' => 'nullable|string',
                'entity_id' => 'nullable|integer',
                'metadata' => 'nullable|array',
            ]);

            // Check if image already exists in database
            $existingImage = CloudflareImage::where('cloudflare_id', $request->cloudflare_id)->first();
            if ($existingImage) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image already tracked',
                    'image' => $existingImage,
                ]);
            }

            // Get image details from Cloudflare to get metadata
            $cloudflareData = null;
            try {
                $cloudflareData = $this->cloudflareService->getImageDetails($request->cloudflare_id);
            } catch (Exception $e) {
                Log::warning('Could not fetch Cloudflare image details: ' . $e->getMessage());
            }

            // Create database record
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
                $imageData['file_size'] = $cfResult['meta']['size'] ?? null;
                $imageData['width'] = $cfResult['meta']['width'] ?? null;
                $imageData['height'] = $cfResult['meta']['height'] ?? null;
                $imageData['mime_type'] = $cfResult['meta']['type'] ?? null;
                $imageData['metadata'] = array_merge($imageData['metadata'], $cfResult);
            }

            $image = CloudflareImage::create($imageData);

            Log::info('confirmUpload success', [
                'cloudflare_id' => $request->cloudflare_id,
                'image_id' => $image->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upload confirmed and tracked',
                'image' => $image,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to confirm upload: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e,
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
                'context' => 'nullable|string|in:avatar,cover,gallery,logo,item',
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