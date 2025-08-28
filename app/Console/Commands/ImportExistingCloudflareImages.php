<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CloudflareImage;
use App\Models\Media;
use Illuminate\Support\Facades\DB;

class ImportExistingCloudflareImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:import-cloudflare';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import existing CloudflareImage records into the Media table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of existing Cloudflare images to Media table...');
        
        $cloudflareImages = CloudflareImage::all();
        $totalCount = $cloudflareImages->count();
        
        if ($totalCount === 0) {
            $this->info('No CloudflareImage records found to import.');
            return 0;
        }
        
        $this->info("Found {$totalCount} CloudflareImage records to import.");
        
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($cloudflareImages as $cfImage) {
            try {
                // Check if already exists in media table
                $existingMedia = Media::where('cloudflare_id', $cfImage->cloudflare_id)->first();
                
                if ($existingMedia) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Determine context from metadata or default
                $context = null;
                if (!empty($cfImage->context)) {
                    $context = $cfImage->context;
                } elseif (!empty($cfImage->metadata['context'])) {
                    $context = $cfImage->metadata['context'];
                }
                
                // Build the Cloudflare URL
                $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
                $url = "{$deliveryUrl}/{$cfImage->cloudflare_id}/public";
                
                // Create Media record
                Media::create([
                    'cloudflare_id' => $cfImage->cloudflare_id,
                    'url' => $url,
                    'filename' => $cfImage->filename ?? 'unknown',
                    'mime_type' => $cfImage->mime_type,
                    'file_size' => $cfImage->file_size,
                    'width' => $cfImage->width,
                    'height' => $cfImage->height,
                    'entity_type' => $cfImage->entity_type,
                    'entity_id' => $cfImage->entity_id,
                    'user_id' => $cfImage->user_id,
                    'context' => $context,
                    'metadata' => $cfImage->metadata ?? [],
                    'status' => 'approved',
                    'created_at' => $cfImage->uploaded_at ?? $cfImage->created_at,
                    'updated_at' => $cfImage->updated_at,
                ]);
                
                $imported++;
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError importing CloudflareImage ID {$cfImage->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Import completed!");
        $this->info("Imported: {$imported}");
        $this->info("Skipped (already exists): {$skipped}");
        
        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }
        
        return 0;
    }
}
