<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\CloudflareImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class MediaController extends Controller
{
    public function __construct(
        private CloudflareImageService $cloudflareService
    ) {}

    /**
     * Display a listing of media items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Media::with(['user:id,firstname,lastname,email']);
        
        Log::info('MediaController::index called', [
            'auth_user' => auth()->id(),
            'request_params' => $request->all(),
        ]);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by entity
        if ($request->has('entity_type') && $request->has('entity_id')) {
            $query->forEntity($request->entity_type, $request->entity_id);
        }

        // Filter by context
        if ($request->filled('context')) {
            $query->byContext($request->context);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by filename
        if ($request->filled('search')) {
            $query->where('filename', 'like', '%' . $request->search . '%');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Debug: Log the SQL query
        Log::info('MediaController::index query SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $media = $query->paginate($request->get('per_page', 20));
        
        Log::info('MediaController::index result', [
            'total' => $media->total(),
            'per_page' => $media->perPage(),
            'current_page' => $media->currentPage(),
            'data_count' => count($media->items()),
        ]);

        return response()->json($media);
    }

    /**
     * Store a newly created media in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cloudflare_id' => 'required|string|unique:media,cloudflare_id',
            'url' => 'required|url',
            'filename' => 'required|string',
            'mime_type' => 'nullable|string',
            'file_size' => 'nullable|integer',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
            'context' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $media = Media::create([
                'cloudflare_id' => $request->cloudflare_id,
                'url' => $request->url,
                'filename' => $request->filename,
                'mime_type' => $request->mime_type,
                'file_size' => $request->file_size,
                'width' => $request->width,
                'height' => $request->height,
                'entity_type' => $request->entity_type,
                'entity_id' => $request->entity_id,
                'user_id' => auth()->id(),
                'context' => $request->context,
                'metadata' => $request->metadata ?? [],
                'status' => 'approved', // Auto-approve for now
            ]);

            Log::info('Media item created', [
                'media_id' => $media->id,
                'cloudflare_id' => $media->cloudflare_id,
                'user_id' => $media->user_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media item created successfully',
                'media' => $media,
            ], 201);

        } catch (Exception $e) {
            Log::error('Failed to create media item', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create media item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified media.
     */
    public function show(Media $media): JsonResponse
    {
        $media->load(['user:id,firstname,lastname,email']);
        
        return response()->json([
            'success' => true,
            'media' => $media,
        ]);
    }

    /**
     * Update the specified media in storage.
     */
    public function update(Request $request, Media $media): JsonResponse
    {
        $request->validate([
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
            'context' => 'nullable|string',
            'metadata' => 'nullable|array',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        try {
            $media->update($request->only([
                'entity_type',
                'entity_id',
                'context',
                'metadata',
                'status',
            ]));

            Log::info('Media item updated', [
                'media_id' => $media->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media item updated successfully',
                'media' => $media,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update media item', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update media item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified media from storage.
     */
    public function destroy(Media $media): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete from Cloudflare
            try {
                $this->cloudflareService->deleteImage($media->cloudflare_id);
            } catch (Exception $e) {
                Log::warning('Failed to delete image from Cloudflare', [
                    'cloudflare_id' => $media->cloudflare_id,
                    'error' => $e->getMessage(),
                ]);
                // Continue even if Cloudflare deletion fails
            }

            // Soft delete the media record
            $media->delete();

            DB::commit();

            Log::info('Media item deleted', [
                'media_id' => $media->id,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media item deleted successfully',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete media item', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get media statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_media' => Media::count(),
                'total_size' => Media::sum('file_size'),
                'by_context' => Media::select('context', DB::raw('count(*) as count'))
                    ->groupBy('context')
                    ->pluck('count', 'context'),
                'by_status' => Media::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'recent_uploads' => Media::with('user:id,firstname,lastname')
                    ->latest()
                    ->take(10)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get media statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get media statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete media items.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:media,id',
        ]);

        try {
            DB::beginTransaction();

            $mediaItems = Media::whereIn('id', $request->ids)->get();

            foreach ($mediaItems as $media) {
                // Try to delete from Cloudflare
                try {
                    $this->cloudflareService->deleteImage($media->cloudflare_id);
                } catch (Exception $e) {
                    Log::warning('Failed to delete image from Cloudflare during bulk delete', [
                        'cloudflare_id' => $media->cloudflare_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $media->delete();
            }

            DB::commit();

            Log::info('Bulk media deletion completed', [
                'count' => count($request->ids),
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' media items deleted successfully',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to bulk delete media items', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk delete media items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}