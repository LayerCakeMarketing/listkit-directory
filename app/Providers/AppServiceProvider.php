<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SiteSettingsService as singleton
        $this->app->singleton(\App\Services\SiteSettingsService::class, function ($app) {
            return new \App\Services\SiteSettingsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Region policy
        Gate::policy(\App\Models\Region::class, \App\Policies\RegionPolicy::class);
        
        // Register Place policy
        Gate::policy(\App\Models\Place::class, \App\Policies\PlacePolicy::class);
        
        // Register Channel policy
        Gate::policy(\App\Models\Channel::class, \App\Policies\ChannelPolicy::class);
        
        // Register custom gates
        Gate::define('manage-regions', function ($user) {
            return in_array($user->role, ['admin', 'manager']);
        });
    }
}