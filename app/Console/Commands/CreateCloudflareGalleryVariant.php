<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudflareImageService;

class CreateCloudflareGalleryVariant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:create-gallery-variant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the gallery-16x9 variant in Cloudflare Images';

    /**
     * Execute the console command.
     */
    public function handle(CloudflareImageService $cloudflareService)
    {
        $this->info('Checking for existing gallery-16x9 variant...');
        
        // List current variants first
        $this->info('Current variants:');
        $variants = $cloudflareService->listVariants();
        if (empty($variants)) {
            $this->warn('No variants found or unable to list variants.');
            $this->warn('Note: Creating custom variants may require a Cloudflare Images plan that supports variants.');
            $this->warn('The default "public" variant will always be available.');
        } else {
            foreach ($variants as $variant) {
                $this->line("- {$variant['id']}");
            }
        }
        
        // Check if variant already exists
        if ($cloudflareService->variantExists('gallery-16x9')) {
            $this->warn('The gallery-16x9 variant already exists!');
            return Command::SUCCESS;
        }
        
        $this->info('Creating gallery-16x9 variant...');
        $this->warn('Note: If this fails, you may need to create the variant manually in your Cloudflare dashboard.');
        
        // Create the variant with 16:9 aspect ratio (1366x768)
        $options = [
            'fit' => 'scale-down',
            'metadata' => 'none',
            'width' => 1366,
            'height' => 768
        ];
        
        if ($cloudflareService->createVariant('gallery-16x9', $options)) {
            $this->info('Successfully created gallery-16x9 variant!');
            
            // List all variants to confirm
            $this->info('Updated variants:');
            $variants = $cloudflareService->listVariants();
            foreach ($variants as $variant) {
                $this->line("- {$variant['id']}");
            }
            
            return Command::SUCCESS;
        } else {
            $this->error('Failed to create gallery-16x9 variant.');
            $this->error('This may be due to plan limitations. You can create the variant manually in Cloudflare dashboard with these settings:');
            $this->line('  ID: gallery-16x9');
            $this->line('  Fit: scale-down');
            $this->line('  Width: 1366');
            $this->line('  Height: 768');
            $this->line('  Metadata: none');
            $this->line('  Never require signed URLs: true');
            return Command::FAILURE;
        }
    }
}