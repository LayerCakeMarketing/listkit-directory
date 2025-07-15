<?php

use App\Services\SiteSettingsService;

if (!function_exists('site_setting')) {
    /**
     * Get a site setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function site_setting(string $key, $default = null)
    {
        return app(SiteSettingsService::class)->get($key, $default);
    }
}

if (!function_exists('site_settings')) {
    /**
     * Get the site settings service instance
     *
     * @return SiteSettingsService
     */
    function site_settings(): SiteSettingsService
    {
        return app(SiteSettingsService::class);
    }
}