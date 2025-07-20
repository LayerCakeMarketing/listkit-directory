<?php

namespace App\Console\Commands;

use App\Models\Place;
use App\Models\CloudflareImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigratePlaceImages extends Command
{
    protected $signature = 'places:migrate-images {--dry-run : Preview what would be migrated without making changes}';
    protected $description = 'Migrate existing place images to CloudflareImage tracking table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Starting place image migration...');
        
        $places = Place::where(function ($query) {
            $query->whereNotNull('logo_url')
                ->orWhereNotNull('cover_image_url')
                ->orWhereRaw("gallery_images::text != '[]'");
        })->get();
            
        $this->info("Found {$places->count()} places with images to process");
        
        $migrated = 0;
        
        foreach ($places as $place) {
            // Process logo
            if ($place->logo_url && $this->isCloudflareUrl($place->logo_url)) {
                $cloudflareId = $this->extractCloudflareId($place->logo_url);
                if ($cloudflareId && !$this->imageExists($cloudflareId, $place)) {
                    if (!$dryRun) {
                        $this->createImageRecord($cloudflareId, 'logo', $place);
                    }
                    $migrated++;
                    $this->line("Would migrate logo for place {$place->id}: {$place->title}");
                }
            }
            
            // Process cover image
            if ($place->cover_image_url && $this->isCloudflareUrl($place->cover_image_url)) {
                $cloudflareId = $this->extractCloudflareId($place->cover_image_url);
                if ($cloudflareId && !$this->imageExists($cloudflareId, $place)) {
                    if (!$dryRun) {
                        $this->createImageRecord($cloudflareId, 'cover', $place);
                    }
                    $migrated++;
                    $this->line("Would migrate cover image for place {$place->id}: {$place->title}");
                }
            }
            
            // Process gallery images
            if ($place->gallery_images && is_array($place->gallery_images)) {
                foreach ($place->gallery_images as $galleryUrl) {
                    if ($this->isCloudflareUrl($galleryUrl)) {
                        $cloudflareId = $this->extractCloudflareId($galleryUrl);
                        if ($cloudflareId && !$this->imageExists($cloudflareId, $place)) {
                            if (!$dryRun) {
                                $this->createImageRecord($cloudflareId, 'gallery', $place);
                            }
                            $migrated++;
                            $this->line("Would migrate gallery image for place {$place->id}: {$place->title}");
                        }
                    }
                }
            }
        }
        
        if ($dryRun) {
            $this->info("Dry run complete. Would migrate {$migrated} images.");
            $this->info('Run without --dry-run to perform the migration.');
        } else {
            $this->info("Migration complete. Migrated {$migrated} images.");
        }
    }
    
    private function isCloudflareUrl($url): bool
    {
        return str_contains($url, 'imagedelivery.net');
    }
    
    private function extractCloudflareId($url): ?string
    {
        // Extract ID from URLs like: https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/ID/public
        if (preg_match('/imagedelivery\.net\/[^\/]+\/([^\/]+)\//', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private function imageExists($cloudflareId, $place): bool
    {
        // Check if image exists with correct entity association
        $existing = CloudflareImage::where('cloudflare_id', $cloudflareId)->first();
        
        if (!$existing) {
            return false;
        }
        
        // If it exists but without proper entity association, update it
        if (!$existing->entity_type || !$existing->entity_id) {
            $existing->update([
                'entity_type' => 'App\Models\Place',
                'entity_id' => $place->id,
            ]);
            $this->line("Updated entity association for existing image {$cloudflareId}");
            return true;
        }
        
        // Image exists with entity association
        return true;
    }
    
    private function createImageRecord($cloudflareId, $context, $place): void
    {
        CloudflareImage::create([
            'cloudflare_id' => $cloudflareId,
            'filename' => "{$context}_{$place->id}.jpg", // Generic filename
            'user_id' => $place->created_by_user_id ?? $place->owner_user_id,
            'context' => $context,
            'entity_type' => 'App\Models\Place',
            'entity_id' => $place->id,
            'metadata' => [
                'migrated' => true,
                'migrated_at' => now()->toIso8601String(),
            ],
            'uploaded_at' => $place->created_at, // Use place creation date as approximation
        ]);
        
        Log::info("Migrated image for place {$place->id}", [
            'cloudflare_id' => $cloudflareId,
            'context' => $context,
            'place_id' => $place->id,
        ]);
    }
}