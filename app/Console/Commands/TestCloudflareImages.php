<?php

namespace App\Console\Commands;

use App\Services\CloudflareImageService;
use Illuminate\Console\Command;
use Exception;

class TestCloudflareImages extends Command
{
    protected $signature = 'cloudflare:test-images';
    protected $description = 'Test Cloudflare Images connection and configuration';

    public function handle()
    {
        $this->info('Testing Cloudflare Images configuration...');

        try {
            $service = app(CloudflareImageService::class);
            
            // Test configuration
            $this->info('✓ Service instantiated successfully');
            
            // Test API connection by getting stats
            $stats = $service->getStats();
            
            if (!empty($stats)) {
                $this->info('✓ API connection successful');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Images Count', $stats['count']['current'] ?? 'N/A'],
                        ['Storage Used', isset($stats['count']['current']) ? number_format($stats['count']['current']) . ' images' : 'N/A'],
                    ]
                );
            } else {
                $this->warn('API connection successful but no stats returned');
            }

            $this->info('✓ Cloudflare Images is properly configured!');
            
            $this->newLine();
            $this->info('Next steps:');
            $this->line('1. Use the CloudflareImageUpload Vue component in your forms');
            $this->line('2. Test uploads via the /api/images/upload endpoint');
            $this->line('3. Monitor uploads in the Cloudflare Images dashboard');

        } catch (Exception $e) {
            $this->error('✗ Configuration test failed: ' . $e->getMessage());
            
            $this->newLine();
            $this->warn('Troubleshooting:');
            $this->line('1. Check your .env file has CLOUDFLARE_ACCOUNT_ID and CLOUDFLARE_IMAGES_TOKEN');
            $this->line('2. Verify the API token has Cloudflare Images:Edit permissions');
            $this->line('3. Ensure Cloudflare Images is enabled on your account');
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}