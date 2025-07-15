<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InjectSiteSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Share site settings with all Inertia responses
        Inertia::share([
            'siteSettings' => function () {
                return [
                    'site_name' => site_setting('site_name', config('app.name', 'Listerino')),
                    'meta_description' => site_setting('meta_description', 'Your local directory for discovering places, businesses, and services'),
                    'meta_keywords' => site_setting('meta_keywords', 'local directory, businesses, places, services, reviews, listings'),
                    'contact_email' => site_setting('contact_email'),
                    'enable_registration' => site_setting('enable_registration', true),
                    'enable_user_listings' => site_setting('enable_user_listings', true),
                    'enable_reviews' => site_setting('enable_reviews', true),
                    'logo_url' => site_setting('logo_url', '/images/listerino_logo.svg'),
                    'primary_color' => site_setting('primary_color', '#4F46E5'),
                ];
            },
        ]);

        return $next($request);
    }
}