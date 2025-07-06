<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CloudflareImage;
use App\Services\CloudflareImageService;

class TrackUnlinkedProfileImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:track-profile-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Track unlinked profile images in the CloudflareImage table';

    /**
     * Execute the console command.
     */
    public function handle(CloudflareImageService $cloudflareService)
    {
        $this->info('Searching for untracked profile images...');
        
        $users = User::whereNotNull('avatar_cloudflare_id')
            ->orWhereNotNull('cover_cloudflare_id')
            ->orWhereNotNull('page_logo_cloudflare_id')
            ->get();
        
        $tracked = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            // Check avatar
            if ($user->avatar_cloudflare_id) {
                if (!CloudflareImage::where('cloudflare_id', $user->avatar_cloudflare_id)->exists()) {
                    try {
                        // Try to get image details from Cloudflare
                        $details = null;
                        try {
                            $details = $cloudflareService->getImageDetails($user->avatar_cloudflare_id);
                        } catch (\Exception $e) {
                            // Continue without details
                        }
                        
                        CloudflareImage::create([
                            'cloudflare_id' => $user->avatar_cloudflare_id,
                            'filename' => $details['result']['filename'] ?? 'avatar.jpg',
                            'user_id' => $user->id,
                            'context' => 'avatar',
                            'entity_type' => 'App\\Models\\User',
                            'entity_id' => $user->id,
                            'metadata' => [
                                'type' => 'avatar',
                                'tracked_retroactively' => true,
                                'original_metadata' => $details['result'] ?? []
                            ],
                            'file_size' => $details['result']['meta']['size'] ?? null,
                            'width' => $details['result']['meta']['width'] ?? null,
                            'height' => $details['result']['meta']['height'] ?? null,
                            'mime_type' => $details['result']['meta']['type'] ?? null,
                            'uploaded_at' => $details['result']['uploaded'] ?? $user->updated_at,
                        ]);
                        
                        $this->line("Tracked avatar for user {$user->name} (ID: {$user->id})");
                        $tracked++;
                    } catch (\Exception $e) {
                        $this->warn("Failed to track avatar for user {$user->name}: " . $e->getMessage());
                    }
                } else {
                    $skipped++;
                }
            }
            
            // Check cover image
            if ($user->cover_cloudflare_id) {
                if (!CloudflareImage::where('cloudflare_id', $user->cover_cloudflare_id)->exists()) {
                    try {
                        $details = null;
                        try {
                            $details = $cloudflareService->getImageDetails($user->cover_cloudflare_id);
                        } catch (\Exception $e) {
                            // Continue without details
                        }
                        
                        CloudflareImage::create([
                            'cloudflare_id' => $user->cover_cloudflare_id,
                            'filename' => $details['result']['filename'] ?? 'cover.jpg',
                            'user_id' => $user->id,
                            'context' => 'cover',
                            'entity_type' => 'App\\Models\\User',
                            'entity_id' => $user->id,
                            'metadata' => [
                                'type' => 'cover',
                                'tracked_retroactively' => true,
                                'original_metadata' => $details['result'] ?? []
                            ],
                            'file_size' => $details['result']['meta']['size'] ?? null,
                            'width' => $details['result']['meta']['width'] ?? null,
                            'height' => $details['result']['meta']['height'] ?? null,
                            'mime_type' => $details['result']['meta']['type'] ?? null,
                            'uploaded_at' => $details['result']['uploaded'] ?? $user->updated_at,
                        ]);
                        
                        $this->line("Tracked cover image for user {$user->name} (ID: {$user->id})");
                        $tracked++;
                    } catch (\Exception $e) {
                        $this->warn("Failed to track cover image for user {$user->name}: " . $e->getMessage());
                    }
                } else {
                    $skipped++;
                }
            }
            
            // Check page logo
            if ($user->page_logo_cloudflare_id) {
                if (!CloudflareImage::where('cloudflare_id', $user->page_logo_cloudflare_id)->exists()) {
                    try {
                        $details = null;
                        try {
                            $details = $cloudflareService->getImageDetails($user->page_logo_cloudflare_id);
                        } catch (\Exception $e) {
                            // Continue without details
                        }
                        
                        CloudflareImage::create([
                            'cloudflare_id' => $user->page_logo_cloudflare_id,
                            'filename' => $details['result']['filename'] ?? 'logo.jpg',
                            'user_id' => $user->id,
                            'context' => 'logo',
                            'entity_type' => 'App\\Models\\User',
                            'entity_id' => $user->id,
                            'metadata' => [
                                'type' => 'page_logo',
                                'tracked_retroactively' => true,
                                'original_metadata' => $details['result'] ?? []
                            ],
                            'file_size' => $details['result']['meta']['size'] ?? null,
                            'width' => $details['result']['meta']['width'] ?? null,
                            'height' => $details['result']['meta']['height'] ?? null,
                            'mime_type' => $details['result']['meta']['type'] ?? null,
                            'uploaded_at' => $details['result']['uploaded'] ?? $user->updated_at,
                        ]);
                        
                        $this->line("Tracked page logo for user {$user->name} (ID: {$user->id})");
                        $tracked++;
                    } catch (\Exception $e) {
                        $this->warn("Failed to track page logo for user {$user->name}: " . $e->getMessage());
                    }
                } else {
                    $skipped++;
                }
            }
        }
        
        $this->info("Tracking complete!");
        $this->info("Tracked: {$tracked} images");
        $this->info("Skipped (already tracked): {$skipped} images");
        
        return Command::SUCCESS;
    }
}