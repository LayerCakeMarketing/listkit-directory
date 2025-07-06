<?php

namespace App\Http\Controllers;

use App\Services\CloudflareImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessImageUpload;
use Exception;

class ImageUploadController extends Controller
{
    private CloudflareImageService $imageService;

    public function __construct(CloudflareImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Generate a signed upload URL for direct client uploads
     */
    public function generateUploadUrl(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|in:avatar,cover,page_logo,list_image,entry_logo',
                'entity_id' => 'nullable|integer',
                'filename' => 'nullable|string|max:255',
            ]);

            // Map upload type to context
            $contextMap = [
                'avatar' => 'avatar',
                'cover' => 'cover',
                'page_logo' => 'logo',
                'list_image' => 'cover',
                'entry_logo' => 'logo',
            ];

            $metadata = [
                'type' => $validated['type'],
                'context' => $contextMap[$validated['type']] ?? 'gallery',
                'user_id' => auth()->id(),
                'entity_id' => $validated['entity_id'] ?? null,
                'entity_type' => 'App\\Models\\User', // Profile images belong to User model
                'filename' => $validated['filename'] ?? null,
                'uploaded_at' => now()->toISOString(),
            ];

            $result = $this->imageService->generateSignedUploadUrl($metadata);

            return response()->json([
                'success' => true,
                'data' => [
                    'uploadURL' => $result['uploadURL'],
                    'imageId' => $result['id'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate upload URL', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate upload URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm upload completion and store in database
     */
    public function confirmUpload(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cloudflare_id' => 'required|string',
                'type' => 'required|string|in:avatar,cover,page_logo,list_image,entry_logo',
                'entity_id' => 'nullable|integer',
                'filename' => 'nullable|string|max:255',
                'file_size' => 'nullable|integer',
            ]);

            // Map upload type to context
            $contextMap = [
                'avatar' => 'avatar',
                'cover' => 'cover',
                'page_logo' => 'logo',
                'list_image' => 'cover',
                'entry_logo' => 'logo',
            ];

            // Check if image already exists
            $existingImage = \App\Models\CloudflareImage::where('cloudflare_id', $validated['cloudflare_id'])->first();
            if ($existingImage) {
                // Get image URLs
                $urls = $this->imageService->getImageVariants($validated['cloudflare_id']);
                
                return response()->json([
                    'success' => true,
                    'image' => [
                        'id' => $existingImage->id,
                        'cloudflare_id' => $existingImage->cloudflare_id,
                        'filename' => $existingImage->filename,
                        'urls' => $urls,
                    ]
                ]);
            }

            // Create CloudflareImage record
            $image = \App\Models\CloudflareImage::create([
                'cloudflare_id' => $validated['cloudflare_id'],
                'filename' => $validated['filename'] ?? 'unknown.jpg',
                'user_id' => auth()->id(),
                'context' => $contextMap[$validated['type']] ?? 'gallery',
                'entity_type' => 'App\\Models\\User', // Profile images belong to User model
                'entity_id' => auth()->id(), // The user ID this profile image belongs to
                'metadata' => [
                    'type' => $validated['type'],
                    'uploaded_via' => 'profile_editor',
                ],
                'file_size' => $validated['file_size'] ?? null,
                'uploaded_at' => now(),
            ]);

            // Get image URLs
            $urls = $this->imageService->getImageVariants($validated['cloudflare_id']);

            return response()->json([
                'success' => true,
                'image' => [
                    'id' => $image->id,
                    'cloudflare_id' => $validated['cloudflare_id'],
                    'urls' => $urls,
                    'filename' => $validated['filename'],
                    'file_size' => $validated['file_size'] ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to confirm upload', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload image immediately (for small images)
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:10240', // 10MB
            'type' => 'required|string|in:avatar,cover,page_logo,list_image,entry_logo',
            'entity_id' => 'nullable|integer',
        ]);

        try {
            $file = $request->file('image');
            $type = $request->input('type');
            $entityId = $request->input('entity_id');

            // Upload to Cloudflare Images
            $uploadResult = $this->imageService->uploadImage($file, [
                'metadata' => [
                    'user_id' => Auth::id(),
                    'type' => $type,
                    'entity_id' => $entityId,
                    'original_name' => $file->getClientOriginalName(),
                ]
            ]);

            // Store in database
            $imageRecord = DB::table('uploaded_images')->insertGetId([
                'user_id' => Auth::id(),
                'cloudflare_id' => $uploadResult['id'],
                'type' => $type,
                'entity_id' => $entityId,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'variants' => json_encode($uploadResult['variants']),
                'meta' => json_encode($uploadResult['meta']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get image URLs
            $urls = $this->imageService->getImageVariants($uploadResult['id']);

            return response()->json([
                'success' => true,
                'image' => [
                    'id' => $imageRecord,
                    'cloudflare_id' => $uploadResult['id'],
                    'urls' => $urls,
                    'filename' => $uploadResult['filename'],
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Image upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Queue image upload for large files (async processing)
     */
    public function uploadAsync(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:20480', // 20MB for async
            'type' => 'required|string|in:avatar,cover,page_logo,list_image,entry_logo',
            'entity_id' => 'nullable|integer',
        ]);

        try {
            $file = $request->file('image');
            
            // Store file temporarily
            $tempPath = $file->store('temp-uploads', 'local');
            
            // Create pending record in database
            $uploadRecord = DB::table('image_uploads')->insertGetId([
                'user_id' => Auth::id(),
                'type' => $request->input('type'),
                'entity_id' => $request->input('entity_id'),
                'temp_path' => $tempPath,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Dispatch job for processing
            ProcessImageUpload::dispatch($uploadRecord);

            return response()->json([
                'success' => true,
                'upload_id' => $uploadRecord,
                'status' => 'processing',
                'message' => 'Image is being processed. You will be notified when complete.'
            ]);

        } catch (Exception $e) {
            Log::error('Async image upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue image upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check upload status (for async uploads)
     */
    public function status($uploadId): JsonResponse
    {
        $upload = DB::table('image_uploads')
            ->where('id', $uploadId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$upload) {
            return response()->json(['success' => false, 'message' => 'Upload not found'], 404);
        }

        $response = [
            'success' => true,
            'status' => $upload->status,
        ];

        if ($upload->status === 'completed' && $upload->cloudflare_id) {
            $urls = $this->imageService->getImageVariants($upload->cloudflare_id);
            $response['image'] = [
                'id' => $upload->id,
                'cloudflare_id' => $upload->cloudflare_id,
                'urls' => $urls,
                'filename' => $upload->original_name,
            ];
        } elseif ($upload->status === 'failed') {
            $response['message'] = $upload->error_message ?? 'Upload failed';
        }

        return response()->json($response);
    }

    /**
     * Delete image
     */
    public function delete($imageId): JsonResponse
    {
        try {
            $image = DB::table('uploaded_images')
                ->where('id', $imageId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$image) {
                return response()->json(['success' => false, 'message' => 'Image not found'], 404);
            }

            // Delete from Cloudflare Images
            $deleted = $this->imageService->deleteImage($image->cloudflare_id);

            if ($deleted) {
                // Remove from database
                DB::table('uploaded_images')->where('id', $imageId)->delete();
                
                return response()->json(['success' => true, 'message' => 'Image deleted successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to delete image'], 500);
            }

        } catch (Exception $e) {
            Log::error('Image deletion failed', [
                'image_id' => $imageId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to delete image'], 500);
        }
    }

    /**
     * Get user's uploaded images
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        
        $query = DB::table('uploaded_images')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');
            
        if ($type) {
            $query->where('type', $type);
        }
        
        $images = $query->paginate(20);
        
        // Add URLs to each image
        $images->getCollection()->transform(function ($image) {
            $image->urls = $this->imageService->getImageVariants($image->cloudflare_id);
            return $image;
        });

        return response()->json([
            'success' => true,
            'images' => $images
        ]);
    }

    /**
     * Get image by ID with URLs
     */
    public function show($imageId): JsonResponse
    {
        $image = DB::table('uploaded_images')
            ->where('id', $imageId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Image not found'], 404);
        }

        $image->urls = $this->imageService->getImageVariants($image->cloudflare_id);

        return response()->json([
            'success' => true,
            'image' => $image
        ]);
    }

    /**
     * Test Cloudflare connection and configuration
     */
    public function testCloudflareConnection(): JsonResponse
    {
        try {
            // Test generating a signed upload URL
            $metadata = [
                'type' => 'test',
                'user_id' => auth()->id(),
                'test' => true,
                'uploaded_at' => now()->toISOString(),
            ];

            $result = $this->imageService->generateSignedUploadUrl($metadata);

            return response()->json([
                'success' => true,
                'message' => 'Cloudflare connection successful',
                'data' => [
                    'account_id' => config('services.cloudflare.account_id'),
                    'delivery_url' => config('services.cloudflare.delivery_url'),
                    'upload_url_generated' => !empty($result['uploadURL']),
                    'image_id_generated' => !empty($result['id']),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Cloudflare connection test failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cloudflare connection failed: ' . $e->getMessage(),
                'config' => [
                    'has_account_id' => !empty(config('services.cloudflare.account_id')),
                    'has_api_token' => !empty(config('services.cloudflare.images_token')),
                    'has_email' => !empty(config('services.cloudflare.email')),
                    'has_delivery_url' => !empty(config('services.cloudflare.delivery_url')),
                ]
            ], 500);
        }
    }
}