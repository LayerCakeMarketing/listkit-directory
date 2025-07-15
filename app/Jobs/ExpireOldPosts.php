<?php

namespace App\Jobs;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireOldPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Soft delete expired posts
        $expiredCount = Post::expired()->count();
        
        if ($expiredCount > 0) {
            Post::expired()->chunk(100, function ($posts) {
                foreach ($posts as $post) {
                    $post->delete();
                }
            });
            
            Log::info("Expired {$expiredCount} posts");
        }
        
        // Permanently delete posts that have been soft deleted for more than 7 days
        $purgeDate = Carbon::now()->subDays(7);
        $purgedCount = Post::onlyTrashed()
            ->where('deleted_at', '<', $purgeDate)
            ->count();
            
        if ($purgedCount > 0) {
            Post::onlyTrashed()
                ->where('deleted_at', '<', $purgeDate)
                ->forceDelete();
                
            Log::info("Permanently deleted {$purgedCount} posts");
        }
    }
}