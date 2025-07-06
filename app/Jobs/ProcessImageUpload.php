<?php

namespace App\Jobs;

use App\Services\CloudflareImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Exception;

class ProcessImageUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    private int $uploadId;

    public function __construct(int $uploadId)
    {
        $this->uploadId = $uploadId;
    }

    public function handle(CloudflareImageService $imageService): void
    {
        try {
            // Get upload record
            $upload = DB::table('image_uploads')->where('id', $this->uploadId)->first();
            
            if (!$upload || $upload->status !== 'pending') {
                Log::warning('Image upload job called for non-pending upload', ['upload_id' => $this->uploadId]);
                return;
            }

            // Update status to processing
            DB::table('image_uploads')
                ->where('id', $this->uploadId)
                ->update([
                    'status' => 'processing',
                    'updated_at' => now(),
                ]);

            // Get the temp file
            $tempFilePath = storage_path('app/' . $upload->temp_path);
            
            if (!file_exists($tempFilePath)) {
                throw new Exception('Temporary file not found');
            }

            // Create UploadedFile instance from temp file
            $file = new UploadedFile(
                $tempFilePath,
                $upload->original_name,
                $upload->mime_type,
                null,
                true
            );

            // Upload to Cloudflare Images
            $uploadResult = $imageService->uploadImage($file, [
                'metadata' => [
                    'user_id' => $upload->user_id,
                    'type' => $upload->type,
                    'entity_id' => $upload->entity_id,
                    'original_name' => $upload->original_name,
                    'processed_async' => true,
                ]
            ]);

            // Store in uploaded_images table
            $imageRecord = DB::table('uploaded_images')->insertGetId([
                'user_id' => $upload->user_id,
                'cloudflare_id' => $uploadResult['id'],
                'type' => $upload->type,
                'entity_id' => $upload->entity_id,
                'original_name' => $upload->original_name,
                'file_size' => $upload->file_size,
                'mime_type' => $upload->mime_type,
                'variants' => json_encode($uploadResult['variants']),
                'meta' => json_encode($uploadResult['meta']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update upload status to completed
            DB::table('image_uploads')
                ->where('id', $this->uploadId)
                ->update([
                    'status' => 'completed',
                    'cloudflare_id' => $uploadResult['id'],
                    'image_record_id' => $imageRecord,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            // Clean up temp file
            Storage::disk('local')->delete($upload->temp_path);

            // Optional: Send notification to user about completion
            $this->notifyUserOfCompletion($upload->user_id, $imageRecord);

            Log::info('Image upload processed successfully', [
                'upload_id' => $this->uploadId,
                'image_id' => $imageRecord,
                'cloudflare_id' => $uploadResult['id']
            ]);

        } catch (Exception $e) {
            // Mark as failed
            DB::table('image_uploads')
                ->where('id', $this->uploadId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            // Clean up temp file if it exists
            if (isset($upload->temp_path)) {
                Storage::disk('local')->delete($upload->temp_path);
            }

            Log::error('Image upload processing failed', [
                'upload_id' => $this->uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }

    public function failed(Exception $exception): void
    {
        // Update upload status to failed
        DB::table('image_uploads')
            ->where('id', $this->uploadId)
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'updated_at' => now(),
            ]);

        Log::error('Image upload job failed permanently', [
            'upload_id' => $this->uploadId,
            'error' => $exception->getMessage()
        ]);
    }

    private function notifyUserOfCompletion(int $userId, int $imageId): void
    {
        // Here you could dispatch a notification, send an email, 
        // trigger a websocket event, etc.
        // For now, we'll just log it
        Log::info('Image upload completed for user', [
            'user_id' => $userId,
            'image_id' => $imageId
        ]);
    }
}