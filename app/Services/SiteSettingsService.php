<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService
{
    /**
     * Get a setting value by key with caching
     */
    public function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value, string $type = 'string', string $group = 'general', string $description = null)
    {
        return Setting::set($key, $value, $type, $group, $description);
    }

    /**
     * Get all settings grouped by category
     */
    public function getAllGrouped()
    {
        return Setting::getAllGrouped();
    }

    /**
     * Get all settings as a flat array
     */
    public function getAll()
    {
        return Cache::remember('settings.all.flat', 3600, function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get settings for a specific group
     */
    public function getGroup(string $group)
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return Setting::where('group', $group)->get()->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => Setting::get($setting->key),
                    'type' => $setting->type,
                    'description' => $setting->description,
                ];
            });
        });
    }

    /**
     * Clear the settings cache
     */
    public function clearCache()
    {
        Setting::clearCache();
        Cache::forget('settings.all.flat');
        // Clear all group caches
        $groups = ['general', 'seo', 'social', 'appearance', 'security', 'features', 'email', 'api', 'maintenance'];
        foreach ($groups as $group) {
            Cache::forget("settings.group.{$group}");
        }
    }

    /**
     * Get SEO metadata settings
     */
    public function getSeoMetadata()
    {
        return [
            'title' => $this->get('site_name', 'Listerino'),
            'tagline' => $this->get('site_tagline', 'Your Local Directory'),
            'description' => $this->get('meta_description', 'Discover the best places, businesses, and services in your area'),
            'keywords' => $this->get('meta_keywords', ''),
            'og_image' => $this->get('og_image', ''),
            'twitter_handle' => $this->get('twitter_handle', ''),
        ];
    }

    /**
     * Get appearance settings
     */
    public function getAppearanceSettings()
    {
        return [
            'primary_color' => $this->get('primary_color', '#3B82F6'),
            'logo_url' => $this->get('logo_url', ''),
            'favicon_url' => $this->get('favicon_url', ''),
        ];
    }

    /**
     * Get feature flags
     */
    public function getFeatureFlags()
    {
        return [
            'enable_user_registration' => $this->get('enable_user_registration', true),
            'enable_social_login' => $this->get('enable_social_login', false),
            'max_upload_size' => (int) $this->get('max_upload_size', 10),
            'items_per_page' => (int) $this->get('items_per_page', 20),
        ];
    }

    /**
     * Check if a feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) $this->get($feature, false);
    }

    /**
     * Get email settings
     */
    public function getEmailSettings()
    {
        return [
            'from_address' => $this->get('email_from_address', config('mail.from.address')),
            'from_name' => $this->get('email_from_name', config('mail.from.name')),
        ];
    }
}