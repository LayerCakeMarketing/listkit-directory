<?php

namespace App\Console\Commands;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpirePost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:expire {id : The ID of the post to expire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually expire a post for testing purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $postId = $this->argument('id');
        
        $post = Post::find($postId);
        
        if (!$post) {
            $this->error("Post with ID {$postId} not found.");
            return Command::FAILURE;
        }
        
        $post->expires_at = Carbon::now()->subMinutes(1);
        $post->saveQuietly(); // Bypass model events
        
        $this->info("Post {$postId} has been manually expired.");
        $this->info("Expiration time set to: {$post->expires_at}");
        
        return Command::SUCCESS;
    }
}