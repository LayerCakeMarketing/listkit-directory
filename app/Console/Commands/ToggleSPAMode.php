<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToggleSPAMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spa:toggle {--enable : Enable SPA mode} {--disable : Disable SPA mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle between SPA and Inertia.js modes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $enable = $this->option('enable');
        $disable = $this->option('disable');

        if (!$enable && !$disable) {
            $this->error('Please specify either --enable or --disable');
            return 1;
        }

        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        if ($enable) {
            // Enable SPA mode
            if (strpos($envContent, 'SPA_MODE=') !== false) {
                $envContent = preg_replace('/SPA_MODE=.*/', 'SPA_MODE=true', $envContent);
            } else {
                $envContent .= "\n\n# SPA Configuration\nSPA_MODE=true\n";
            }

            $this->info('SPA mode enabled!');
            $this->info('Make sure to:');
            $this->info('1. Run "npm run dev" for development');
            $this->info('2. Update your Vite config to use app-spa.js');
            $this->info('3. Clear caches with "php artisan optimize:clear"');
        } else {
            // Disable SPA mode
            if (strpos($envContent, 'SPA_MODE=') !== false) {
                $envContent = preg_replace('/SPA_MODE=.*/', 'SPA_MODE=false', $envContent);
            }

            $this->info('SPA mode disabled! Reverted to Inertia.js mode.');
        }

        File::put($envPath, $envContent);

        // Clear config cache
        $this->call('config:clear');

        return 0;
    }
}