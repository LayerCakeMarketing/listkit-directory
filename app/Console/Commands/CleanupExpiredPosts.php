<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ImageKitService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired posts and clean up their media from ImageKit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired posts...');
        
        // Get expired posts
        $expiredPosts = Post::expired()->get();
        
        if ($expiredPosts->isEmpty()) {
            $this->info('No expired posts found.');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$expiredPosts->count()} expired posts to clean up.");
        
        $imageKitService = app(ImageKitService::class);
        $deletedCount = 0;
        $mediaCleanedCount = 0;
        
        foreach ($expiredPosts as $post) {
            try {
                // Clean up media from ImageKit
                if ($post->media && is_array($post->media)) {
                    foreach ($post->media as $mediaItem) {
                        if (!empty($mediaItem['fileId'])) {
                            try {
                                $imageKitService->deleteImage($mediaItem['fileId']);
                                $mediaCleanedCount++;
                                $this->info("Deleted media {$mediaItem['fileId']} from ImageKit");
                            } catch (\Exception $e) {
                                $this->error("Failed to delete media {$mediaItem['fileId']}: " . $e->getMessage());
                                Log::error('Failed to delete ImageKit media during cleanup', [
                                    'post_id' => $post->id,
                                    'file_id' => $mediaItem['fileId'],
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
                
                // Delete the post
                $post->forceDelete();
                $deletedCount++;
                $this->info("Deleted post ID: {$post->id}");
                
            } catch (\Exception $e) {
                $this->error("Failed to delete post {$post->id}: " . $e->getMessage());
                Log::error('Failed to delete expired post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("Cleanup completed: {$deletedCount} posts deleted, {$mediaCleanedCount} media items cleaned from ImageKit.");
        
        return Command::SUCCESS;
    }
}